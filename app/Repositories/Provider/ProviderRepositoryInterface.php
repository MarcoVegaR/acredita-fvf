<?php

namespace App\Repositories\Provider;

use App\Models\Provider;
use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProviderRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated providers with filters
     *
     * @param int $perPage
     * @param array $relations
     * @param array $filters
     * @param array $sortOptions
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = []): LengthAwarePaginator;
    
    /**
     * Crea o actualiza un proveedor interno asociado a un área y un usuario gerente
     *
     * @param \App\Models\Area $area
     * @param int $userId
     * @return \App\Models\Provider
     */
    public function createOrUpdateInternal($area, $userId);
    
    /**
     * Find provider by UUID
     *
     * @param string $uuid
     * @param array $relations
     * @return Provider|null
     */
    public function findByUuid(string $uuid, array $relations = []): ?Provider;
    
    /**
     * Create external provider
     *
     * @param array $data
     * @return Provider
     */
    public function createExternal(array $data): Provider;
    
    /**
     * Create an internal provider for an area, optionally with a manager
     * 
     * @param int $areaId
     * @param int|null $managerUserId Usuario gerente (puede ser null)
     * @param array $data Datos adicionales del proveedor
     * @return Provider
     */
    public function createInternal(int $areaId, ?int $managerUserId = null, array $data = []): Provider;
    
    /**
     * Update provider
     *
     * @param string $uuid
     * @param array $data
     * @return Provider
     */
    public function updateProvider(string $uuid, array $data): Provider;
    
    /**
     * Toggle provider active status
     *
     * @param string $uuid
     * @param bool $active
     * @return void
     */
    public function toggleActive(string $uuid, bool $active): void;
}
