<?php

namespace App\Jobs;

use App\Models\Credential;
use App\Models\Template;
use App\Services\Credential\CredentialServiceInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegenerateSingleCredentialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180; // seconds

    protected int $credentialId;
    protected int $templateId;
    protected bool $regenerateQr;
    protected bool $regeneratePdf;

    /**
     * Create a new job instance.
     */
    public function __construct(int $credentialId, int $templateId, bool $regenerateQr = false, bool $regeneratePdf = true)
    {
        $this->credentialId = $credentialId;
        $this->templateId = $templateId;
        $this->regenerateQr = $regenerateQr;
        $this->regeneratePdf = $regeneratePdf;
        $this->onQueue('credentials');
    }

    /**
     * Execute the job.
     */
    public function handle(CredentialServiceInterface $credentialService): void
    {
        $credential = Credential::find($this->credentialId);
        if (!$credential) {
            Log::warning('[REGENERATE SINGLE] Credencial no encontrada', [
                'credential_id' => $this->credentialId,
            ]);
            return;
        }

        $template = Template::find($this->templateId);
        if (!$template) {
            Log::warning('[REGENERATE SINGLE] Template no encontrado', [
                'credential_id' => $this->credentialId,
                'template_id' => $this->templateId,
            ]);
            return;
        }

        Log::info('[REGENERATE SINGLE] Iniciando', [
            'credential_id' => $credential->id,
            'credential_uuid' => $credential->uuid,
            'template_id' => $template->id,
        ]);

        try {
            // Actualizar snapshot del template al nuevo
            $templateSnapshot = [
                'id' => $template->id,
                'name' => $template->name,
                'file_path' => $template->file_path,
                'layout_meta' => $template->layout_meta,
                'version' => $template->version,
                'captured_at' => now()->toISOString(),
            ];

            // Preparar estado y limpiar artefactos de imagen/PDF (preservar QR por defecto)
            $update = [
                'template_snapshot' => $templateSnapshot,
                'status' => 'generating',
                'generated_at' => null,
                'credential_image_path' => null,
                'credential_pdf_path' => null,
                'error_message' => null,
            ];

            if ($this->regenerateQr) {
                // Si explícitamente se pide regenerar QR, limpiarlo
                $update['qr_code'] = null;
                $update['qr_image_path'] = null;
            }

            $credential->update($update);

            // Asegurar QR existente: solo generar si falta o se solicitó regenerar
            if ($this->regenerateQr || empty($credential->qr_code) || empty($credential->qr_image_path)) {
                $credentialService->generateQRCode($credential);
            }

            // Regenerar imagen y opcionalmente PDF
            $credentialService->generateCredentialImage($credential);
            if ($this->regeneratePdf) {
                $credentialService->generateCredentialPDF($credential);
            }

            // Finalizar
            $credential->update([
                'status' => 'ready',
                'generated_at' => now(),
            ]);

            Log::info('[REGENERATE SINGLE] Completado', [
                'credential_id' => $credential->id,
            ]);
        } catch (Exception $e) {
            Log::error('[REGENERATE SINGLE] Error', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage(),
            ]);
            // Marcar como failed y re-lanzar para retry
            $credential->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[REGENERATE SINGLE] Job falló definitivamente', [
            'credential_id' => $this->credentialId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
