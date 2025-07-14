<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Repositories\Event\EventRepositoryInterface;
use App\Repositories\Zone\ZoneRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventService implements EventServiceInterface
{
    /**
     * @var EventRepositoryInterface
     */
    protected $eventRepository;
    
    /**
     * @var ZoneRepositoryInterface
     */
    protected $zoneRepository;

    /**
     * EventService constructor.
     *
     * @param EventRepositoryInterface $eventRepository
     * @param ZoneRepositoryInterface $zoneRepository
     */
    public function __construct(
        EventRepositoryInterface $eventRepository,
        ZoneRepositoryInterface $zoneRepository
    )
    {
        $this->eventRepository = $eventRepository;
        $this->zoneRepository = $zoneRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedEvents(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        
        $filters = [];
        
        // Add search filter if provided
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Add active status filter if provided
        if ($request->has('active')) {
            $filters['active'] = $request->input('active');
        }
        
        // Add date range filters if provided
        if ($request->has('start_date')) {
            $filters['start_date'] = $request->input('start_date');
        }
        
        if ($request->has('end_date')) {
            $filters['end_date'] = $request->input('end_date');
        }
        
        // Add zone filter if provided
        if ($request->has('zone_id')) {
            $filters['zone_id'] = $request->input('zone_id');
        }
        
        // Set up sorting options
        $sortOptions = [
            'field' => $request->input('sort', 'id'),
            'direction' => $request->input('order', 'desc')
        ];
        
        return $this->eventRepository->paginate(
            $perPage,
            ['zones'], // Always include zones relation
            $filters,
            $sortOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEventStats(): array
    {
        return $this->eventRepository->getCountsByStatus();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAllActive()
    {
        // Obtener todos los eventos activos usando el método específico del repositorio
        return $this->eventRepository->getActive();
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(array $data): Event
    {
        // Extract zone IDs if present
        $zoneIds = $data['zones'] ?? [];
        unset($data['zones']);
        
        DB::beginTransaction();
        try {
            $event = $this->eventRepository->create($data);
            
            // Sync zones if provided
            if (!empty($zoneIds)) {
                $this->eventRepository->syncZones($event, $zoneIds);
            }
            
            DB::commit();
            
            // Load zones relation
            $event->load('zones');
            
            return $event;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEventById(int $id): Event
    {
        $event = $this->eventRepository->find($id, ['zones']);
        
        if (!$event) {
            throw new \Exception("Event not found");
        }
        
        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function updateEvent(Event $event, array $data): Event
    {
        // Extract zone IDs if present
        $zoneIds = isset($data['zones']) ? $data['zones'] : null;
        unset($data['zones']);
        
        DB::beginTransaction();
        try {
            // Update event
            $event = $this->eventRepository->update($event->id, $data);
            
            // Sync zones if provided
            if ($zoneIds !== null) {
                $this->eventRepository->syncZones($event, $zoneIds);
            }
            
            DB::commit();
            
            // Load zones relation
            $event->load('zones');
            
            return $event;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEvent(Event $event): bool
    {
        DB::beginTransaction();
        try {
            $result = $this->eventRepository->delete($event->id);
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEventWithZones(Event $event): array
    {
        // Load event with its zones
        $event = $this->getEventById($event->id);
        
        // Get all zones (for potential assignment)
        $allZones = $this->zoneRepository->all();
        
        return [
            'event' => $event,
            'zones' => $event->zones,
            'allZones' => $allZones
        ];
    }
}
