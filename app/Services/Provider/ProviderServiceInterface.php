<?php

namespace App\Services\Provider;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProviderServiceInterface
{
    /**
     * Get paginated providers with filters
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedProviders(Request $request): LengthAwarePaginator;
    
    /**
     * Get provider by UUID
     *
     * @param string $uuid
     * @return Provider
     */
    public function getProviderByUuid(string $uuid): Provider;
    
    /**
     * Create external provider
     *
     * @param array $data
     * @return Provider
     */
    public function createExternalProvider(array $data): Provider;
    
    /**
     * Create internal provider
     * 
     * @param int $areaId
     * @param int|null $managerUserId Usuario gerente (puede ser null)
     * @param array $data Datos adicionales del proveedor
     * @return Provider
     */
    public function createInternalProvider(int $areaId, ?int $managerUserId = null, array $data = []): Provider;
    
    /**
     * Update provider
     *
     * @param string $uuid
     * @param array $data
     * @return Provider
     */
    public function updateProvider(string $uuid, array $data): Provider;
    
    /**
     * Toggle provider active state
     *
     * @param string $uuid
     * @param bool $active
     * @return void
     */
    public function toggleActiveProvider(string $uuid, bool $active): void;
    
    /**
     * Reset provider user password
     *
     * @param string $uuid
     * @return void
     */
    public function resetProviderPassword(string $uuid): void;
    
    /**
     * Get provider with related data for display
     *
     * @param string $uuid
     * @return array
     */
    public function getProviderForDisplay(string $uuid): array;
    
    /**
     * Get accessible providers for the current user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleProviders(): \Illuminate\Database\Eloquent\Collection;
}
