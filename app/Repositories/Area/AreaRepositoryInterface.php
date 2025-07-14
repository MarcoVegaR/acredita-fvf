<?php

namespace App\Repositories\Area;

use App\Models\Area;
use App\Repositories\RepositoryInterface;

interface AreaRepositoryInterface extends RepositoryInterface
{
    /**
     * Get areas by status
     *
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(bool $active): \Illuminate\Database\Eloquent\Collection;
    
    /**
     * Get counts of areas by status
     *
     * @return array<string, int>
     */
    public function getCountsByStatus(): array;

    /**
     * Find an area by UUID
     *
     * @param string $uuid
     * @param array $relations
     * @return Area|null
     */
    public function findByUuid(string $uuid, array $relations = []): ?Area;
    
    /**
     * Find an area by code
     *
     * @param string $code
     * @return Area|null
     */
    public function findByCode(string $code): ?Area;
}
