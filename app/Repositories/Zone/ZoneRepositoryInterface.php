<?php

namespace App\Repositories\Zone;

use App\Models\Zone;
use App\Repositories\RepositoryInterface;

interface ZoneRepositoryInterface extends RepositoryInterface
{
    /**
     * Get zones by code
     *
     * @param string $code
     * @return Zone|null
     */
    public function findByCode(string $code);
    
    /**
     * Sync events for a zone
     *
     * @param Zone $zone
     * @param array $eventIds
     * @return Zone
     */
    public function syncEvents(Zone $zone, array $eventIds);
}
