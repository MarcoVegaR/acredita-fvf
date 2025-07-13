<?php

namespace App\Repositories\Event;

use App\Models\Event;
use App\Repositories\RepositoryInterface;

interface EventRepositoryInterface extends RepositoryInterface
{
    /**
     * Get active events
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive();
    
    /**
     * Get inactive events
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInactive();
    
    /**
     * Get events count by status
     *
     * @return array
     */
    public function getCountsByStatus();
    
    /**
     * Sync zones for an event
     *
     * @param Event $event
     * @param array $zoneIds
     * @return Event
     */
    public function syncZones(Event $event, array $zoneIds);
}
