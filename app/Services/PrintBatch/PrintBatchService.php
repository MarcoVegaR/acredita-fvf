<?php

namespace App\Services\PrintBatch;

use App\Jobs\GeneratePrintBatchJob;
use App\Models\Area;
use App\Models\Event;
use App\Models\PrintBatch;
use App\Models\Provider;
use App\Models\User;
use App\Repositories\PrintBatch\PrintBatchRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrintBatchService implements PrintBatchServiceInterface
{
    public function __construct(
        private PrintBatchRepositoryInterface $printBatchRepository
    ) {}

    public function queueBatch(array $filters, User $user): PrintBatch
    {
        Log::info('[PRINT BATCH SERVICE] Iniciando creación de lote', [
            'filters' => $filters,
            'user_id' => $user->id
        ]);

        // Validar filtros
        $validatedFilters = $this->validateFilters($filters);

        // Obtener credenciales candidatas
        $credentials = $this->printBatchRepository->getCredentialsForPrinting($validatedFilters);

        if ($credentials->isEmpty()) {
            throw new \Exception('No se encontraron credenciales listas para imprimir con los filtros especificados.');
        }

        Log::info('[PRINT BATCH SERVICE] Credenciales encontradas', [
            'count' => $credentials->count(),
            'credential_ids' => $credentials->pluck('id')->toArray()
        ]);

        // Crear el lote
        $batchData = [
            'event_id' => $validatedFilters['event_id'],
            'area_id' => $validatedFilters['area_id'] ?? null,
            'provider_id' => $validatedFilters['provider_id'] ?? null,
            'generated_by' => $user->id,
            'status' => 'queued',
            'filters_snapshot' => $validatedFilters,
            'total_credentials' => $credentials->count(),
            'processed_credentials' => 0
        ];

        $batch = $this->printBatchRepository->createBatch($batchData);

        Log::info('[PRINT BATCH SERVICE] Lote creado', [
            'batch_id' => $batch->id,
            'batch_uuid' => $batch->uuid
        ]);

        // Encolar job para procesamiento
        GeneratePrintBatchJob::dispatch($batch, $credentials->pluck('id'))
            ->onQueue('print_batches');

        Log::info('[PRINT BATCH SERVICE] Job encolado', [
            'batch_uuid' => $batch->uuid,
            'queue' => 'print_batches'
        ]);

        // Cargar solo las relaciones válidas (event y generatedBy)
        // areas y providers ahora se obtienen mediante accessors
        return $batch->load(['event', 'generatedBy']);
    }

    public function getPaginatedBatches(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->printBatchRepository->getPaginatedWithFilters($filters, $perPage);
    }

    public function getProcessingBatches(): array
    {
        $batches = $this->printBatchRepository->getProcessingBatches();

        return $batches->map(function ($batch) {
            return [
                'id' => $batch->id,
                'uuid' => $batch->uuid,
                'status' => $batch->status,
                'progress_percentage' => $batch->progress_percentage,
                'total_credentials' => $batch->total_credentials,
                'processed_credentials' => $batch->processed_credentials,
                'event_name' => $batch->event->name ?? 'N/A'
            ];
        })->toArray();
    }

    public function downloadBatch(PrintBatch $batch): string
    {
        if (!$batch->canBeDownloaded()) {
            throw new \Exception('El lote no está listo para descarga.');
        }

        if (!Storage::disk('public')->exists($batch->pdf_path)) {
            throw new \Exception('El archivo PDF no existe.');
        }

        return Storage::disk('public')->path($batch->pdf_path);
    }

    public function getFiltersData(): array
    {
        return [
            'events' => Event::select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray(),
            
            'areas' => Area::select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray(),
            
            'providers' => Provider::select('id', 'name', 'area_id')
                ->with('area:id,name')
                ->orderBy('name')
                ->get()
                ->toArray(),
            
            'statuses' => [
                ['value' => 'queued', 'label' => 'En Cola'],
                ['value' => 'processing', 'label' => 'Procesando'],
                ['value' => 'ready', 'label' => 'Listo'],
                ['value' => 'failed', 'label' => 'Fallido'],
                ['value' => 'archived', 'label' => 'Archivado']
            ]
        ];
    }

    public function validateFilters(array $filters): array
    {
        // Validar evento obligatorio
        if (empty($filters['event_id'])) {
            throw new \Exception('El evento es obligatorio para crear un lote de impresión.');
        }

        // Verificar que el evento existe y está activo
        $event = Event::find($filters['event_id']);
        if (!$event) {
            throw new \Exception('El evento especificado no existe.');
        }

        // Validar área si se especifica
        if (!empty($filters['area_id'])) {
            $areaIds = is_array($filters['area_id']) ? $filters['area_id'] : [$filters['area_id']];
            $existingAreas = Area::whereIn('id', $areaIds)->count();
            if ($existingAreas !== count($areaIds)) {
                throw new \Exception('Una o más áreas especificadas no existen.');
            }
        }

        // Validar proveedor si se especifica
        if (!empty($filters['provider_id'])) {
            $providerIds = is_array($filters['provider_id']) ? $filters['provider_id'] : [$filters['provider_id']];
            $existingProviders = Provider::whereIn('id', $providerIds)->count();
            if ($existingProviders !== count($providerIds)) {
                throw new \Exception('Uno o más proveedores especificados no existen.');
            }
        }

        // Establecer valor por defecto para "solo no impresas"
        $filters['only_unprinted'] = $filters['only_unprinted'] ?? true;

        return $filters;
    }

    public function getBatchStats(): array
    {
        $totalBatches = PrintBatch::where('status', '!=', 'archived')->count();
        $readyBatches = PrintBatch::where('status', 'ready')->count();
        $processingBatches = PrintBatch::whereIn('status', ['queued', 'processing'])->count();
        $failedBatches = PrintBatch::where('status', 'failed')->count();

        return [
            'total' => $totalBatches,
            'ready' => $readyBatches,
            'processing' => $processingBatches,
            'failed' => $failedBatches
        ];
    }

    public function retryBatch(PrintBatch $batch): PrintBatch
    {
        if (!$batch->canBeRetried()) {
            throw new \Exception('Este lote no puede ser reintentado.');
        }

        // Resetear estado del lote
        $batch->update([
            'status' => 'queued',
            'error_message' => null,
            'processed_credentials' => 0,
            'started_at' => null,
            'finished_at' => null
        ]);

        // Obtener credenciales originales basadas en los filtros
        $credentials = $this->printBatchRepository->getCredentialsForPrinting($batch->filters_snapshot);

        // Re-encolar job
        GeneratePrintBatchJob::dispatch($batch, $credentials->pluck('id'))
            ->onQueue('print_batches');

        Log::info('[PRINT BATCH SERVICE] Lote reencolado para reintento', [
            'batch_uuid' => $batch->uuid,
            'retry_count' => $batch->retry_count
        ]);

        return $batch->fresh();
    }

    public function cleanupOldBatches(int $daysOld = 90): array
    {
        $batchesToCleanup = $this->printBatchRepository->getBatchesForCleanup($daysOld);
        
        $cleanedFiles = 0;
        $archivedBatches = 0;

        foreach ($batchesToCleanup as $batch) {
            // Eliminar archivo PDF físico
            if ($batch->pdf_path && Storage::disk('public')->exists($batch->pdf_path)) {
                Storage::disk('public')->delete($batch->pdf_path);
                $cleanedFiles++;
            }

            // Marcar como archivado
            $batch->update([
                'status' => 'archived',
                'pdf_path' => null
            ]);
            $archivedBatches++;
        }

        Log::info('[PRINT BATCH SERVICE] Limpieza de lotes completada', [
            'days_old' => $daysOld,
            'cleaned_files' => $cleanedFiles,
            'archived_batches' => $archivedBatches
        ]);

        return [
            'cleaned_files' => $cleanedFiles,
            'archived_batches' => $archivedBatches,
            'total_processed' => $batchesToCleanup->count()
        ];
    }
}
