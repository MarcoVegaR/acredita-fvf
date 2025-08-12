<?php

namespace App\Services\Credential;

use App\Models\AccreditationRequest;
use App\Models\Credential;
use App\Repositories\Credential\CredentialRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Exception;

class CredentialService implements CredentialServiceInterface
{
    protected $credentialRepository;
    protected $imageManager;
    protected $currentCredentialUuid;

    public function __construct(CredentialRepositoryInterface $credentialRepository)
    {
        $this->credentialRepository = $credentialRepository;
        if (extension_loaded('imagick')) {
            $this->imageManager = new ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
        } else {
            $this->imageManager = new ImageManager(new Driver());
        }
    }

    /**
     * Crear credencial inicial para una solicitud aprobada
     */
    public function createCredentialForRequest(AccreditationRequest $request): Credential
    {
        Log::info('[CREDENTIAL SERVICE] Creando credencial inicial', [
            'request_id' => $request->id,
            'request_uuid' => $request->uuid
        ]);

        return $this->credentialRepository->create([
            'accreditation_request_id' => $request->id,
            'status' => 'pending'
        ]);
    }

    /**
     * Capturar snapshots inmutables de datos
     */
    public function captureSnapshots(Credential $credential): void
    {
        Log::info('[CREDENTIAL SERVICE] Capturando snapshots', [
            'credential_id' => $credential->id
        ]);

        $request = $credential->accreditationRequest->load([
            'employee.provider.area', 
            'event.templates',
            'zones'
        ]);

        // Snapshot del empleado
        Log::info('[CREDENTIAL SERVICE] DEBUG: Datos del empleado antes de snapshot', [
            'function' => $request->employee->function,
            'photo_path' => $request->employee->photo_path,
            'document_type' => $request->employee->document_type,
            'document_number' => $request->employee->document_number
        ]);
        
        $employeeSnapshot = [
            'id' => $request->employee->id,
            'first_name' => $request->employee->first_name,
            'last_name' => $request->employee->last_name,
            'document_type' => $request->employee->document_type,
            'document_number' => $request->employee->document_number,
            'function' => $request->employee->function,
            'photo_path' => $request->employee->photo_path,
            'provider_id' => $request->employee->provider_id,
            'provider' => [
                'id' => $request->employee->provider->id,
                'name' => $request->employee->provider->name,
                'rif' => $request->employee->provider->rif,
                'type' => $request->employee->provider->type,
                'area' => [
                    'id' => $request->employee->provider->area->id ?? null,
                    'name' => $request->employee->provider->area->name ?? null,
                    'color' => $request->employee->provider->area->color ?? '#000000'
                ]
            ],
            'captured_at' => now()->toISOString()
        ];
        
        Log::info('[CREDENTIAL SERVICE] DEBUG: Employee snapshot creado', $employeeSnapshot);

        // Snapshot del template
        $defaultTemplate = $request->event->templates->where('is_default', true)->first();
        $templateSnapshot = $defaultTemplate ? [
            'id' => $defaultTemplate->id,
            'name' => $defaultTemplate->name,
            'file_path' => $defaultTemplate->file_path,
            'layout_meta' => $defaultTemplate->layout_meta,
            'version' => $defaultTemplate->version,
            'captured_at' => now()->toISOString()
        ] : null;

        // Snapshot del evento
        $eventSnapshot = [
            'id' => $request->event->id,
            'name' => $request->event->name,
            'description' => $request->event->description,
            'start_date' => $request->event->start_date?->toISOString(),
            'end_date' => $request->event->end_date?->toISOString(),
            'location' => $request->event->location,
            'status' => $request->event->status,
            'captured_at' => now()->toISOString()
        ];

        // Snapshot de las zonas
        $zonesSnapshot = $request->zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'color' => $zone->color,
                'capacity' => $zone->capacity,
            ];
        })->toArray();

        $credential->update([
            'employee_snapshot' => $employeeSnapshot,
            'template_snapshot' => $templateSnapshot,
            'event_snapshot' => $eventSnapshot,
            'zones_snapshot' => $zonesSnapshot
        ]);

        Log::info('[CREDENTIAL SERVICE] Snapshots capturados exitosamente');
    }

    /**
     * Generar código QR único
     */
    public function generateQRCode(Credential $credential): string
    {
        Log::info('[CREDENTIAL SERVICE] Generando código QR', [
            'credential_id' => $credential->id
        ]);

        // Generar código único
        $qrCode = 'CRD_' . Str::upper(Str::random(12)) . '_' . $credential->id;
        
        // Verificar unicidad
        $attempts = 0;
        while ($this->credentialRepository->findByQRCode($qrCode) && $attempts < 10) {
            $qrCode = 'CRD_' . Str::upper(Str::random(12)) . '_' . $credential->id;
            $attempts++;
        }

        if ($attempts >= 10) {
            throw new Exception('No se pudo generar un código QR único después de 10 intentos');
        }

        // Generar imagen QR con URL completa del sistema
        $verificationUrl = url('/verify-qr?qr=' . $qrCode);
        $qrConfig = config('credentials.qr');
        
        Log::info('[CREDENTIAL SERVICE] Generando QR con URL', [
            'qr_code' => $qrCode,
            'verification_url' => $verificationUrl
        ]);
        
        $qrImage = QrCode::format('png')
            ->size($qrConfig['size'])
            ->margin($qrConfig['margin'])
            ->errorCorrection($qrConfig['error_correction'])
            ->encoding($qrConfig['encoding'])
            ->generate($verificationUrl);

        // Guardar imagen QR
        $qrPath = config('credentials.paths.qr') . '/' . $qrCode . '.png';
        Storage::disk('public')->put($qrPath, $qrImage);

        // Actualizar credencial
        $credential->update([
            'qr_code' => $qrCode,
            'qr_image_path' => $qrPath
        ]);

        Log::info('[CREDENTIAL SERVICE] Código QR generado exitosamente', [
            'qr_code' => $qrCode,
            'qr_path' => $qrPath
        ]);

        return $qrCode;
    }

    /**
     * Generar imagen de credencial final
     */
    public function generateCredentialImage(Credential $credential): string
    {
        // Establecer el UUID actual para uso en getTextForBlock
        $this->currentCredentialUuid = $credential->uuid;
        
        Log::info('[CREDENTIAL SERVICE] Generando imagen de credencial', [
            'credential_id' => $credential->id,
            'credential_uuid' => $credential->uuid
        ]);

        // Detectar orientación y dimensiones del template
        $dimensions = $this->detectTemplateDimensions($credential);
        
        Log::info('[CREDENTIAL SERVICE] Dimensiones detectadas', [
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'orientation' => $dimensions['orientation']
        ]);
        
        // Crear canvas con las dimensiones detectadas
        $canvas = $this->imageManager->create($dimensions['width'], $dimensions['height']);
        $canvas->fill('#ffffff'); // Fondo blanco

        // Aplicar template si existe
        Log::info('[CREDENTIAL SERVICE] DEBUG: Decidiendo tipo de template', [
            'has_template_snapshot' => !empty($credential->template_snapshot),
            'template_snapshot_type' => gettype($credential->template_snapshot)
        ]);
        
        if ($credential->template_snapshot) {
            Log::info('[CREDENTIAL SERVICE] DEBUG: Usando template personalizado');
            $this->applyTemplateToCanvas($canvas, $credential, $dimensions);
        } else {
            // Template básico por defecto
            Log::info('[CREDENTIAL SERVICE] DEBUG: Usando template por defecto');
            $this->applyDefaultTemplate($canvas, $credential);
        }

        // Incrustar datos del empleado
        $this->embedEmployeeData($canvas, $credential, $dimensions);

        // Incrustar foto del empleado
        $this->embedEmployeePhoto($canvas, $credential, $dimensions);

        // Incrustar QR
        $this->embedQRCode($canvas, $credential, $dimensions);

        // Guardar imagen
        $filename = 'credential_' . $credential->uuid . '.png';
        $imagePath = config('credentials.paths.images') . '/' . $filename;
        
        // DEBUGGING: Información del canvas antes de guardar
        $encodedImage = $canvas->toPng();
        $imageSize = strlen($encodedImage);
        
        Log::info('[CREDENTIAL SERVICE] DEBUGGING - Guardando imagen final', [
            'filename' => $filename,
            'image_path' => $imagePath,
            'image_size_bytes' => $imageSize,
            'canvas_dimensions' => $dimensions['width'] . 'x' . $dimensions['height'],
            'storage_path' => Storage::disk('public')->path($imagePath)
        ]);
        
        Storage::disk('public')->put($imagePath, $encodedImage);
        $credential->update(['credential_image_path' => $imagePath]);

        // Verificar que el archivo se guardó correctamente
        $savedFileSize = Storage::disk('public')->size($imagePath);
        Log::info('[CREDENTIAL SERVICE] Imagen de credencial generada y verificada', [
            'image_path' => $imagePath,
            'final_dimensions' => $dimensions['width'] . 'x' . $dimensions['height'],
            'saved_file_size' => $savedFileSize,
            'encoding_size' => $imageSize,
            'sizes_match' => $savedFileSize === $imageSize
        ]);

        return $imagePath;
    }

    /**
     * Generar PDF con credencial incrustada
     */
    public function generateCredentialPDF(Credential $credential): string
    {
        Log::info('[CREDENTIAL SERVICE] Generando PDF de credencial', [
            'credential_id' => $credential->id
        ]);

        // Obtenemos la ruta de la imagen PNG
        $pngPath = $credential->credential_image_path;
        
        // Definimos el nombre del archivo PDF resultante
        $filename = 'credential_' . $credential->uuid . '.pdf';
        
        // Ruta relativa donde se guardará el PDF (dentro del disco 'public')
        $relativePdfPath = config('credentials.paths.images') . '/' . $filename;
        
        // Verificar que existe la imagen PNG original
        if (!$pngPath || !Storage::disk('public')->exists($pngPath)) {
            Log::error('[CREDENTIAL SERVICE] No se encontró imagen para generar PDF', [
                'credential_id' => $credential->id,
                'png_path' => $pngPath
            ]);
            return '';  // Devolvemos string vacío en lugar de null para cumplir con la interfaz
        }
        
        try {
            // Obtenemos la ruta absoluta de la imagen PNG
            $imagePath = Storage::disk('public')->path($pngPath);
            
            // Obtenemos las dimensiones de la imagen
            list($width, $height) = getimagesize($imagePath);
            
            // Constantes de conversión según documentación FPDF (igual que en PrintBatchJob)
            $defaultDpi = 96; // DPI por defecto que usa FPDF para imágenes
            $mmPerInch = 25.4; // 1 pulgada = 25.4 milímetros
            
            // Conversión píxel → milímetro: mm = px * 25.4 / dpi
            $credentialMmWidth = $width * $mmPerInch / $defaultDpi;
            $credentialMmHeight = $height * $mmPerInch / $defaultDpi;
            
            // Determinar la orientación del PDF basándonos en las dimensiones
            $orientation = ($width > $height) ? 'L' : 'P';
            
            // Inicializar FPDF con dimensiones calculadas correctamente
            $pdf = new \FPDF(
                $orientation, 
                'mm', 
                [round($credentialMmWidth, 2), round($credentialMmHeight, 2)]
            );
            
            // Configuración de página
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0); // Sin márgenes para fidelidad 1:1
            
            // Verificar dimensiones reales del PDF
            $actualPageWidth = $pdf->GetPageWidth();
            $actualPageHeight = $pdf->GetPageHeight();
            
            Log::info('[CREDENTIAL SERVICE] Dimensiones de PDF', [
                'credential_id' => $credential->id,
                'pixel_dimensions' => "{$width}x{$height}",
                'expected_mm' => round($credentialMmWidth, 2) . 'x' . round($credentialMmHeight, 2),
                'actual_mm' => $actualPageWidth . 'x' . $actualPageHeight,
                'dimensions_match' => (abs($actualPageWidth - $credentialMmWidth) < 0.1 && 
                                     abs($actualPageHeight - $credentialMmHeight) < 0.1)
            ]);
            
            // Agregar la imagen al PDF con tamaño completo de la página
            $pdf->Image(
                $imagePath, 
                0, 
                0, 
                $actualPageWidth, 
                $actualPageHeight
            );
            
            // Ruta absoluta donde guardar el PDF
            $absolutePdfPath = Storage::disk('public')->path($relativePdfPath);
            
            // Guardamos el PDF en disco
            $pdf->Output('F', $absolutePdfPath);
            
            // Actualizamos el modelo con la ruta relativa (para acceso via URL)
            $credential->update(['credential_pdf_path' => $relativePdfPath]);
            
            Log::info('[CREDENTIAL SERVICE] PDF generado correctamente', [
                'pdf_path' => $relativePdfPath,
                'absolute_path' => $absolutePdfPath,
                'orientation' => $orientation,
                'width_mm' => $actualPageWidth,
                'height_mm' => $actualPageHeight
            ]);
            
            return $relativePdfPath;
            
        } catch (\Exception $e) {
            Log::error('[CREDENTIAL SERVICE] Error al generar PDF', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '';  // Devolvemos string vacío en lugar de null para cumplir con la interfaz
        }
    }

    /**
     * Procesar generación completa de credencial
     */
    public function processCredentialGeneration(Credential $credential): void
    {
        Log::info('[CREDENTIAL SERVICE] Iniciando generación completa', [
            'credential_id' => $credential->id,
            'credential_uuid' => $credential->uuid
        ]);

        try {
            // Marcar como generando
            $credential->update(['status' => 'generating']);

            // Pasos de generación
            $this->captureSnapshots($credential);
            $this->generateQRCode($credential);
            $this->generateCredentialImage($credential);
            $this->generateCredentialPDF($credential);

            // Marcar como completada
            $credential->update([
                'status' => 'ready',
                'generated_at' => now(),
                'error_message' => null
            ]);

            Log::info('[CREDENTIAL SERVICE] Generación completada exitosamente', [
                'credential_id' => $credential->id
            ]);

        } catch (Exception $e) {
            Log::error('[CREDENTIAL SERVICE] Error en generación', [
                'credential_id' => $credential->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-lanzar para que el Job maneje el retry
        }
    }

    /**
     * Verificar credencial por código QR
     */
    public function verifyCredentialByQR(string $qrCode): ?array
    {
        Log::info('[CREDENTIAL SERVICE] Verificando QR', [
            'qr_code' => $qrCode
        ]);
        
        $credential = $this->credentialRepository->findByQRCode($qrCode);

        if (!$credential) {
            Log::warning('[CREDENTIAL SERVICE] QR no encontrado en base de datos', [
                'qr_code' => $qrCode
            ]);
            return null;
        }

        Log::info('[CREDENTIAL SERVICE] Credencial encontrada', [
            'credential_id' => $credential->id,
            'credential_uuid' => $credential->uuid,
            'credential_status' => $credential->status,
            'is_valid' => $credential->isValid(),
            'is_expired' => $credential->isExpired(),
            'accreditation_request_id' => $credential->accreditationRequest->id ?? null,
            'request_status' => $credential->accreditationRequest->status ?? null
        ]);

        if (!$credential->isValid()) {
            return [
                'valid' => false,
                'message' => $credential->isExpired() ? 'Credencial expirada' : 'Credencial inactiva',
                'credential_status' => $credential->status,
                'request_status' => $credential->accreditationRequest->status ?? 'unknown'
            ];
        }

        return [
            'valid' => true,
            'employee' => $credential->employee_snapshot,
            'event' => $credential->event_snapshot,
            'zones' => $credential->zones_snapshot,
            'credential_status' => $credential->status,
            'request_status' => $credential->accreditationRequest->status ?? 'approved',
            'issued_at' => $credential->generated_at,
            'expires_at' => $credential->expires_at
        ];
    }

    /**
     * Expirar todas las credenciales de un evento
     */
    public function expireEventCredentials(int $eventId): int
    {
        Log::info('[CREDENTIAL SERVICE] Expirando credenciales del evento', [
            'event_id' => $eventId
        ]);

        $count = $this->credentialRepository->expireByEventId($eventId);

        Log::info('[CREDENTIAL SERVICE] Credenciales expiradas', [
            'event_id' => $eventId,
            'count' => $count
        ]);

        return $count;
    }

    /**
     * Obtener estadísticas de credenciales
     */
    public function getCredentialStats(): array
    {
        return [
            'total' => $this->credentialRepository->count(),
            'pending' => $this->credentialRepository->getPending()->count(),
            'ready' => $this->credentialRepository->getValid()->count(),
            'failed' => $this->credentialRepository->getFailed()->count(),
        ];
    }

    /**
     * Aplicar template al canvas
     */
    private function applyTemplateToCanvas($canvas, Credential $credential, array $dimensions): void
    {
        $template = $credential->template_snapshot;
        
        Log::info('[CREDENTIAL SERVICE] Entrando a applyTemplateToCanvas', [
            'has_template' => !empty($template),
            'has_layout_meta' => isset($template['layout_meta']),
            'layout_meta_value' => $template['layout_meta'] ?? 'N/A',
            'target_dimensions' => $dimensions['width'] . 'x' . $dimensions['height']
        ]);
        
        if (!$template || !$template['layout_meta']) {
            Log::warning('[CREDENTIAL SERVICE] Saliendo early de applyTemplateToCanvas');
            return;
        }

        // Cargar imagen de fondo del template
        Log::info('[CREDENTIAL SERVICE] Verificando template snapshot', [
            'has_file_path' => isset($template['file_path']),
            'file_path' => $template['file_path'] ?? 'N/A'
        ]);
        
        if (isset($template['file_path'])) {
            try {
                $templatePath = Storage::disk('public')->path('templates/' . $template['file_path']);
                
                Log::info('[CREDENTIAL SERVICE] Intentando cargar template', [
                    'template_path' => $templatePath,
                    'file_exists' => file_exists($templatePath)
                ]);
                
                if (file_exists($templatePath)) {
                    $templateImage = $this->imageManager->read($templatePath);
                    
                    // Redimensionar template a las dimensiones del canvas manteniendo proporción
                    $templateImage->resize($dimensions['width'], $dimensions['height']);
                    
                    // Aplicar template como fondo
                    $canvas->place($templateImage, 'top-left', 0, 0);
                    
                    Log::info('[CREDENTIAL SERVICE] Template de fondo aplicado', [
                        'template_path' => $templatePath,
                        'final_size' => $dimensions['width'] . 'x' . $dimensions['height']
                    ]);
                } else {
                    Log::warning('[CREDENTIAL SERVICE] Archivo de template no encontrado', [
                        'template_path' => $templatePath
                    ]);
                }
            } catch (Exception $e) {
                Log::error('[CREDENTIAL SERVICE] Error aplicando template', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    // --- Rounded rectangle AA with soft shadow (GD safe) -----------------------
    private function hexToRgbaArray(string $hex, float $alpha = 1.0): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex = preg_replace('/(.)/','$1$1',$hex); }
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
        return [$r,$g,$b,max(0,min(1,$alpha))];
    }

    /**
     * Crea un layer PNG con un rectángulo redondeado ANTIALIAS + sombra suave.
     * Retorna Intervention\Image\Image listo para place().
     */
    private function makeRoundedRectLayerAA(
        int $w, int $h,
        int $radius,
        string $fill = '#FFFFFF',
        string $stroke = '#8E8E8E', int $strokeW = 6,
        bool $withShadow = true, int $shadowOffset = 12,
        int $shadowBlur = 14, string $shadowColor = 'rgba(0,0,0,0.25)'
    ) {
        $scale = 3; // supermuestreo
        $W = max(1, $w * $scale);
        $H = max(1, $h * $scale);
        $R = max(1, $radius * $scale);
        $S = max(0, $strokeW * $scale);

        // Área extra para la sombra (offset + blur)
        $pad = $withShadow ? max(0, ($shadowBlur * $scale) + ($shadowOffset * $scale)) : 0;
        $layerW = $W + $pad;
        $layerH = $H + $pad;

        $layer = $this->imageManager->create($layerW, $layerH)->fill('rgba(0,0,0,0)');

        // --- Sombra difusa ---
        if ($withShadow) {
            $shadow = $this->imageManager->create($W, $H)->fill('rgba(0,0,0,0)');
            // máscara de sombra con esquinas redondeadas
            $this->fillRoundedRect($shadow, 0, 0, $W, $H, $R, $shadowColor);
            $shadow->blur(max(1, $shadowBlur * $scale));
            $layer->place($shadow, 'top-left', $shadowOffset * $scale, $shadowOffset * $scale);
        }

        // --- Figura principal (relleno + borde) ---
        $base = $this->imageManager->create($W, $H)->fill('rgba(0,0,0,0)');
        if ($S > 0) {
            // capa de borde
            $this->fillRoundedRect($base, 0, 0, $W, $H, $R, $stroke);
            // capa de relleno (inset por stroke)
            $ix = $S; $iy = $S;
            $iw = max(1, $W - 2 * $S);
            $ih = max(1, $H - 2 * $S);
            $ir = max(0, $R - $S);
            $this->fillRoundedRect($base, $ix, $iy, $iw, $ih, $ir, $fill);
        } else {
            $this->fillRoundedRect($base, 0, 0, $W, $H, $R, $fill);
        }
        $layer->place($base, 'top-left', 0, 0);

        // Reducir con antialias al tamaño final
        $finalW = max(1, intdiv($layerW, $scale));
        $finalH = max(1, intdiv($layerH, $scale));
        $layer->resize($finalW, $finalH);

        return $layer;
    }

    /**
     * Aplicar template por defecto
     */
    private function applyDefaultTemplate($canvas, Credential $credential): void
    {
        // Template básico con colores y diseño por defecto
        $canvas->fill('#f8f9fa');
        
        // Borde
        // $canvas->drawRectangle(10, 10, config('credentials.image.width') - 10, config('credentials.image.height') - 10, function ($draw) {
        //     $draw->border(2, '#dee2e6');
        // });

        Log::info('[CREDENTIAL SERVICE] Aplicando template por defecto');
    }

    /**
     * Incrustar datos del empleado en el canvas
     */
    private function embedEmployeeData($canvas, Credential $credential, array $dimensions): void
    {
        $employee = $credential->employee_snapshot;
        $event = $credential->event_snapshot;
        $template = $credential->template_snapshot;
        
        // Obtener zonas del snapshot (inmutable) - CORREGIDO
        $zones = $credential->zones_snapshot;
        Log::info('[CREDENTIAL SERVICE] Zonas obtenidas del snapshot', [
            'zones_count' => $zones ? count($zones) : 0,
            'zones_data' => $zones
        ]);

        if (!$template || !isset($template['layout_meta']['text_blocks'])) {
            Log::warning('[CREDENTIAL SERVICE] No hay text_blocks en el template');
            return;
        }

        // CORECCIÓN: Usar coordenadas directamente sin escalado para WYSIWYG exacto
        // Las coordenadas del editor ya están en píxeles absolutos y deben usarse tal como están
        Log::info('[CREDENTIAL SERVICE] Usando coordenadas directas sin escalado', [
            'dimensions' => $dimensions['width'] . 'x' . $dimensions['height'],
            'approach' => 'WYSIWYG 1:1 coordinate mapping'
        ]);

        $textBlocks = $template['layout_meta']['text_blocks'];
        
        $backgroundBlocks = ['provider', 'proveedor'];
        foreach ($textBlocks as $block) {
            // Renderizado especial para bloque dinámico de zonas
            if ((isset($block['type']) && $block['type'] === 'zones') || ($block['id'] ?? '') === 'zones') {
                $this->renderZonesBlock($canvas, $block, $zones);
                continue;
            }

            $text = $this->getTextForBlock($block['id'], $employee, $event, $zones);
            
            if ($text) {
                // Usar coordenadas directamente del editor (sin escalado)
                $x = intval($block['x']);
                $y = intval($block['y']);
                
                // Font size: usar directamente el valor del editor (sin escalado)
                $fontSize = $block['font_size'] ?? 12;
                
                // Verificar si este bloque debe tener fondo de color

                 // Si el bloque está en la lista de fondos, aplicar fondo con el color del área (banner proveedor)
                if (in_array($block['id'], $backgroundBlocks, true)) {
                    $areaColor = $employee['provider']['area']['color'] ?? '#000000';
                    $text = preg_replace('/\s+/u', ' ', trim($text)); // normalizar espacios
                    $fontPath = public_path('fonts/arial.ttf');

                    // Medir con el mismo motor que dibuja (Imagick si está activo, fallback GD)
                    $metrics = $this->measureTextBox($text, (int) $fontSize, $fontPath);
                    $minX = $metrics['minX'];
                    $maxX = $metrics['maxX'];
                    $minY = $metrics['minY'];
                    $maxY = $metrics['maxY'];
                    $textW = $metrics['w'];
                    $textH = $metrics['h'];

                    // Padding por lado
                    $padL = $block['bg_pad_left']  ?? ($block['bg_pad_x'] ?? 12);
                    $padR = $block['bg_pad_right'] ?? ($block['bg_pad_x'] ?? 12);
                    $padY = $block['bg_pad_y']     ?? 6;

                    // Dimensiones iniciales del fondo
                    $bgW = (int) ceil($textW + $padL + $padR);
                    $bgH = (int) ceil($textH + 2 * $padY);

                    // Posicionamiento del rect según alineación y línea base
                    $align = strtolower($block['alignment'] ?? 'left');
                    if ($align === 'center') {
                        $rectX = (int) round($x - ($bgW / 2));
                    } elseif ($align === 'right') {
                        $rectX = (int) round($x - $bgW);
                    } else { // left: considerar left-bearing
                        $rectX = (int) round($x + $minX - $padL);
                    }
                    // Top del rect usando minY (arriba del texto respecto a la línea base)
                    $rectY = (int) round($y + $minY - $padY);
                    // Offset vertical opcional fino
                    if (isset($block['bg_offset_y'])) {
                        $rectY += (int) $block['bg_offset_y'];
                    }

                    // si se fuerzan dimensiones desde layout_meta (opcionales)
                    if (isset($block['bg_fixed_width']) && is_numeric($block['bg_fixed_width'])) {
                        $bgW = (int) $block['bg_fixed_width'];
                    } elseif (isset($block['bg_max_width']) && is_numeric($block['bg_max_width'])) {
                        $bgW = min($bgW, (int) $block['bg_max_width']);
                    }
                    if (isset($block['bg_height']) && is_numeric($block['bg_height'])) {
                        $bgH = (int) $block['bg_height'];
                    }

                    // Auto-fit: si el fondo se desborda por la derecha del lienzo y NO hay ancho fijo, reducir fontSize
                    $hasFixedWidth = isset($block['bg_fixed_width']) && is_numeric($block['bg_fixed_width']);
                    if (!$hasFixedWidth) {
                        // Restricción por borde derecho del lienzo
                        $availableRight = isset($dimensions['width']) ? (int) $dimensions['width'] - $rectX : $bgW;
                        $rightConstraint = ($availableRight > 0 && $bgW > $availableRight)
                            ? max(1, $availableRight - ($padL + $padR))
                            : null;

                        // Restricción por bg_max_width (si definió un máximo menor que el ancho actual del texto)
                        $maxWidthConstraint = null;
                        if (isset($block['bg_max_width']) && is_numeric($block['bg_max_width'])) {
                            $maxAllowedBgW = (int) $block['bg_max_width'];
                            if ($maxAllowedBgW < (int) round($textW + $padL + $padR)) {
                                $maxWidthConstraint = max(1, $maxAllowedBgW - ($padL + $padR));
                            }
                        }

                        // Determinar el objetivo más estricto si aplica
                        $targetTextW = null;
                        if ($rightConstraint !== null && $maxWidthConstraint !== null) {
                            $targetTextW = min($rightConstraint, $maxWidthConstraint);
                        } elseif ($rightConstraint !== null) {
                            $targetTextW = $rightConstraint;
                        } elseif ($maxWidthConstraint !== null) {
                            $targetTextW = $maxWidthConstraint;
                        }

                        if ($targetTextW !== null && $targetTextW < $textW) {
                            $origFontSize = $fontSize;
                            $fontSize = $this->findMaxFontSize($text, $fontPath, (int) $targetTextW, $textH + 100);

                            // Recalcular bbox y dimensiones con el nuevo fontSize
                            $metrics = $this->measureTextBox($text, (int) $fontSize, $fontPath);
                            $minX = $metrics['minX'];
                            $maxX = $metrics['maxX'];
                            $minY = $metrics['minY'];
                            $maxY = $metrics['maxY'];
                            $textW = $metrics['w'];
                            $textH = $metrics['h'];
                            $bgW = (int) ceil($textW + $padL + $padR);
                            $bgH = (int) ceil($textH + 2 * $padY);

                            Log::debug('[CREDENTIAL SERVICE] Provider banner auto-fit applied', [
                                'rightConstraint' => $rightConstraint,
                                'maxWidthConstraint' => $maxWidthConstraint,
                                'targetTextW' => $targetTextW,
                                'origFontSize' => $origFontSize,
                                'newFontSize' => $fontSize,
                                'newBgW' => $bgW,
                                'rectX' => $rectX,
                                'canvasW' => $dimensions['width'] ?? null,
                            ]);
                        }
                    } else {
                        if ((isset($dimensions['width']) ? (int) $dimensions['width'] - $rectX : $bgW) < $bgW) {
                            Log::debug('[CREDENTIAL SERVICE] Provider banner overflow with fixed width', [
                                'bg_fixed_width' => $block['bg_fixed_width'],
                                'rectX' => $rectX,
                                'bgW' => $bgW,
                                'canvasW' => $dimensions['width'] ?? null,
                            ]);
                        }
                    }

                    // Re-evaluar rect tras posibles cambios de tamaño de fuente/bbox
                    if ($align === 'center') {
                        $rectX = (int) round($x - ($bgW / 2));
                    } elseif ($align === 'right') {
                        $rectX = (int) round($x - $bgW);
                    } else {
                        $rectX = (int) round($x + $minX - $padL);
                    }
                    $rectY = (int) round($y + $minY - $padY);
                    if (isset($block['bg_offset_y'])) {
                        $rectY += (int) $block['bg_offset_y'];
                    }

                    // Fondo con esquinas redondeadas AA (sin sombra)
                    $radius = $block['bg_radius'] ?? 8;
                    $bgLayer = $this->makeRoundedRectLayerAA(
                        $bgW, $bgH,
                        $radius,
                        $areaColor, $areaColor, 0,
                        false, 0, 0, 'rgba(0,0,0,0)'
                    );
                    $canvas->place($bgLayer, 'top-left', $rectX, $rectY);

                    // Dibujar el texto anclado al rectángulo de fondo (baseline)
                    $drawX = (int) round($rectX + $padL - $minX);
                    $drawY = (int) round($rectY + $padY - $minY); // baseline alineada al interior del rect

                    // Métricas de depuración para validar el ajuste del fondo al texto
                    Log::debug('[CREDENTIAL SERVICE] Provider banner metrics', [
                        'align' => $align,
                        'minX' => $minX, 'maxX' => $maxX,
                        'minY' => $minY, 'maxY' => $maxY,
                        'textW' => $textW, 'textH' => $textH,
                        'rectX' => $rectX, 'rectY' => $rectY,
                        'drawX' => $drawX, 'drawY' => $drawY,
                        'bgW' => $bgW, 'bgH' => $bgH,
                        'padL' => $padL, 'padR' => $padR, 'padY' => $padY,
                        'color' => $areaColor,
                    ]);
                    $canvas->text($text, $drawX, $drawY, function ($font) use ($fontSize, $fontPath) {
                        $font->file($fontPath);
                        $font->size($fontSize);
                        $font->color('#FFFFFF');
                        $font->align('left'); // evitar center/right para no desfasar bbox
                    });
                } else {
                    // Para otros bloques, usar configuración normal con auto-ajuste opcional
                    $fontPath = public_path('fonts/arial.ttf'); // TTF válido REQUERIDO

                    // Auto-fit para: nombre, federacion, rol/position/function
                    // Usa el rectángulo (width/height) del bloque si está definido
                    $blockId = $block['id'] ?? null;
                    // IDs soportados para auto-fit (solo ancho)
                    $autoFitIds = [
                        'nombre',
                        'federacion', 'federation',
                        'rol', 'role',
                        'proveedor', 'provider',
                        'position', 'function',
                    ];
                    $shouldAutoFit = $blockId && in_array($blockId, $autoFitIds, true);
                    if ($shouldAutoFit && isset($block['width'], $block['height'])
                        && is_numeric($block['width']) && is_numeric($block['height'])
                    ) {
                        $allowedW = max(1, (int) $block['width']);
                        $allowedH = max(1, (int) $block['height']);

                        // Medir con tamaño actual y solo reducir si EXCEDE el ANCHO permitido
                        // (ignoramos la altura para no reducir nombres cortos innecesariamente)
                        $metrics = $this->measureTextBox($text, (int) $fontSize, $fontPath);
                        if ($metrics['w'] > $allowedW) {
                            $origFontSize = (int) $fontSize;
                            // Limitar por ancho; altura muy grande para que no sea restrictiva
                            $computed = $this->findMaxFontSize($text, $fontPath, $allowedW, 10000);
                            // Ajustar exactamente al tamaño que cabe (sin clamp) para evitar desbordes
                            $fontSize = (int) $computed;

                            Log::debug('[CREDENTIAL SERVICE] Auto-fit aplicado (width-only)', [
                                'block_id' => $blockId,
                                'origFontSize' => $origFontSize,
                                'newFontSize' => $fontSize,
                                'allowedW' => $allowedW,
                                'allowedH' => $allowedH,
                                'measuredW' => $metrics['w'],
                                'measuredH' => $metrics['h'],
                            ]);
                        }
                    }

                    $canvas->text($text, $x, $y, function ($font) use ($fontSize, $block) {
                        $font->file(public_path('fonts/arial.ttf')); // TTF válido REQUERIDO
                        $font->size($fontSize);
                        $font->color('#000000');
                        $font->align($block['alignment'] ?? 'left');
                    });
                }
                
                // Texto aplicado
            }
        }
        
        Log::info('[CREDENTIAL SERVICE] Datos del empleado incrustados');
    }

    /**
     * Renderizar bloque dinámico de zonas dentro de un rectángulo
     */
    private function renderZonesBlock($canvas, array $block, ?array $zones): void
    {
        try {
            $rectX = isset($block['x']) ? intval($block['x']) : 0;
            $rectY = isset($block['y']) ? intval($block['y']) : 0;
            $rectW = isset($block['width']) ? intval($block['width']) : 0;
            $rectH = isset($block['height']) ? intval($block['height']) : 0;

            if ($rectW <= 0 || $rectH <= 0) {
                Log::warning('[CREDENTIAL SERVICE] Zones block con dimensiones inválidas', [
                    'block' => $block
                ]);
                return;
            }

            // Preparar datos de zonas
            $zoneNumbers = [];
            if ($zones && is_array($zones)) {
                foreach ($zones as $z) {
                    if (isset($z['id'])) {
                        $zoneNumbers[] = intval($z['id']);
                    }
                }
            }

            if (empty($zoneNumbers)) {
                Log::info('[CREDENTIAL SERVICE] Sin zonas aprobadas para renderizar en zones block');
                return;
            }

            sort($zoneNumbers); // Orden ascendente para consistencia visual

            $padding = isset($block['padding']) ? intval($block['padding']) : 8;
            $gap = isset($block['gap']) ? intval($block['gap']) : 10;
            $fontFileName = isset($block['font_family']) ? $block['font_family'] : 'arial.ttf';
            $fontColor = isset($block['font_color']) ? $block['font_color'] : '#000000';

            $fontPath = public_path('fonts/' . $fontFileName);
            if (!file_exists($fontPath)) {
                Log::warning('[CREDENTIAL SERVICE] Font no encontrada para zones, usando arial.ttf por defecto', [
                    'requested' => $fontFileName
                ]);
                $fallback = public_path('fonts/arial.ttf');
                if (file_exists($fallback)) {
                    $fontPath = $fallback;
                } else {
                    Log::error('[CREDENTIAL SERVICE] No se encontró archivo de fuente arial.ttf en public/fonts');
                    return;
                }
            }

        $innerW = max(0, $rectW - 2 * $padding);
        $innerH = max(0, $rectH - 2 * $padding);
        
        // Parámetros de caja y estilos (overrides desde layout_meta)
        $boxMargin    = max(4, intval($gap / 3)); // margen dentro de la celda
        $numPadding   = 6; // padding interno para cálculo de fuente
        $borderWidth  = isset($block['border_width']) ? intval($block['border_width']) : 4;   // borde más fino
        $boxFill      = $block['fill'] ?? '#FFFFFF';
        $boxBorder    = $block['border_color'] ?? '#000000';                                   // negro
        $cornerRadius = isset($block['corner_radius']) ? intval($block['corner_radius']) : 20; // radio más sobrio
        $shadowOn     = isset($block['shadow']) ? (bool) $block['shadow'] : false;             // SIN sombra por defecto
        $shadowOffset = 0;
        $shadowColor  = 'rgba(0,0,0,0)';                                                       // sin sombra
        $aspect       = isset($block['aspect']) ? floatval($block['aspect']) : 1.35; // relación alto/ancho

        // Datos base
        $n = count($zoneNumbers);
        $gutterX = $gap; // separación horizontal constante
        $gutterY = $gap; // separación vertical constante

        // Modo AMPLIFICADO para 1–3 zonas (una fila, cajas grandes)
        if ($n <= 3) {
            $cols = $n;
            $rows = 1;

            $boxW = ($cols > 0)
                ? intval(floor(($innerW - ($cols - 1) * $gutterX) / $cols))
                : 0;
            $boxW = max(1, $boxW);
            $boxH = isset($block['box_height'])
                ? intval(min($innerH, intval($block['box_height'])))
                : intval(min($innerH, round($boxW * $aspect)));
            $boxH = max(1, $boxH);

            $contentW = $cols * $boxW + ($cols - 1) * $gutterX;
            $startX   = $rectX + $padding + intval(floor(($innerW - $contentW) / 2));
            $startY   = $rectY + $padding + intval(floor(($innerH - $boxH) / 2));

            Log::info('[CREDENTIAL SERVICE] Renderizando zones block (MODO AMPLIFICADO N<=3)', [
                'rect' => compact('rectX', 'rectY', 'rectW', 'rectH'),
                'inner' => ['width' => $innerW, 'height' => $innerH],
                'cols' => $cols,
                'boxW' => $boxW,
                'boxH' => $boxH,
                'zones' => $zoneNumbers
            ]);

            // --- Ajustes especiales cuando hay UNA sola zona ---
            $single = ($n === 1);

            // Acolchado casi nulo para que el texto mida el cuadro
            $boxMargin  = $single ? 0 : max(4, intval($gap / 3));
            $numPadding = $single ? 0 : 6;

            // Límites reales para el texto (dejamos casi todo el espacio)
            $allowedTextW = max(1, ($boxW - 2 * $boxMargin) - 2 * $numPadding);
            $allowedTextH = max(1, ($boxH - 2 * $boxMargin) - 2 * $numPadding);

            // Tamaño de fuente uniforme
            $uniformFontSize = 400;
            foreach ($zoneNumbers as $zn) {
                $candidate = $this->findMaxFontSize((string) $zn, $fontPath, $allowedTextW, $allowedTextH);
                $uniformFontSize = min($uniformFontSize, $candidate);
            }
            $uniformFontSize = max(1, $uniformFontSize);

            for ($i = 0; $i < $cols; $i++) {
                $idx = $i;
                if (!isset($zoneNumbers[$idx])) { continue; }
                $text = strval($zoneNumbers[$idx]);

                $x = $startX + $i * ($boxW + $gutterX);
                $boxLeft = $x + $boxMargin;
                $boxTop  = $startY + $boxMargin;
                $drawW   = max(1, $boxW - 2 * $boxMargin);
                $drawH   = max(1, $boxH - 2 * $boxMargin);

                // Ajustes especiales cuando hay UNA sola zona: usar tamaño objetivo como si fueran 2 columnas
                if ($single) {
                    // 1) Calcula dimensiones objetivo como si fueran 2 columnas
                    $targetCols = 2;
                    $targetBoxW = ($targetCols > 0)
                        ? intval(floor(($innerW - ($targetCols - 1) * $gutterX) / $targetCols))
                        : $drawW; // fallback
                    $targetBoxW = max(1, $targetBoxW);

                    $targetBoxH = isset($block['box_height'])
                        ? intval(min($innerH, intval($block['box_height'])))
                        : intval(min($innerH, round($targetBoxW * $aspect)));
                    $targetBoxH = max(1, $targetBoxH);

                    // 2) Re-centrar la caja con ese tamaño objetivo
                    $boxLeft += intdiv($drawW - $targetBoxW, 2);
                    $boxTop  += intdiv($drawH - $targetBoxH, 2);
                    $drawW    = $targetBoxW;
                    $drawH    = $targetBoxH;

                    // 3) Recalcular límites de texto con márgenes/padding
                    $allowedTextW = max(1, ($drawW - 2 * $boxMargin) - 2 * $numPadding);
                    $allowedTextH = max(1, ($drawH - 2 * $boxMargin) - 2 * $numPadding);

                    // 4) Tamaño de fuente máximo que llene bien este nuevo rectángulo
                    $uniformFontSize = $this->findMaxFontSize($text, $fontPath, $allowedTextW, $allowedTextH);
                    $uniformFontSize = max(1, $uniformFontSize);
                }

                // Caja AA supersample sin sombra. Ajustes para 1 zona.
                $radiusPx = $single
                    ? intval(max(14, round($drawH * 0.14)))
                    : (isset($block['corner_radius']) ? intval($block['corner_radius']) : intval(round($drawH * 0.22)));
                $strokePx = $single ? 4 : $borderWidth;
                $shadowBlur = 0;
                $offsetAA   = 0;

                $boxLayer = $this->makeRoundedRectLayerAA(
                    $drawW, $drawH,
                    $radiusPx,
                    $boxFill, $boxBorder, $strokePx,
                    false, 0, 0, 'rgba(0,0,0,0)'
                );
                $canvas->place($boxLayer, 'top-left', $boxLeft, $boxTop);

                // Texto centrado
                $textX = intval($boxLeft + ($drawW / 2));
                $textY = intval($boxTop + ($drawH / 2));
                $canvas->text($text, $textX, $textY, function ($font) use ($uniformFontSize, $fontPath, $fontColor) {
                    $font->file($fontPath);
                    $font->size($uniformFontSize);
                    $font->color($fontColor);
                    $font->align('center');
                    $font->valign('middle');
                });
            }

            return; // no continuar con el layout de 5 columnas
        }

        // --- Layout balanceado especial: 6 -> 3+3, 8 -> 4+4 -----------------------
        if ($n === 6 || $n === 8) {
            $cols = intdiv($n, 2);   // 3 ó 4
            $rows = 2;

            // ancho/alto de cada caja en función del ancho disponible
            $unitBoxW = ($cols > 0)
                ? intval(floor(($innerW - ($cols - 1) * $gutterX) / $cols))
                : 0;
            $unitBoxW = max(1, $unitBoxW);

            // usa box_height si viene en el template; si no, deriva por aspecto
            $unitBoxH = isset($block['box_height'])
                ? intval($block['box_height'])
                : intval(round($unitBoxW * $aspect));
            $unitBoxH = max(1, $unitBoxH);

            // quepan 2 filas centradas verticalmente
            $totalH = $rows * $unitBoxH + ($rows - 1) * $gutterY;
            if ($totalH > $innerH) {
                $unitBoxH = intval(floor(($innerH - ($rows - 1) * $gutterY) / $rows));
                $unitBoxH = max(1, $unitBoxH);
                $totalH   = $rows * $unitBoxH + ($rows - 1) * $gutterY;
            }
            $startY = $rectY + $padding + intval(floor(($innerH - $totalH) / 2));

            // límites de texto (márgenes/padding actuales)
            $allowedTextW = max(1, ($unitBoxW - 2 * $boxMargin) - 2 * $numPadding);
            $allowedTextH = max(1, ($unitBoxH - 2 * $boxMargin) - 2 * $numPadding);

            // font-size uniforme para todos los números
            $uniformFontSize = 400;
            foreach ($zoneNumbers as $zn) {
                $candidate = $this->findMaxFontSize((string) $zn, $fontPath, $allowedTextW, $allowedTextH);
                $uniformFontSize = min($uniformFontSize, $candidate);
            }
            $uniformFontSize = max(1, $uniformFontSize);

            // render de 2 filas idénticas centradas
            for ($r = 0; $r < $rows; $r++) {
                $itemsInRow = $cols; // 3 o 4 fijos
                $contentW   = $itemsInRow * $unitBoxW + ($itemsInRow - 1) * $gutterX;
                $startX     = $rectX + $padding + intval(floor(($innerW - $contentW) / 2));
                $top        = $startY + $r * ($unitBoxH + $gutterY);

                for ($i = 0; $i < $itemsInRow; $i++) {
                    $idx = $r * $cols + $i;
                    if (!isset($zoneNumbers[$idx])) { continue; }
                    $text = strval($zoneNumbers[$idx]);

                    $x = $startX + $i * ($unitBoxW + $gutterX);
                    $boxLeft = $x + $boxMargin;
                    $boxTop  = $top + $boxMargin;
                    $drawW   = max(1, $unitBoxW - 2 * $boxMargin);
                    $drawH   = max(1, $unitBoxH - 2 * $boxMargin);

                    // caja redondeada (misma estética que el layout estándar)
                    $radiusPx   = isset($block['corner_radius']) ? intval($block['corner_radius']) : intval(round($drawH * 0.18));
                    $strokePx   = $borderWidth;
                    $shadowBlur = 0;
                    $offsetAA   = 0;

                    $boxLayer = $this->makeRoundedRectLayerAA(
                        $drawW, $drawH,
                        $radiusPx,
                        $boxFill, $boxBorder, $strokePx,
                        $shadowOn, $offsetAA, $shadowBlur, $shadowColor
                    );
                    $canvas->place($boxLayer, 'top-left', $boxLeft, $boxTop);

                    // texto centrado
                    $textX = intval($boxLeft + ($drawW / 2));
                    $textY = intval($boxTop  + ($drawH / 2));
                    $canvas->text($text, $textX, $textY, function ($font) use ($uniformFontSize, $fontPath, $fontColor) {
                        $font->file($fontPath);
                        $font->size($uniformFontSize);
                        $font->color($fontColor);
                        $font->align('center');
                        $font->valign('middle');
                    });
                }
            }

            return; // evita que caiga al layout de 5 por fila
        }

        // ---- Layout estándar (máx 5 por fila, ancho de unidad fijo) ----
        $unitCols = 5;
        $rows = ($n > 0) ? (int) ceil($n / $unitCols) : 0;

        // Ancho/alto de caja por unidad
        $unitBoxW = ($unitCols > 0)
            ? intval(floor(($innerW - ($unitCols - 1) * $gutterX) / $unitCols))
            : 0;
        $unitBoxW = max(1, $unitBoxW);
        $unitBoxH = isset($block['box_height'])
            ? intval($block['box_height'])
            : intval(round($unitBoxW * 1.4));
        $unitBoxH = max(1, $unitBoxH);

        // Ajuste para que todas las filas quepan dentro del bloque y centrado vertical
        $totalH = ($rows > 0) ? ($rows * $unitBoxH + ($rows - 1) * $gutterY) : 0;
        if ($totalH > $innerH && $rows > 0) {
            $unitBoxH = intval(floor(($innerH - ($rows - 1) * $gutterY) / $rows));
            $unitBoxH = max(1, $unitBoxH);
            $totalH = $rows * $unitBoxH + ($rows - 1) * $gutterY;
        }
        $startY = $rectY + $padding + intval(floor(($innerH - $totalH) / 2));

        Log::info('[CREDENTIAL SERVICE] Renderizando zones block (filas máx 5, ancho unidad y centrado por fila)', [
            'rect' => compact('rectX', 'rectY', 'rectW', 'rectH'),
            'inner' => ['width' => $innerW, 'height' => $innerH],
            'rows' => $rows,
            'unitBoxW' => $unitBoxW,
            'unitBoxH' => $unitBoxH,
            'zones' => $zoneNumbers
        ]);

        // Límites de texto dentro de la caja de unidad
        $allowedTextW = max(1, ($unitBoxW - 2 * $boxMargin) - 2 * $numPadding);
        $allowedTextH = max(1, ($unitBoxH - 2 * $boxMargin) - 2 * $numPadding);

        // Tamaño de fuente uniforme
        $uniformFontSize = 400;
        foreach ($zoneNumbers as $zn) {
            $candidate = $this->findMaxFontSize((string) $zn, $fontPath, $allowedTextW, $allowedTextH);
            $uniformFontSize = min($uniformFontSize, $candidate);
        }
        $uniformFontSize = max(1, $uniformFontSize);

        // Renderizado por filas (máx 5 por fila), centrado por fila
        for ($r = 0; $r < $rows; $r++) {
            $itemsInRow = min($unitCols, max(0, $n - $r * $unitCols));
            if ($itemsInRow <= 0) { continue; }

            $contentW = $itemsInRow * $unitBoxW + ($itemsInRow - 1) * $gutterX;
            $startX   = $rectX + $padding + intval(floor(($innerW - $contentW) / 2));
            $top      = $startY + $r * ($unitBoxH + $gutterY);

            for ($i = 0; $i < $itemsInRow; $i++) {
                $idx = $r * $unitCols + $i;
                if (!isset($zoneNumbers[$idx])) { continue; }
                $text = strval($zoneNumbers[$idx]);

                $x = $startX + $i * ($unitBoxW + $gutterX);
                $boxLeft = $x + $boxMargin;
                $boxTop  = $top + $boxMargin;
                $drawW   = max(1, $unitBoxW - 2 * $boxMargin);
                $drawH   = max(1, $unitBoxH - 2 * $boxMargin);

                // Caja AA supersample + sombra suave
                $radiusPx   = isset($block['corner_radius']) ? intval($block['corner_radius']) : intval(round($drawH * 0.18));
                $strokePx   = $borderWidth;
                $shadowBlur = 0;
                $offsetAA   = 0;

                $boxLayer = $this->makeRoundedRectLayerAA(
                    $drawW, $drawH,
                    $radiusPx,
                    $boxFill, $boxBorder, $strokePx,
                    $shadowOn, $offsetAA, $shadowBlur, $shadowColor
                );
                $canvas->place($boxLayer, 'top-left', $boxLeft, $boxTop);

                // Texto centrado
                $textX = intval($boxLeft + ($drawW / 2));
                $textY = intval($boxTop + ($drawH / 2));
                $canvas->text($text, $textX, $textY, function ($font) use ($uniformFontSize, $fontPath, $fontColor) {
                    $font->file($fontPath);
                    $font->size($uniformFontSize);
                    $font->color($fontColor);
                    $font->align('center');
                    $font->valign('middle');
                });
            }
        }
    } catch (\Throwable $e) {
        Log::error('[CREDENTIAL SERVICE] Error renderizando zones block', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

    /**
     * Calcular el layout (rows, cols) óptimo para N elementos dentro de un área
     */
    private function calculateGridLayout(int $n, int $width, int $height, int $gap): array
    {
        if ($n <= 0 || $width <= 0 || $height <= 0) {
            return [0, 0];
        }

        $best = [1, $n];
        $bestScore = -1.0;
        $minWaste = PHP_INT_MAX;

        for ($rows = 1; $rows <= $n; $rows++) {
            $cols = (int) ceil($n / $rows);
            // Dimensiones de celda con este layout
            $cellW = ($cols > 0) ? ($width - ($cols - 1) * $gap) / $cols : 0;
            $cellH = ($rows > 0) ? ($height - ($rows - 1) * $gap) / $rows : 0;
            if ($cellW <= 0 || $cellH <= 0) {
                continue;
            }

            $waste = $rows * $cols - $n;
            $score = min($cellW, $cellH); // preferir celdas más grandes y cuadradas

            if ($waste < $minWaste || ($waste === $minWaste && $score > $bestScore)) {
                $minWaste = $waste;
                $bestScore = $score;
                $best = [$rows, $cols];
            }
        }

        return $best;
    }

    /**
     * Medir ancho/alto y bbox relativo a la línea base usando el mismo motor de render.
     * Si Imagick está disponible, usa queryFontMetrics; si no, fallback a GD (imagettfbbox).
     * Retorna: [minX, maxX, minY, maxY, w, h]
     */
    private function measureTextBox(string $text, int $size, string $fontPath): array
    {
        if (extension_loaded('imagick')) {
            try {
                $draw = new \ImagickDraw();
                $draw->setFont($fontPath);
                $draw->setFontSize($size);
                $im = new \Imagick();
                $m = $im->queryFontMetrics($draw, $text);
                $asc = isset($m['ascender']) ? (float) $m['ascender'] : 0.0;   // > 0
                $desc = isset($m['descender']) ? (float) $m['descender'] : 0.0; // < 0
                $w = (int) ceil($m['textWidth'] ?? 0);
                $h = (int) ceil($asc - $desc);
                return [
                    'minX' => 0,
                    'maxX' => $w,
                    'minY' => -$asc,
                    'maxY' => $desc,
                    'w' => $w,
                    'h' => $h,
                ];
            } catch (\Throwable $e) {
                // Fallback a GD si falla Imagick por cualquier motivo
            }
        }

        // Fallback GD
        $bbox = imagettfbbox($size, 0, $fontPath, $text);
        $xs = [$bbox[0], $bbox[2], $bbox[4], $bbox[6]];
        $ys = [$bbox[1], $bbox[3], $bbox[5], $bbox[7]];
        $minX = min($xs);
        $maxX = max($xs);
        $minY = min($ys);
        $maxY = max($ys);
        return [
            'minX' => $minX,
            'maxX' => $maxX,
            'minY' => $minY,
            'maxY' => $maxY,
            'w' => $maxX - $minX,
            'h' => $maxY - $minY,
        ];
    }

    /**
     * Encontrar tamaño máximo de fuente que quepa en el área dada
     */
    private function findMaxFontSize(string $text, string $fontPath, int $maxWidth, int $maxHeight): int
    {
        $low = 1;
        $high = max(10, min($maxWidth, $maxHeight));
        $best = 1;

        // Intento rápido de aumentar high si cabe
        while ($this->textFits($text, $fontPath, $high, $maxWidth, $maxHeight) && $high < 400) {
            $best = $high;
            $high *= 2;
        }

        // Búsqueda binaria
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            if ($this->textFits($text, $fontPath, $mid, $maxWidth, $maxHeight)) {
                $best = $mid;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return max(1, $best - 1); // pequeño margen para evitar cortes
    }

    private function textFits(string $text, string $fontPath, int $fontSize, int $maxWidth, int $maxHeight): bool
    {
        $m = $this->measureTextBox($text, $fontSize, $fontPath);
        return $m['w'] <= $maxWidth && $m['h'] <= $maxHeight;
    }

    /**
     * Dibuja un rectángulo redondeado con borde opcional y sombra (compatible con GD).
     */
    private function drawRoundedRect($canvas, int $x, int $y, int $w, int $h, int $radius,
        string $fill = '#FFFFFF', ?string $border = null, int $borderWidth = 0,
        bool $shadow = false, int $shadowOffset = 0, string $shadowColor = 'rgba(0,0,0,0.14)'): void
    {
        $r = max(0, min($radius, intdiv(min($w, $h), 2)));

        // Sombra
        if ($shadow && $shadowOffset > 0) {
            $this->fillRoundedRect($canvas, $x + $shadowOffset, $y + $shadowOffset, $w, $h, $r, $shadowColor);
        }

        if ($border && $borderWidth > 0) {
            // Capa de borde
            $this->fillRoundedRect($canvas, $x, $y, $w, $h, $r, $border);

            // Capa de relleno (inset)
            $ix = $x + $borderWidth;
            $iy = $y + $borderWidth;
            $iw = max(1, $w - 2 * $borderWidth);
            $ih = max(1, $h - 2 * $borderWidth);
            $ir = max(0, $r - $borderWidth);
            $this->fillRoundedRect($canvas, $ix, $iy, $iw, $ih, $ir, $fill);
        } else {
            // Solo relleno
            $this->fillRoundedRect($canvas, $x, $y, $w, $h, $r, $fill);
        }
    }

    /**
     * Rellena un rectángulo redondeado (4 círculos + 3 rectángulos).
     */
    private function fillRoundedRect($canvas, int $x, int $y, int $w, int $h, int $r, string $color): void
    {
        // Centro
        $canvas->drawRectangle($x + $r, $y, function ($rect) use ($w, $h, $r, $color) {
            $rect->size(max(1, $w - 2 * $r), $h);
            $rect->background($color);
        });
        // Laterales
        if ($r > 0) {
            $canvas->drawRectangle($x, $y + $r, function ($rect) use ($r, $h, $color) {
                $rect->size($r, max(1, $h - 2 * $r));
                $rect->background($color);
            });
            $canvas->drawRectangle($x + $w - $r, $y + $r, function ($rect) use ($r, $h, $color) {
                $rect->size($r, max(1, $h - 2 * $r));
                $rect->background($color);
            });
            // Esquinas (círculos)
            $d = $r * 2;
            $circles = [
                [$x + $r,       $y + $r      ], // TL
                [$x + $w - $r,  $y + $r      ], // TR
                [$x + $r,       $y + $h - $r ], // BL
                [$x + $w - $r,  $y + $h - $r ], // BR
            ];
            foreach ($circles as [$cx, $cy]) {
                $canvas->drawEllipse($cx, $cy, function ($el) use ($d, $color) {
                    $el->size($d, $d);
                    $el->background($color);
                });
            }
        }
    }

    /**
     * Incrustar foto del empleado en el canvas
     */
    private function embedEmployeePhoto($canvas, Credential $credential, array $dimensions): void
    {
        $employee = $credential->employee_snapshot;
        $template = $credential->template_snapshot;

        if (!$template || !isset($template['layout_meta']['rect_photo'])) {
            Log::info('[CREDENTIAL SERVICE] No hay rect_photo en el template, saltando foto');
            return;
        }

        if (empty($employee['photo_path'])) {
            Log::info('[CREDENTIAL SERVICE] Empleado no tiene foto, saltando');
            return;
        }

        try {
            $photoRect = $template['layout_meta']['rect_photo'];
            $photoPath = Storage::disk('public')->path($employee['photo_path']);
            
            // Calcular factores de escala
            $originalWidth = $dimensions['original_width'] ?? $dimensions['width'];
            $originalHeight = $dimensions['original_height'] ?? $dimensions['height'];
            $scaleX = $dimensions['width'] / $originalWidth;
            $scaleY = $dimensions['height'] / $originalHeight;
            
            // Escalar posición y tamaño de la foto
            $scaledX = intval($photoRect['x'] * $scaleX);
            $scaledY = intval($photoRect['y'] * $scaleY);
            $scaledWidth = intval($photoRect['width'] * $scaleX);
            $scaledHeight = intval($photoRect['height'] * $scaleY);
            
            Log::info('[CREDENTIAL SERVICE] Intentando cargar foto del empleado', [
                'photo_path' => $photoPath,
                'file_exists' => file_exists($photoPath),
                'original_rect' => $photoRect,
                'scaled_rect' => ['x' => $scaledX, 'y' => $scaledY, 'width' => $scaledWidth, 'height' => $scaledHeight]
            ]);
            
            if (file_exists($photoPath)) {
                $photoImage = $this->imageManager->read($photoPath);
                
                // Redimensionar foto al tamaño escalado
                $photoImage->resize($scaledWidth, $scaledHeight);
                
                // Colocar foto en las coordenadas escaladas
                $canvas->place($photoImage, 'top-left', $scaledX, $scaledY);
                
                Log::info('[CREDENTIAL SERVICE] Foto del empleado incrustada exitosamente', [
                    'position' => ['x' => $scaledX, 'y' => $scaledY],
                    'size' => ['width' => $scaledWidth, 'height' => $scaledHeight]
                ]);
            } else {
                Log::warning('[CREDENTIAL SERVICE] Archivo de foto no encontrado', [
                    'photo_path' => $photoPath
                ]);
            }
        } catch (Exception $e) {
            Log::error('[CREDENTIAL SERVICE] Error incrustando foto del empleado', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Incrustar código QR en el canvas
     */
    private function embedQRCode($canvas, Credential $credential, array $dimensions): void
    {
        if (!$credential->qr_image_path) {
            return;
        }

        try {
            $qrImagePath = Storage::disk('public')->path($credential->qr_image_path);
            
            if (file_exists($qrImagePath)) {
                $qrImage = $this->imageManager->read($qrImagePath);
                
                // Calcular factores de escala
                $originalWidth = $dimensions['original_width'] ?? $dimensions['width'];
                $originalHeight = $dimensions['original_height'] ?? $dimensions['height'];
                $scaleX = $dimensions['width'] / $originalWidth;
                $scaleY = $dimensions['height'] / $originalHeight;
                
                // Obtener coordenadas del template si existe
                $template = $credential->template_snapshot;
                if ($template && isset($template['layout_meta']['rect_qr'])) {
                    $qrRect = $template['layout_meta']['rect_qr'];
                    // Escalar coordenadas y tamaño del QR
                    $x = intval($qrRect['x'] * $scaleX);
                    $y = intval($qrRect['y'] * $scaleY);
                    $qrSize = intval($qrRect['width'] * min($scaleX, $scaleY));
                } else {
                    // Posición por defecto en esquina inferior derecha (escalada)
                    $qrSize = intval(150 * min($scaleX, $scaleY));
                    $margin = intval(20 * min($scaleX, $scaleY));
                    $x = $dimensions['width'] - $qrSize - $margin;
                    $y = $dimensions['height'] - $qrSize - $margin;
                }
                
                $qrImage->resize($qrSize, $qrSize);
                $canvas->place($qrImage, 'top-left', $x, $y);
                
                Log::info('[CREDENTIAL SERVICE] QR code incrustado exitosamente', [
                    'position' => ['x' => $x, 'y' => $y, 'size' => $qrSize]
                ]);
            }
        } catch (Exception $e) {
            Log::warning('[CREDENTIAL SERVICE] Error incrustando QR', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener texto para un bloque específico
     */
    private function getTextForBlock(string $blockId, array $employee, array $event, ?array $zones = null): ?string
    {
        switch ($blockId) {
            case 'nombre':
                return mb_strtoupper(trim($employee['first_name'] . ' ' . $employee['last_name']));
                
            case 'federacion':
                // Solo mostrar para proveedores internos
                if (isset($employee['provider']) && 
                    isset($employee['provider']['type']) && 
                    strtolower($employee['provider']['type']) === 'internal') {
                    return mb_strtoupper('FEDERACION VENEZOLANA DE FUTBOL');
                }
                return '';
            
            case 'rol':
            case 'position':
            case 'function':
                return mb_strtoupper($employee['function'] ?? '');
            
            case 'company':
            case 'empresa':
                return mb_strtoupper($employee['company'] ?? '');
            
            case 'identification':
            case 'document':
                return mb_strtoupper(trim(($employee['document_type'] ?? '') . ' ' . ($employee['document_number'] ?? '')));
            
            case 'event':
            case 'evento':
                return mb_strtoupper($event['name'] ?? '');
            
            case 'location':
            case 'lugar':
                return mb_strtoupper($event['location'] ?? '');
            
            case 'zona':
            case 'zonas':
            case 'zones':
                if (!$zones || empty($zones)) {
                    return mb_strtoupper('Todas las zonas');
                }
                
                // Obtener solo los nombres de las zonas
                $zoneNames = array_map(function($name) {
                    return mb_strtoupper($name);
                }, array_column($zones, 'name'));
                
                // Limitar a 3 zonas para no saturar la credencial
                if (count($zoneNames) > 3) {
                    $zoneNames = array_slice($zoneNames, 0, 3);
                    return implode(", ", $zoneNames) . " Y " . (count($zones) - 3) . " MÁS";
                }
                
                return implode(", ", $zoneNames);
            
            // Zonas individuales (zona1-zona9)
            case 'zona1':
                return mb_strtoupper($this->getZoneNumber(1, $zones));
            case 'zona2':
                return mb_strtoupper($this->getZoneNumber(2, $zones));
            case 'zona3':
                return mb_strtoupper($this->getZoneNumber(3, $zones));
            case 'zona4':
                return mb_strtoupper($this->getZoneNumber(4, $zones));
            case 'zona5':
                return mb_strtoupper($this->getZoneNumber(5, $zones));
            case 'zona6':
                return mb_strtoupper($this->getZoneNumber(6, $zones));
            case 'zona7':
                return mb_strtoupper($this->getZoneNumber(7, $zones));
            case 'zona8':
                return mb_strtoupper($this->getZoneNumber(8, $zones));
            case 'zona9':
                return mb_strtoupper($this->getZoneNumber(9, $zones));
            
            case 'proveedor':
            case 'provider':
                return mb_strtoupper($employee['provider']['name'] ?? '');
            
            case 'credential_uuid':
                // El UUID se pasa como parámetro adicional desde embedEmployeeData
                return isset($this->currentCredentialUuid) ? mb_strtoupper($this->currentCredentialUuid) : '';
            
            default:
                Log::warning('[CREDENTIAL SERVICE] Block ID no reconocido', [
                    'block_id' => $blockId
                ]);
                return null;
        }
    }
    
    /**
     * Obtener número de zona si está autorizada
     */
    private function getZoneNumber(int $zoneNumber, ?array $zones = null): string
    {
        if (!$zones || empty($zones)) {
            return ''; // No mostrar número si no hay zonas autorizadas
        }
        
        // Verificar si la zona está en la lista de zonas autorizadas
        foreach ($zones as $zone) {
            if (isset($zone['id']) && intval($zone['id']) === $zoneNumber) {
                return strval($zoneNumber); // Devolver el número como string (será convertido a mayúsculas en getTextForBlock)
            }
        }
        
        return ''; // Zona no autorizada, no mostrar número
    }

    /**
     * Regenerate a failed or expired credential
     *
     * @param Credential $credential
     * @return Credential
     */
    public function regenerateCredential(Credential $credential): Credential
    {
        Log::info('[CREDENTIAL SERVICE] Regenerando credencial', [
            'credential_id' => $credential->id,
            'credential_uuid' => $credential->uuid,
            'previous_status' => $credential->status
        ]);
        
        // Reiniciar el estado y contadores
        $credential->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => 0,
            'generated_at' => null,
            'qr_code' => null,
            'qr_image_path' => null,
            'credential_image_path' => null,
            'credential_pdf_path' => null
        ]);
        
        // Despachar el job para procesamiento
        dispatch(new \App\Jobs\GenerateCredentialJob($credential))->onQueue('credentials');
        
        Log::info('[CREDENTIAL SERVICE] Credencial enviada para regeneración');
        
        return $credential;
    }

    /**
     * Detectar dimensiones y orientación de la plantilla
     */
    private function detectTemplateDimensions(Credential $credential): array
    {
        Log::info('[CREDENTIAL SERVICE] ENTRANDO A detectTemplateDimensions', [
            'credential_id' => $credential->id,
            'has_template_snapshot' => !empty($credential->template_snapshot)
        ]);
        
        $defaultConfig = config('credentials.image');
        
        Log::info('[CREDENTIAL SERVICE] DEBUG: Verificando template snapshot', [
            'template_snapshot_exists' => !empty($credential->template_snapshot),
            'has_file_path' => isset($credential->template_snapshot['file_path']),
            'file_path_value' => $credential->template_snapshot['file_path'] ?? 'N/A'
        ]);
        
        // Si no hay template snapshot, usar dimensiones por defecto
        if (!$credential->template_snapshot || !isset($credential->template_snapshot['file_path'])) {
            Log::info('[CREDENTIAL SERVICE] Sin template snapshot, usando dimensiones por defecto');
            return [
                'width' => $defaultConfig['width'],
                'height' => $defaultConfig['height'],
                'orientation' => $defaultConfig['width'] > $defaultConfig['height'] ? 'landscape' : 'portrait'
            ];
        }

        try {
            $templatePath = Storage::disk('public')->path('templates/' . $credential->template_snapshot['file_path']);
            
            if (!file_exists($templatePath)) {
                Log::warning('[CREDENTIAL SERVICE] Archivo de template no encontrado, usando dimensiones por defecto', [
                    'template_path' => $templatePath
                ]);
                return [
                    'width' => $defaultConfig['width'],
                    'height' => $defaultConfig['height'],
                    'orientation' => 'portrait'
                ];
            }

            // Obtener dimensiones reales del archivo de imagen
            $imageInfo = getimagesize($templatePath);
            
            if (!$imageInfo) {
                Log::warning('[CREDENTIAL SERVICE] No se pudieron obtener dimensiones de la imagen');
                return [
                    'width' => $defaultConfig['width'],
                    'height' => $defaultConfig['height'],
                    'orientation' => 'portrait'
                ];
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $isLandscape = $originalWidth > $originalHeight;
            
            Log::info('[CREDENTIAL SERVICE] DEBUG: Análisis de orientación', [
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'ratio' => round($originalWidth / $originalHeight, 2),
                'is_landscape_calculation' => $originalWidth . ' > ' . $originalHeight . ' = ' . ($isLandscape ? 'true' : 'false'),
                'template_path' => $templatePath
            ]);
            
            // Calcular dimensiones optimizadas manteniendo proporción
            $maxSize = 1448; // Tamaño máximo para mantener calidad
            
            if ($isLandscape) {
                // Plantilla horizontal: ancho = maxSize, calcular alto proporcionalmente
                $width = $maxSize;
                $height = intval(($originalHeight / $originalWidth) * $maxSize);
                $orientation = 'landscape';
                Log::info('[CREDENTIAL SERVICE] DEBUG: Configurando como LANDSCAPE');
            } else {
                // Plantilla vertical: alto = maxSize, calcular ancho proporcionalmente
                $height = $maxSize;
                $width = intval(($originalWidth / $originalHeight) * $maxSize);
                $orientation = 'portrait';
                Log::info('[CREDENTIAL SERVICE] DEBUG: Configurando como PORTRAIT');
            }

            Log::info('[CREDENTIAL SERVICE] Dimensiones originales vs finales', [
                'original' => $originalWidth . 'x' . $originalHeight,
                'final' => $width . 'x' . $height,
                'orientation' => $orientation
            ]);

            return [
                'width' => $width,
                'height' => $height,
                'orientation' => $orientation,
                'original_width' => $originalWidth,
                'original_height' => $originalHeight
            ];
            
        } catch (Exception $e) {
            Log::error('[CREDENTIAL SERVICE] Error detectando dimensiones del template', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'width' => $defaultConfig['width'],
                'height' => $defaultConfig['height'],
                'orientation' => 'portrait'
            ];
        }
    }
}
