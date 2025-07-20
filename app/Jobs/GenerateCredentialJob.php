<?php

namespace App\Jobs;

use App\Models\Credential;
use App\Services\Credential\CredentialServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCredentialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;           // Máximo 3 intentos
    public $maxExceptions = 3;   // Máximo 3 excepciones  
    public $timeout = 120;       // 2 minutos timeout

    protected $credential;

    /**
     * Create a new job instance.
     */
    public function __construct(Credential $credential)
    {
        $this->credential = $credential;
        $this->onQueue('credentials'); // Cola dedicada
    }

    /**
     * Execute the job.
     */
    public function handle(CredentialServiceInterface $credentialService): void
    {
        Log::info('[GENERATE CREDENTIAL JOB] Iniciando procesamiento', [
            'credential_id' => $this->credential->id,
            'credential_uuid' => $this->credential->uuid,
            'attempt' => $this->attempts(),
            'queue' => $this->queue
        ]);

        try {
            // 🔒 Verificar idempotencia ANTES de procesar
            $this->credential->refresh();
            if ($this->credential->status === 'ready') {
                Log::info('[GENERATE CREDENTIAL JOB] Credencial ya generada, omitiendo...', [
                    'credential_id' => $this->credential->id
                ]);
                return;
            }

            // Procesar generación completa
            $credentialService->processCredentialGeneration($this->credential);

            Log::info('[GENERATE CREDENTIAL JOB] Generación completada exitosamente', [
                'credential_id' => $this->credential->id,
                'credential_uuid' => $this->credential->uuid,
                'qr_code' => $this->credential->fresh()->qr_code
            ]);

        } catch (Exception $e) {
            $this->handleJobFailure($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[GENERATE CREDENTIAL JOB] Job falló definitivamente', [
            'credential_id' => $this->credential->id,
            'attempts' => $this->attempts(),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->credential->update([
            'status' => 'failed',
            'error_message' => $this->summarizeException($exception),
            'retry_count' => $this->attempts()
        ]);
    }

    /**
     * Manejar fallos del job con retry automático
     */
    private function handleJobFailure(Exception $e): void
    {
        $retryCount = $this->attempts();
        $maxRetries = config('credentials.retry.max_attempts', 3);
        $delaySeconds = config('credentials.retry.delay_seconds', 30);

        Log::warning('[GENERATE CREDENTIAL JOB] Error en procesamiento', [
            'credential_id' => $this->credential->id,
            'attempt' => $retryCount,
            'max_attempts' => $maxRetries,
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);

        // 📝 Stack trace resumido para debugging
        $errorSummary = [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'attempt' => $retryCount,
            'timestamp' => now()->toISOString(),
            'type' => get_class($e)
        ];

        $this->credential->update([
            'error_message' => json_encode($errorSummary),
            'retry_count' => $retryCount
        ]);

        // 🔄 Retry automático con delay incremental
        if ($retryCount < $maxRetries) {
            $delay = $delaySeconds * $retryCount; // 30s, 60s, 90s
            Log::info('[GENERATE CREDENTIAL JOB] Programando reintento', [
                'credential_id' => $this->credential->id,
                'delay_seconds' => $delay,
                'next_attempt' => $retryCount + 1
            ]);
            
            $this->release($delay);
        } else {
            // Falló definitivamente
            Log::error('[GENERATE CREDENTIAL JOB] Máximo de reintentos alcanzado', [
                'credential_id' => $this->credential->id,
                'total_attempts' => $retryCount
            ]);
            
            $this->fail($e);
        }
    }

    /**
     * Resumir excepción para storage
     */
    private function summarizeException(Throwable $exception): string
    {
        return json_encode([
            'message' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'type' => get_class($exception),
            'failed_at' => now()->toISOString(),
            'total_attempts' => $this->attempts()
        ]);
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil()
    {
        return now()->addMinutes(10); // 10 minutos máximo total
    }
}
