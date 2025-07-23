<?php

namespace App\Http\Controllers;

use App\Services\Credential\CredentialServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QRVerificationController extends Controller
{
    public function __construct(
        private CredentialServiceInterface $credentialService
    ) {}

    /**
     * Página web para verificar credencial por QR
     */
    public function page(Request $request): Response
    {
        $qrCode = $request->query('qr');
        $result = null;

        if ($qrCode) {
            \Log::info('[QR VERIFICATION] Iniciando verificación', [
                'qr_code' => $qrCode,
                'qr_length' => strlen($qrCode)
            ]);

            try {
                $verificationResult = $this->credentialService->verifyCredentialByQR($qrCode);
                
                \Log::info('[QR VERIFICATION] Resultado del servicio', [
                    'result' => $verificationResult
                ]);
                
                if ($verificationResult) {
                    $result = [
                        'valid' => $verificationResult['valid'],
                        'data' => $verificationResult['valid'] ? [
                            'employee' => [
                                'first_name' => $verificationResult['employee']['first_name'] ?? '',
                                'last_name' => $verificationResult['employee']['last_name'] ?? '',
                                // Usamos la identificación del snapshot si existe, formato tipo-número
                                'identification' => ($verificationResult['employee']['document_type'] && $verificationResult['employee']['document_number']) ? 
                                    $verificationResult['employee']['document_type'] . '-' . $verificationResult['employee']['document_number'] :
                                    $verificationResult['employee']['identification'] ?? '',
                                // También incluimos los campos individuales para más flexibilidad en el frontend
                                'document_type' => $verificationResult['employee']['document_type'] ?? '',
                                'document_number' => $verificationResult['employee']['document_number'] ?? '',
                                // Para el cargo/función usamos 'function' que es el campo correcto en el snapshot
                                'position' => $verificationResult['employee']['function'] ?? $verificationResult['employee']['position'] ?? '',
                                // Para la compañía usamos el nombre del proveedor desde el objeto provider anidado
                                'company' => $verificationResult['employee']['provider']['name'] ?? 
                                            $verificationResult['employee']['company'] ?? 
                                            'No especificado',
                            ],
                            'event' => [
                                'name' => $verificationResult['event']['name'] ?? '',
                                'location' => $verificationResult['event']['location'] ?? '',
                                'start_date' => $verificationResult['event']['start_date'] ?? '',
                                'end_date' => $verificationResult['event']['end_date'] ?? '',
                            ],
                            'zones' => array_map(function($zone) {
                                return [
                                    'name' => $zone['name'] ?? '',
                                    'color' => $zone['color'] ?? ''
                                ];
                            }, $verificationResult['zones'] ?? []),
                            'request_status' => $verificationResult['request_status'] ?? 'unknown',
                            'credential' => [
                                'status' => $verificationResult['credential_status'] ?? 'unknown',
                                'issued_at' => $verificationResult['issued_at'],
                                'expires_at' => $verificationResult['expires_at'],
                                'verified_at' => now()->toISOString()
                            ]
                        ] : null,
                        'message' => !$verificationResult['valid'] ? ($verificationResult['message'] ?? 'Credencial inválida') : null
                    ];
                } else {
                    \Log::warning('[QR VERIFICATION] Servicio retornó null', [
                        'qr_code' => $qrCode
                    ]);
                    
                    $result = [
                        'valid' => false,
                        'message' => 'Código QR no encontrado o inválido'
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('[QR VERIFICATION PAGE] Error verificando QR', [
                    'qr_code' => $qrCode,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $result = [
                    'valid' => false,
                    'message' => 'Error al verificar la credencial: ' . $e->getMessage()
                ];
            }
        }

        return Inertia::render('qr/verification', [
            'qrCode' => $qrCode,
            'result' => $result
        ]);
    }
}
