<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Template;
use App\Models\Credential;
 
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
        // Ejecutar este job en la cola dedicada de credenciales
        $this->onQueue('credentials');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[REGENERATE JOB] Iniciando despacho de regeneración de credenciales', [
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'template_id' => $this->template->id,
            'template_name' => $this->template->name
        ]);

        try {
            $chunkSize = 200; // tamaño de lote para despacho
            $totalDispatched = 0;
            $batches = 0;

            $query = Credential::query()
                ->whereHas('accreditationRequest', function ($q) {
                    $q->where('event_id', $this->event->id)
                      ->where('status', 'approved');
                })
                ->select('id');

            $query->chunkById($chunkSize, function ($rows) use (&$totalDispatched, &$batches) {
                foreach ($rows as $row) {
                    // Preservar QR por defecto y regenerar PDF
                    RegenerateSingleCredentialJob::dispatch($row->id, $this->template->id, false, true);
                    $totalDispatched++;
                }
                $batches++;
            });

            Log::info('[REGENERATE JOB] Despacho de regeneración completado', [
                'total_dispatched' => $totalDispatched,
                'batches' => $batches,
                'chunk_size' => $chunkSize
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
