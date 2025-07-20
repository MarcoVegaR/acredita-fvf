<?php

namespace App\Console\Commands;

use App\Jobs\ExpireEventCredentialsJob;
use App\Jobs\GenerateCredentialJob;
use App\Models\AccreditationRequest;
use App\Models\Credential;
use App\Models\Event;
use App\Services\Credential\CredentialServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:manage 
                           {action : Action to perform (status, regenerate, expire-event, cleanup)}
                           {--request= : Specific AccreditationRequest UUID for regenerate action}
                           {--event= : Specific Event ID for expire-event action}
                           {--force : Force action without confirmation}
                           {--retry-failed : Include failed credentials in regenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage credentials - check status, regenerate, expire, or cleanup';

    protected CredentialServiceInterface $credentialService;

    public function __construct(CredentialServiceInterface $credentialService)
    {
        parent::__construct();
        $this->credentialService = $credentialService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                return $this->showStatus();
            case 'regenerate':
                return $this->regenerateCredentials();
            case 'expire-event':
                return $this->expireEventCredentials();
            case 'cleanup':
                return $this->cleanupCredentials();
            default:
                $this->error("Invalid action: {$action}");
                $this->line('Available actions: status, regenerate, expire-event, cleanup');
                return 1;
        }
    }

    protected function showStatus(): int
    {
        $this->info('=== Estado de Credenciales ===');

        $stats = [
            'pending' => Credential::pending()->count(),
            'generating' => Credential::generating()->count(), 
            'ready' => Credential::ready()->count(),
            'failed' => Credential::failed()->count()
        ];

        $total = array_sum($stats);

        $this->table(
            ['Estado', 'Cantidad', 'Porcentaje'],
            collect($stats)->map(function ($count, $status) use ($total) {
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                return [
                    ucfirst($status),
                    $count,
                    "{$percentage}%"
                ];
            })->toArray()
        );

        $this->line('');
        $this->info("Total de credenciales: {$total}");

        // Mostrar credenciales con errores recurrentes
        $problematicCredentials = Credential::where('retry_count', '>', 1)
            ->where('status', 'failed')
            ->with('accreditationRequest.employee')
            ->get();

        if ($problematicCredentials->isNotEmpty()) {
            $this->line('');
            $this->warn('=== Credenciales con múltiples fallos ===');
            $this->table(
                ['UUID', 'Empleado', 'Reintentos', 'Último Error'],
                $problematicCredentials->map(function ($credential) {
                    $employee = $credential->accreditationRequest->employee;
                    $errorMessage = $credential->error_message;
                    
                    // Intentar decodificar si es JSON
                    if ($errorMessage) {
                        try {
                            $decoded = json_decode($errorMessage, true);
                            $errorMessage = $decoded['message'] ?? $errorMessage;
                        } catch (\Exception $e) {
                            // Mantener mensaje original
                        }
                    }

                    return [
                        $credential->uuid,
                        "{$employee->first_name} {$employee->last_name}",
                        $credential->retry_count,
                        \Str::limit($errorMessage ?: 'Sin mensaje', 50)
                    ];
                })->toArray()
            );
        }

        return 0;
    }

    protected function regenerateCredentials(): int
    {
        $requestUuid = $this->option('request');
        $retryFailed = $this->option('retry-failed');
        $force = $this->option('force');

        if ($requestUuid) {
            // Regenerar credencial específica
            $request = AccreditationRequest::where('uuid', $requestUuid)
                ->with('credential')
                ->first();

            if (!$request) {
                $this->error("Solicitud de acreditación no encontrada: {$requestUuid}");
                return 1;
            }

            if (!$request->credential) {
                $this->error("La solicitud no tiene una credencial asociada: {$requestUuid}");
                return 1;
            }

            if (!$force && !$this->confirm("¿Regenerar credencial para {$request->employee->first_name} {$request->employee->last_name}?")) {
                return 0;
            }

            $this->info("Regenerando credencial...");
            
            try {
                $this->credentialService->regenerateCredential($request->credential);
                $this->info("Credencial regenerada exitosamente. El job fue despachado.");
            } catch (\Exception $e) {
                $this->error("Error al regenerar credencial: " . $e->getMessage());
                return 1;
            }

        } else {
            // Regenerar múltiples credenciales
            $query = Credential::query();
            
            if ($retryFailed) {
                $query->where('status', 'failed');
                $this->info('Regenerando credenciales fallidas...');
            } else {
                $query->whereIn('status', ['pending', 'generating']);
                $this->info('Regenerando credenciales pendientes y en proceso...');
            }

            $credentials = $query->with('accreditationRequest.employee')->get();

            if ($credentials->isEmpty()) {
                $this->info('No hay credenciales para regenerar.');
                return 0;
            }

            $this->table(
                ['UUID', 'Empleado', 'Estado', 'Reintentos'],
                $credentials->map(function ($credential) {
                    $employee = $credential->accreditationRequest->employee;
                    return [
                        $credential->uuid,
                        "{$employee->first_name} {$employee->last_name}",
                        $credential->status,
                        $credential->retry_count
                    ];
                })->toArray()
            );

            if (!$force && !$this->confirm("¿Regenerar {$credentials->count()} credenciales?")) {
                return 0;
            }

            $successCount = 0;
            $errorCount = 0;

            foreach ($credentials as $credential) {
                try {
                    $this->credentialService->regenerateCredential($credential);
                    $successCount++;
                    $this->line("✓ {$credential->uuid}");
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("✗ {$credential->uuid}: " . $e->getMessage());
                }
            }

            $this->info("Regeneración completada: {$successCount} exitosas, {$errorCount} errores.");
        }

        return 0;
    }

    protected function expireEventCredentials(): int
    {
        $eventId = $this->option('event');
        $force = $this->option('force');

        if (!$eventId) {
            $this->error('Se requiere el ID del evento (--event=ID)');
            return 1;
        }

        $event = Event::find($eventId);
        if (!$event) {
            $this->error("Evento no encontrado: {$eventId}");
            return 1;
        }

        $credentialsCount = Credential::whereHas('accreditationRequest', function ($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })->where('is_active', true)->count();

        $this->info("Evento: {$event->name}");
        $this->info("Credenciales activas: {$credentialsCount}");

        if (!$force && !$this->confirm("¿Expirar todas las credenciales de este evento?")) {
            return 0;
        }

        $this->info('Despachando job de expiración...');
        ExpireEventCredentialsJob::dispatch($event);
        
        $this->info('Job despachado exitosamente. Las credenciales serán marcadas como expiradas.');

        return 0;
    }

    protected function cleanupCredentials(): int
    {
        $force = $this->option('force');

        $this->info('=== Limpieza de Credenciales ===');

        // Credenciales órfanas (sin solicitud)
        $orphanCredentials = Credential::whereDoesntHave('accreditationRequest')->count();
        
        // Credenciales muy antiguas fallidas
        $oldFailedCredentials = Credential::where('status', 'failed')
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Credenciales órfanas', $orphanCredentials],
                ['Credenciales fallidas (>30 días)', $oldFailedCredentials]
            ]
        );

        if ($orphanCredentials === 0 && $oldFailedCredentials === 0) {
            $this->info('No hay credenciales para limpiar.');
            return 0;
        }

        if (!$force && !$this->confirm('¿Proceder con la limpieza?')) {
            return 0;
        }

        $deleted = 0;

        // Eliminar órfanas
        if ($orphanCredentials > 0) {
            $deleted += Credential::whereDoesntHave('accreditationRequest')->delete();
            $this->info("✓ Eliminadas {$orphanCredentials} credenciales órfanas");
        }

        // Eliminar fallidas antiguas
        if ($oldFailedCredentials > 0) {
            $deleted += Credential::where('status', 'failed')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
            $this->info("✓ Eliminadas {$oldFailedCredentials} credenciales fallidas antiguas");
        }

        $this->info("Limpieza completada: {$deleted} credenciales eliminadas.");

        return 0;
    }
}
