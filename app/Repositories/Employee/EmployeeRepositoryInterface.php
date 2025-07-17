<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all employees by provider ID.
     *
     * @param int $providerId
     * @return Collection
     */
    public function findByProviderId(int $providerId): Collection;

    /**
     * Get employees paginated by provider ID.
     *
     * @param int $providerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByProviderId(int $providerId, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get employees by provider IDs.
     *
     * @param array $providerIds
     * @return Collection
     */
    public function findByProviderIds(array $providerIds): Collection;
    
    /**
     * Get employees paginated by provider IDs.
     *
     * @param array $providerIds
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByProviderIds(array $providerIds, int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if a document already exists for a provider.
     *
     * @param string $documentType
     * @param string $documentNumber
     * @param int $providerId
     * @param int|null $excludeEmployeeId
     * @return bool
     */
    public function documentExistsForProvider(string $documentType, string $documentNumber, int $providerId, ?int $excludeEmployeeId = null): bool;
    
    /**
     * Find employee by UUID.
     *
     * @param string $uuid
     * @param array $relations
     * @return Employee|null
     */
    public function findByUuid(string $uuid, array $relations = []);
}
