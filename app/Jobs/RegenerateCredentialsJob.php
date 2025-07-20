<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Template;
use App\Models\Credential;
use App\Services\Credential\CredentialServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class RegenerateCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;
    protected $template;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event, Template $template)
    {
        $this->event = $event;
        $this->template = $template;
    }

    /**
     * Execute the job.
     */
    public function handle(CredentialServiceInterface $credentialService): void
    {
        Log::info('[REGENERATE JOB] Iniciando regeneración de credenciales', [
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'template_id' => $this->template->id,
            'template_name' => $this->template->name
        ]);

        try {
            // Obtener todas las credenciales activas del evento
            $credentials = Credential::whereHas('accreditationRequest', function ($query) {
                $query->where('event_id', $this->event->id)
                      ->where('status', 'approved');
            })->get();

            $successCount = 0;
            $errorCount = 0;

            foreach ($credentials as $credential) {
                try {
                    Log::info('[REGENERATE JOB] Regenerando credencial', [
                        'credential_id' => $credential->id,
                        'credential_uuid' => $credential->uuid
                    ]);

                    // Actualizar el template snapshot con la nueva plantilla
                    $templateSnapshot = [
                        'id' => $this->template->id,
                        'name' => $this->template->name,
                        'file_path' => $this->template->file_path,
                        'layout_meta' => $this->template->layout_meta,
                        'version' => $this->template->version,
                        'captured_at' => now()->toISOString()
                    ];

                    $credential->update([
                        'template_snapshot' => $templateSnapshot,
                        'status' => 'pending',
                        'generated_at' => null,
                        'credential_image_path' => null,
                        'credential_pdf_path' => null,
                        'qr_code_path' => null
                    ]);

                    // Regenerar archivos
                    $credentialService->generateQRCode($credential);
                    $credentialService->generateCredentialImage($credential);
                    $credentialService->generateCredentialPDF($credential);

                    // Marcar como completado
                    $credential->update([
                        'status' => 'ready',
                        'generated_at' => now()
                    ]);

                    $successCount++;

                } catch (Exception $e) {
                    $errorCount++;
                    Log::error('[REGENERATE JOB] Error regenerando credencial individual', [
                        'credential_id' => $credential->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('[REGENERATE JOB] Regeneración completada', [
                'total_credentials' => $credentials->count(),
                'success_count' => $successCount,
                'error_count' => $errorCount
            ]);

        } catch (Exception $e) {
            Log::error('[REGENERATE JOB] Error general en regeneración', [
                'event_id' => $this->event->id,
                'template_id' => $this->template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Reenviar excepción para que el job falle y pueda ser reintentado
        }
    }
}
