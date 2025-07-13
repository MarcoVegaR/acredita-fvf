<?php

namespace App\Services\Zone;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ZoneServiceInterface
{
    /**
     * Get paginated list of zones with filters
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedZones(Request $request): LengthAwarePaginator;
    
    /**
     * Create new zone
     *
     * @param array $data
     * @return Zone
     */
    public function createZone(array $data): Zone;
    
    /**
     * Get zone by ID
     *
     * @param int $id
     * @return Zone
     */
    public function getZoneById(int $id): Zone;
    
    /**
     * Update existing zone
     *
     * @param Zone $zone
     * @param array $data
     * @return Zone
     */
    public function updateZone(Zone $zone, array $data): Zone;
    
    /**
     * Delete zone
     *
     * @param Zone $zone
     * @return bool
     */
    public function deleteZone(Zone $zone): bool;
    
    /**
     * Get zone with events
     *
     * @param Zone $zone
     * @return array
     */
    public function getZoneWithEvents(Zone $zone): array;
}
