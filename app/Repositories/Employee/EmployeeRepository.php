<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * Create a new repository instance.
     *
     * @param Employee $model
     */
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all employees by provider ID.
     *
     * @param int $providerId
     * @return Collection
     */
    public function findByProviderId(int $providerId): Collection
    {
        return $this->model->where('provider_id', $providerId)->get();
    }

    /**
     * Get employees paginated by provider ID.
     *
     * @param int $providerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByProviderId(int $providerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('provider_id', $providerId)->paginate($perPage);
    }
    
    /**
     * Get employees by provider IDs.
     *
     * @param array $providerIds
     * @return Collection
     */
    public function findByProviderIds(array $providerIds): Collection
    {
        return $this->model->whereIn('provider_id', $providerIds)->get();
    }
    
    /**
     * Get employees paginated by provider IDs.
     *
     * @param array $providerIds
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByProviderIds(array $providerIds, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->whereIn('provider_id', $providerIds)->paginate($perPage);
    }

    /**
     * Check if a document already exists for a provider.
     *
     * @param string $documentType
     * @param string $documentNumber
     * @param int $providerId
     * @param int|null $excludeEmployeeId
     * @return bool
     */
    public function documentExistsForProvider(string $documentType, string $documentNumber, int $providerId, ?int $excludeEmployeeId = null): bool
    {
        $query = $this->model
            ->where('document_type', $documentType)
            ->where('document_number', $documentNumber)
            ->where('provider_id', $providerId);
            
        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }
        
        return $query->exists();
    }
    
    /**
     * Check if a document already exists globally (across all providers).
     *
     * @param string $documentType
     * @param string $documentNumber
     * @param int|null $excludeEmployeeId
     * @return bool
     */
    public function documentExistsGlobally(string $documentType, string $documentNumber, ?int $excludeEmployeeId = null): bool
    {
        $query = $this->model
            ->where('document_type', $documentType)
            ->where('document_number', $documentNumber);
            
        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }
        
        return $query->exists();
    }
    
    /**
     * Find employee by UUID.
     *
     * @param string $uuid
     * @param array $relations
     * @return Employee|null
     */
    public function findByUuid(string $uuid, array $relations = [])
    {
        return $this->model->with($relations)->where('uuid', $uuid)->first();
    }
}
