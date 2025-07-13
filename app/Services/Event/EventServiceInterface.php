<?php

namespace App\Services\Event;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventServiceInterface
{
    /**
     * Get paginated list of events with filters
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedEvents(Request $request): LengthAwarePaginator;
    
    /**
     * Get event statistics
     *
     * @return array
     */
    public function getEventStats(): array;
    
    /**
     * Create new event
     *
     * @param array $data
     * @return Event
     */
    public function createEvent(array $data): Event;
    
    /**
     * Get event by ID
     *
     * @param int $id
     * @return Event
     */
    public function getEventById(int $id): Event;
    
    /**
     * Update existing event
     *
     * @param Event $event
     * @param array $data
     * @return Event
     */
    public function updateEvent(Event $event, array $data): Event;
    
    /**
     * Delete event
     *
     * @param Event $event
     * @return bool
     */
    public function deleteEvent(Event $event): bool;
    
    /**
     * Get event with zones
     *
     * @param Event $event
     * @return array
     */
    public function getEventWithZones(Event $event): array;
}
