<?php

namespace App\Repositories\PrintBatch;

use App\Models\Credential;
use App\Models\PrintBatch;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrintBatchRepository extends BaseRepository implements PrintBatchRepositoryInterface
{
    public function __construct(PrintBatch $model)
    {
        parent::__construct($model);
    }

    public function createBatch(array $data): PrintBatch
    {
        return $this->create($data);
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['event', 'generatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getProcessingBatches(): Collection
    {
        return $this->model->whereIn('status', ['queued', 'processing'])
            ->with(['event'])
            ->get();
    }

    public function getPaginatedWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['event', 'generatedBy']);

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('uuid', 'like', "%{$filters['search']}%")
                  ->orWhereHas('event', fn($eq) => $eq->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['area_id'])) {
            if (is_array($filters['area_id'])) {
                $query->whereIn('area_id', $filters['area_id']);
            } else {
                $query->where('area_id', $filters['area_id']);
            }
        }

        if (!empty($filters['provider_id'])) {
            if (is_array($filters['provider_id'])) {
                $query->whereIn('provider_id', $filters['provider_id']);
            } else {
                $query->where('provider_id', $filters['provider_id']);
            }
        }

        // Filtro para mostrar/ocultar archivados
        if (isset($filters['include_archived']) && !$filters['include_archived']) {
            $query->active();
        }

        // Ordenamiento
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    public function updateStatus(PrintBatch $batch, string $status, array $additionalData = []): bool
    {
        $updateData = array_merge(['status' => $status], $additionalData);
        
        return $batch->update($updateData);
    }

    public function getCredentialsForPrinting(array $filters): Collection
    {
        $query = Credential::query()
            ->where('status', 'ready')
            ->with(['accreditationRequest.employee', 'accreditationRequest.event']);

        // Filtro obligatorio por evento
        if (!empty($filters['event_id'])) {
            $query->whereHas('accreditationRequest', function ($q) use ($filters) {
                $q->where('event_id', $filters['event_id']);
            });
        }

        // Filtro por área (a través del proveedor)
        if (!empty($filters['area_id'])) {
            $areaIds = is_array($filters['area_id']) ? $filters['area_id'] : [$filters['area_id']];
            $query->whereHas('accreditationRequest.employee.provider', function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds);
            });
        }

        // Filtro por proveedor
        if (!empty($filters['provider_id'])) {
            $providerIds = is_array($filters['provider_id']) ? $filters['provider_id'] : [$filters['provider_id']];
            $query->whereHas('accreditationRequest.employee', function ($q) use ($providerIds) {
                $q->whereIn('provider_id', $providerIds);
            });
        }

        // Filtro "solo no impresas" (por defecto true)
        $onlyUnprinted = $filters['only_unprinted'] ?? true;
        if ($onlyUnprinted) {
            $query->unprinted();
        }

        return $query->get();
    }

    public function markCredentialsAsPrinted(Collection $credentialIds, PrintBatch $batch): void
    {
        DB::transaction(function () use ($credentialIds, $batch) {
            Credential::whereIn('id', $credentialIds->pluck('id'))
                ->update([
                    'printed_at' => now(),
                    'print_batch_id' => $batch->id
                ]);
        });
    }

    public function getBatchesForCleanup(int $daysOld = 90): Collection
    {
        return $this->model->where('status', 'ready')
            ->where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('pdf_path')
            ->get();
    }

    public function archiveBatches(Collection $batchIds): int
    {
        return $this->model->whereIn('id', $batchIds)
            ->update(['status' => 'archived']);
    }
}
