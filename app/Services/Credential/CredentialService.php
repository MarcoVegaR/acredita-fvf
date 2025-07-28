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
        $this->imageManager = new ImageManager(new Driver());
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
            $text = $this->getTextForBlock($block['id'], $employee, $event, $zones);
            
            if ($text) {
                // Usar coordenadas directamente del editor (sin escalado)
                $x = intval($block['x']);
                $y = intval($block['y']);
                
                // Font size: usar directamente el valor del editor (sin escalado)
                $fontSize = $block['font_size'] ?? 12;
                
                // Verificar si este bloque debe tener fondo de color

                // Si el bloque está en la lista de fondos, aplicar fondo con el color del área
                if (in_array($block['id'], $backgroundBlocks, true)) {
                    // Obtener el color del área del proveedor del empleado
                    $areaColor = isset($employee['provider']['area']['color'])
                        ? $employee['provider']['area']['color']
                        : '#000000';

                    // Preparando background para el bloque de proveedor

                    // Calcular el tamaño real del texto para ajustar el fondo exactamente
                    $fontPath = public_path('fonts/arial.ttf');
                    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
                    $textWidth  = abs($bbox[4] - $bbox[0]);
                    $textHeight = abs($bbox[5] - $bbox[1]);
                    
                    // Padding extra para que el texto no quede pegado al borde
                    $paddingX = 10;
                    $paddingY = 6;

                    $width  = $textWidth + $paddingX * 2;
                    $height = $textHeight + $paddingY * 2;

                    // Ajustar X e Y para que el texto quede centrado dentro del rectángulo
                    $rectX = $x - $paddingX;
                    $rectY = $y - $textHeight - $paddingY + 2; // +2 para compensar baseline

                    // Sobrescribir la coordenada X de texto dentro del rectángulo para respetar padding
                    $textX = $x;
                    $textY = $y;

                    // Dimensiones calculadas para el fondo

                    // Crear una imagen de color sólido como fondo
                    $backgroundRect = $this->imageManager->create($width, $height)->fill($areaColor);

                    // Colocar el rectángulo de fondo
                    $canvas->place($backgroundRect, 'top-left', $rectX, $rectY);

                    // Rectángulo de fondo colocado

                    // Para el texto del bloque con fondo, usar color blanco y coordenadas ajustadas
                    $canvas->text($text, $textX, $textY, function ($font) use ($fontSize, $block) {
                        $font->file(public_path('fonts/arial.ttf')); // TTF válido REQUERIDO
                        $font->size($fontSize);
                        $font->color('#FFFFFF'); // Texto blanco para contraste
                        $font->align($block['alignment'] ?? 'left');
                    });
                } else {
                    // Para otros bloques, usar configuración normal
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
