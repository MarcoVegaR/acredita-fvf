<?php

namespace App\Http\Controllers;

use App\Models\AccreditationRequest;
use App\Services\Credential\CredentialServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CredentialController extends BaseController
{
    protected $credentialService;

    public function __construct(CredentialServiceInterface $credentialService)
    {
        $this->credentialService = $credentialService;
    }

    // Removed: show method - functionality moved to AccreditationRequest show with tabs

    /**
     * Previsualizar credencial en modal
     */
    public function preview(AccreditationRequest $request)
    {
        Gate::authorize('credential.preview');
        
        $this->logAction('preview_credential', "Previsualizar credencial: {$request->uuid}");

        try {
            $request->load(['credential', 'employee', 'event', 'zones']);

            if (!$request->credential || !$request->credential->is_ready) {
                return response()->json([
                    'error' => 'Credencial no disponible para preview'
                ], 404);
            }

            return Inertia::render('credentials/preview', [
                'request' => $request,
                'credential' => $request->credential,
                'imageUrl' => $this->getCredentialImageUrl($request->credential),
                'canDownload' => $request->credential->is_ready
            ]);

        } catch (\Throwable $e) {
            return $this->handleException($e, 'Previsualizar credencial');
        }
    }

    /**
     * Descargar credencial como PNG
     */
    public function downloadImage(AccreditationRequest $request)
    {
        Gate::authorize('credential.download');
        
        $this->logAction('download_credential_image', "Descargar imagen de credencial: {$request->uuid}");

        try {
            $credential = $request->credential;

            if (!$credential || !$credential->is_ready) {
                return $this->respondWithError(
                    'accreditation-requests.show',
                    ['request' => $request],
                    'Credencial no disponible para descarga.'
                );
            }

            $imagePath = $credential->credential_image_path;
            
            if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
                return $this->respondWithError(
                    'accreditation-requests.show', 
                    ['request' => $request],
                    'Archivo de credencial no encontrado.'
                );
            }

            $filename = "credencial_{$request->employee->identification}.png";
            
            return Response::download(
                Storage::disk('public')->path($imagePath),
                $filename,
                ['Content-Type' => 'image/png']
            );

        } catch (\Throwable $e) {
            return $this->handleException($e, 'Descargar imagen de credencial');
        }
    }

    /**
     * Descargar credencial como PDF
     */
    public function downloadPdf(AccreditationRequest $request)
    {
        Gate::authorize('credential.download');
        
        $this->logAction('download_credential_pdf', "Descargar PDF de credencial: {$request->uuid}");

        try {
            $credential = $request->credential;

            if (!$credential || !$credential->is_ready) {
                return $this->respondWithError(
                    'accreditation-requests.show',
                    ['request' => $request],
                    'Credencial no disponible para descarga.'
                );
            }

            $pdfPath = $credential->credential_pdf_path;
            
            if (!$pdfPath || !Storage::disk('public')->exists($pdfPath)) {
                return $this->respondWithError(
                    'accreditation-requests.show',
                    ['request' => $request], 
                    'Archivo PDF de credencial no encontrado.'
                );
            }

            $filename = "credencial_{$request->employee->identification}.pdf";
            
            return Response::download(
                Storage::disk('public')->path($pdfPath),
                $filename,
                ['Content-Type' => 'application/pdf']
            );

        } catch (\Throwable $e) {
            return $this->handleException($e, 'Descargar PDF de credencial');
        }
    }

    /**
     * Obtener estado de credencial (para polling)
     */
    public function status(AccreditationRequest $request)
    {
        Gate::authorize('credential.view');
        
        try {
            $credential = $request->credential;

            if (!$credential) {
                return response()->json([
                    'credential' => null,
                    'status' => 'not_created'
                ]);
            }

            return response()->json([
                'credential' => [
                    'id' => $credential->id,
                    'uuid' => $credential->uuid,
                    'status' => $credential->status,
                    'retry_count' => $credential->retry_count,
                    'error_message' => $credential->error_message,
                    'generated_at' => $credential->generated_at,
                    'is_ready' => $credential->is_ready
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error obteniendo estado de credencial'
            ], 500);
        }
    }

    /**
     * Verificación pública de credencial (sin auth)
     */
    public function verify(string $qrCode)
    {
        try {
            $verification = $this->credentialService->verifyCredentialByQR($qrCode);

            if (!$verification) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Código QR no válido o credencial no encontrada'
                ], 404);
            }

            return response()->json($verification);

        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error en verificación'
            ], 500);
        }
    }

    /**
     * Regenerar credencial manualmente (admin only)
     */
    public function regenerate(AccreditationRequest $request)
    {
        Gate::authorize('credential.regenerate');
        
        $this->logAction('regenerate_credential', "Regenerar credencial: {$request->uuid}");

        try {
            if ($request->status !== \App\Enums\AccreditationStatus::Approved) {
                return $this->respondWithError(
                    'accreditation-requests.show',
                    ['accreditation_request' => $request->uuid],
                    'Solo se pueden regenerar credenciales de solicitudes aprobadas.'
                );
            }

            // Crear nueva credencial si no existe
            if (!$request->credential) {
                $credential = $this->credentialService->createCredentialForRequest($request);
            } else {
                $credential = $request->credential;
                $credential->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'retry_count' => 0
                ]);
            }

            // IMPORTANTE: Re-capturar template actual para obtener cambios de font_size
            $this->credentialService->captureSnapshots($credential);

            // Disparar job
            \App\Jobs\GenerateCredentialJob::dispatch($credential);

            return $this->redirectWithSuccess(
                'accreditation-requests.show',
                ['accreditation_request' => $request->uuid],
                'Regeneración de credencial iniciada. Se procesará en segundo plano.'
            );

        } catch (\Throwable $e) {
            return $this->handleException($e, 'Regenerar credencial');
        }
    }
    /**
     * Obtener URL de imagen de credencial
     */
    private function getCredentialImageUrl($credential): ?string
    {
        if (!$credential->credential_image_path) {
            return null;
        }

        return Storage::disk('public')->url($credential->credential_image_path);
    }
}
