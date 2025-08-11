<?php

namespace App\Jobs;

use App\Models\Credential;
use App\Models\PrintBatch;
use App\Repositories\PrintBatch\PrintBatchRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratePrintBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;
    public $timeout = 1800; // 30 minutos

    public function __construct(
        public PrintBatch $batch,
        public Collection $credentialIds
    ) {}

    public function handle(PrintBatchRepositoryInterface $repository): void
    {
        Log::info('[GENERATE PRINT BATCH JOB] Iniciando procesamiento', [
            'batch_uuid' => $this->batch->uuid,
            'credential_count' => $this->credentialIds->count()
        ]);

        try {
            // Verificar si el lote ya está procesado
            if ($this->batch->fresh()->status === 'ready') {
                Log::info('[GENERATE PRINT BATCH JOB] Lote ya procesado', [
                    'batch_uuid' => $this->batch->uuid
                ]);
                return;
            }

            // Marcar como procesando
            $this->batch->markAsProcessing();

            // Obtener credenciales actualizadas
            $credentials = Credential::whereIn('id', $this->credentialIds)
                ->where('status', 'ready')
                ->with(['accreditationRequest.employee'])
                ->get();

            if ($credentials->isEmpty()) {
                throw new \Exception('No se encontraron credenciales válidas para procesar.');
            }

            Log::info('[GENERATE PRINT BATCH JOB] Credenciales cargadas', [
                'batch_uuid' => $this->batch->uuid,
                'credentials_found' => $credentials->count()
            ]);

            // Generar PDF
            $pdfPath = $this->generatePDF($credentials);

            // Marcar credenciales como impresas
            $repository->markCredentialsAsPrinted($credentials, $this->batch);

            // Marcar lote como listo
            $this->batch->markAsReady($pdfPath);

            Log::info('[GENERATE PRINT BATCH JOB] Procesamiento completado exitosamente', [
                'batch_uuid' => $this->batch->uuid,
                'pdf_path' => $pdfPath,
                'credentials_processed' => $credentials->count()
            ]);

        } catch (\Exception $e) {
            Log::error('[GENERATE PRINT BATCH JOB] Error en procesamiento', [
                'batch_uuid' => $this->batch->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->batch->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function generatePDF(Collection $credentials): string
    {
        Log::info('[GENERATE PRINT BATCH JOB] Iniciando generación de PDF', [
            'batch_uuid' => $this->batch->uuid,
            'total_credentials' => $credentials->count()
        ]);

        // SOLUCIÓN BASADA EN MEJORES PRÁCTICAS DE FPDF:
        // Las credenciales PNG son de 1448x1018 píxeles a 96 DPI por defecto
        // Necesitamos convertir correctamente píxeles → milímetros
        
        // Constantes de conversión según documentación FPDF
        $defaultDpi = 96; // DPI por defecto que usa FPDF para imágenes
        $mmPerInch = 25.4; // 1 pulgada = 25.4 milímetros
        
        // Dimensiones de referencia de las credenciales individuales
        $credentialPixelWidth = 1448;
        $credentialPixelHeight = 1018;
        
        // Conversión píxel → milímetro: mm = px * 25.4 / dpi
        $credentialMmWidth = $credentialPixelWidth * $mmPerInch / $defaultDpi;   // ~383.8mm
        $credentialMmHeight = $credentialPixelHeight * $mmPerInch / $defaultDpi; // ~269.5mm
        
        Log::info('[GENERATE PRINT BATCH JOB] Cálculo de dimensiones', [
            'batch_uuid' => $this->batch->uuid,
            'credential_pixels' => "{$credentialPixelWidth}x{$credentialPixelHeight}",
            'conversion_dpi' => $defaultDpi,
            'credential_mm' => round($credentialMmWidth, 1) . 'x' . round($credentialMmHeight, 1),
            'mm_per_inch' => $mmPerInch
        ]);
        
        // Inicializar FPDF con dimensiones calculadas correctamente
        $pdf = new \FPDF('L', 'mm', [round($credentialMmWidth, 2), round($credentialMmHeight, 2)]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0); // Sin márgenes para fidelidad 1:1
        
        // **VERIFICACIÓN EN TIEMPO DE EJECUCIÓN**: Confirmar dimensiones reales del PDF
        // Agregar página temporal para poder leer dimensiones
        $pdf->AddPage();
        $actualPageWidth = $pdf->GetPageWidth();
        $actualPageHeight = $pdf->GetPageHeight();
        
        Log::info('[GENERATE PRINT BATCH JOB] Verificación de dimensiones PDF', [
            'batch_uuid' => $this->batch->uuid,
            'expected_mm' => round($credentialMmWidth, 2) . 'x' . round($credentialMmHeight, 2),
            'actual_mm' => $actualPageWidth . 'x' . $actualPageHeight,
            'dimensions_match' => (abs($actualPageWidth - $credentialMmWidth) < 0.1 && abs($actualPageHeight - $credentialMmHeight) < 0.1)
        ]);
        
        // Configuración para mejor tamaño de archivo (habilitar compresión)
        if (method_exists($pdf, 'SetCompression')) {
            $pdf->SetCompression(true); // Habilitar compresión para reducir tamaño
        }
        
        // Limpiar la página temporal
        $pdf = new \FPDF('L', 'mm', [round($credentialMmWidth, 2), round($credentialMmHeight, 2)]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        // Configuración de compresión y calidad (debe aplicarse antes de insertar imágenes)
        if (method_exists($pdf, 'SetCompression')) {
            $pdf->SetCompression(true);
        }
        if (method_exists($pdf, 'SetJPEGQuality')) {
            $pdf->SetJPEGQuality(90); // Calidad alta pero no máxima, mejor desempeño
        }

        // Configuración básica del PDF
        $credentialsPerPage = 1; // Una credencial por página para máxima fidelidad

        $processedCount = 0;
        $chunkSize = 100; // Reducir tamaño de chunk para evitar problemas de memoria

        // Procesar en chunks si hay muchas credenciales
        $chunks = $credentials->chunk($chunkSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            Log::info('[GENERATE PRINT BATCH JOB] Procesando chunk', [
                'batch_uuid' => $this->batch->uuid,
                'chunk_index' => $chunkIndex + 1,
                'chunk_size' => $chunk->count()
            ]);

            foreach ($chunk as $index => $credential) {
                $globalIndex = $processedCount + $index;

                // Una credencial por página - siempre agregar página nueva
                $pdf->AddPage();

                // Verificar que existe el archivo de imagen de la credencial
                $imagePath = $credential->credential_image_path;
                if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
                    Log::warning('[GENERATE PRINT BATCH JOB] Imagen de credencial no encontrada', [
                        'credential_id' => $credential->id,
                        'image_path' => $imagePath
                    ]);
                    continue;
                }

                $fullImagePath = Storage::disk('public')->path($imagePath);
                
                // Obtener información básica de la imagen (ancho/alto)
                $imageInfo = getimagesize($fullImagePath);
                if (!$imageInfo) {
                    Log::warning('[GENERATE PRINT BATCH JOB] No se pudo obtener información de la imagen', [
                        'credential_id' => $credential->id,
                        'image_path' => $fullImagePath
                    ]);
                    continue;
                }
                
                [$pixelWidth, $pixelHeight] = $imageInfo;
                Log::debug('[GENERATE PRINT BATCH JOB] Procesando imagen', [
                    'batch_uuid' => $this->batch->uuid,
                    'credential_id' => $credential->id,
                    'image_pixels' => "{$pixelWidth}x{$pixelHeight}"
                ]);
                
                // **MÉTODO FULL-WIDTH**: Hacer que la imagen ocupe exactamente todo el ancho de la página
                $pageWidth = $pdf->GetPageWidth();
                $pageHeight = $pdf->GetPageHeight();
                
                // Calcular proporción de la imagen
                $imageAspectRatio = $pixelHeight / $pixelWidth;
                $calculatedHeight = $pageWidth * $imageAspectRatio;
                
                Log::debug('[GENERATE PRINT BATCH JOB] Colocación de imagen full-width', [
                    'batch_uuid' => $this->batch->uuid,
                    'credential_id' => $credential->id,
                    'page_dimensions' => "{$pageWidth}x{$pageHeight}mm",
                    'image_aspect_ratio' => round($imageAspectRatio, 3),
                    'calculated_height' => round($calculatedHeight, 1) . 'mm',
                    'height_matches_page' => abs($calculatedHeight - $pageHeight) < 0.1
                ]);
                
                // Colocar imagen ocupando todo el ancho de la página
                // Si usamos altura 0, FPDF mantendrá la proporción automáticamente
                try {
                    // **TÉCNICA RECOMENDADA**: width = página completa, height = 0 (proporción automática)
                    $pdf->Image($fullImagePath, 0, 0, $pageWidth, 0);
                    
                    Log::debug('[GENERATE PRINT BATCH JOB] Imagen colocada exitosamente con método full-width', [
                        'credential_id' => $credential->id,
                        'original_pixels' => "{$pixelWidth}x{$pixelHeight}",
                        'final_width_mm' => $pageWidth,
                        'auto_height' => 'proporción_automática',
                        'method' => 'full_width_auto_height'
                    ]);
                } catch (\Exception $e) {
                    Log::error('[GENERATE PRINT BATCH JOB] Error al colocar imagen en PDF', [
                        'credential_id' => $credential->id,
                        'error' => $e->getMessage(),
                        'image_path' => $fullImagePath
                    ]);
                    continue;
                }
            }

            $processedCount += $chunk->count();

            // Actualizar progreso
            $this->batch->updateProgress($processedCount);

            // Liberar memoria del chunk
            unset($chunk);

            Log::info('[GENERATE PRINT BATCH JOB] Chunk procesado', [
                'batch_uuid' => $this->batch->uuid,
                'processed_count' => $processedCount,
                'total_count' => $credentials->count(),
                'progress_percentage' => round(($processedCount / $credentials->count()) * 100, 1)
            ]);
            
            // Liberar recursos para evitar problemas de memoria
            gc_collect_cycles();
        }

        // Generar nombre de archivo único y asegurar directorio
        $dir = 'print_batches';
        Storage::disk('public')->makeDirectory($dir);
        $fileName = "$dir/batch_{$this->batch->uuid}.pdf";
        
        // Guardar PDF directamente a disco para ahorrar memoria
        try {
            $fullPath = Storage::disk('public')->path($fileName);
            $pdf->Output('F', $fullPath);
            
            // Verificar si el tamaño es razonable para evitar archivos corruptos
            $fileSize = @filesize($fullPath) ?: 0;
            if ($fileSize < 1024) { // menos de 1KB es sospechoso
                throw new \Exception("PDF generado demasiado pequeño: {$fileSize} bytes");
            }
            
            // Registro detallado para verificación
            Log::info('[GENERATE PRINT BATCH JOB] PDF guardado exitosamente', [
                'batch_uuid' => $this->batch->uuid,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'pages' => $pdf->PageNo(),
                'path' => $fullPath
            ]);
        } catch (\Exception $e) {
            Log::error('[GENERATE PRINT BATCH JOB] Error al guardar el PDF', [
                'batch_uuid' => $this->batch->uuid,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-lanzar para que el job falle adecuadamente
        }
        
        Log::info('[GENERATE PRINT BATCH JOB] PDF generado exitosamente', [
            'batch_uuid' => $this->batch->uuid,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'total_pages' => $pdf->PageNo()
        ]);

        return $fileName;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[GENERATE PRINT BATCH JOB] Job fallido definitivamente', [
            'batch_uuid' => $this->batch->uuid,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->batch->markAsFailed(
            "Error después de {$this->attempts()} intentos: " . $exception->getMessage()
        );
    }
}
