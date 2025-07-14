<?php

namespace App\Services\Area;

use App\Models\Area;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface AreaServiceInterface
{
    /**
     * Get paginated areas with optional filtering
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedAreas(Request $request): LengthAwarePaginator;
    
    /**
     * Get area statistics
     *
     * @return array<string, int>
     */
    public function getAreaStats(): array;
    
    /**
     * Create a new area
     *
     * @param array $data
     * @return Area
     */
    public function createArea(array $data): Area;
    
    /**
     * Get area by ID
     *
     * @param int $id
     * @return Area
     * @throws \Exception if area is not found
     */
    public function getAreaById(int $id): Area;
    
    /**
     * Get area by UUID
     *
     * @param string $uuid
     * @return Area
     * @throws \Exception if area is not found
     */
    public function getAreaByUuid(string $uuid): Area;
    
    /**
     * Update an area
     *
     * @param Area $area
     * @param array $data
     * @return Area
     */
    public function updateArea(Area $area, array $data): Area;
    
    /**
     * Delete an area
     *
     * @param Area $area
     * @return bool
     */
    public function deleteArea(Area $area): bool;
}
