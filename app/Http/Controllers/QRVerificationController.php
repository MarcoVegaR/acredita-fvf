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
            try {
                $verificationResult = $this->credentialService->verifyCredentialByQR($qrCode);
                
                if ($verificationResult) {
                    $result = [
                        'valid' => $verificationResult['valid'],
                        'data' => $verificationResult['valid'] ? [
                            'employee' => [
                                'first_name' => $verificationResult['employee']['first_name'] ?? '',
                                'last_name' => $verificationResult['employee']['last_name'] ?? '',
                                'position' => $verificationResult['employee']['position'] ?? '',
                                'company' => $verificationResult['employee']['company'] ?? '',
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
                            'credential' => [
                                'issued_at' => $verificationResult['issued_at'],
                                'expires_at' => $verificationResult['expires_at'],
                                'verified_at' => now()->toISOString()
                            ]
                        ] : null,
                        'message' => !$verificationResult['valid'] ? $verificationResult['message'] : null
                    ];
                } else {
                    $result = [
                        'valid' => false,
                        'message' => 'Código QR no encontrado o inválido'
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('[QR VERIFICATION PAGE] Error verificando QR', [
                    'qr_code' => $qrCode,
                    'error' => $e->getMessage()
                ]);
                
                $result = [
                    'valid' => false,
                    'message' => 'Error al verificar la credencial'
                ];
            }
        }

        return Inertia::render('qr/verification', [
            'qrCode' => $qrCode,
            'result' => $result
        ]);
    }
}
