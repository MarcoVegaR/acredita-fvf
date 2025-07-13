<?php

namespace App\Services\Zone;

use App\Models\Zone;
use App\Repositories\Zone\ZoneRepositoryInterface;
use App\Repositories\Event\EventRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneService implements ZoneServiceInterface
{
    /**
     * @var ZoneRepositoryInterface
     */
    protected $zoneRepository;
    
    /**
     * @var EventRepositoryInterface
     */
    protected $eventRepository;

    /**
     * ZoneService constructor.
     *
     * @param ZoneRepositoryInterface $zoneRepository
     * @param EventRepositoryInterface $eventRepository
     */
    public function __construct(
        ZoneRepositoryInterface $zoneRepository,
        EventRepositoryInterface $eventRepository
    )
    {
        $this->zoneRepository = $zoneRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedZones(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        
        $filters = [];
        
        // Add search filter if provided
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Add code filter if provided
        if ($request->has('code')) {
            $filters['code'] = $request->input('code');
        }
        
        // Add event filter if provided
        if ($request->has('event_id')) {
            $filters['event_id'] = $request->input('event_id');
        }
        
        // Set up sorting options
        $sortOptions = [
            'field' => $request->input('sort', 'code'),
            'direction' => $request->input('order', 'asc')
        ];
        
        return $this->zoneRepository->paginate(
            $perPage,
            ['events'], // Always include events relation
            $filters,
            $sortOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createZone(array $data): Zone
    {
        // Extract event IDs if present
        $eventIds = $data['events'] ?? [];
        unset($data['events']);
        
        DB::beginTransaction();
        try {
            $zone = $this->zoneRepository->create($data);
            
            // Sync events if provided
            if (!empty($eventIds)) {
                $this->zoneRepository->syncEvents($zone, $eventIds);
            }
            
            DB::commit();
            
            // Load events relation
            $zone->load('events');
            
            return $zone;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getZoneById(int $id): Zone
    {
        $zone = $this->zoneRepository->find($id, ['events']);
        
        if (!$zone) {
            throw new \Exception("Zone not found");
        }
        
        return $zone;
    }

    /**
     * {@inheritdoc}
     */
    public function updateZone(Zone $zone, array $data): Zone
    {
        // Extract event IDs if present
        $eventIds = isset($data['events']) ? $data['events'] : null;
        unset($data['events']);
        
        DB::beginTransaction();
        try {
            // Update zone
            $zone = $this->zoneRepository->update($zone->id, $data);
            
            // Sync events if provided
            if ($eventIds !== null) {
                $this->zoneRepository->syncEvents($zone, $eventIds);
            }
            
            DB::commit();
            
            // Load events relation
            $zone->load('events');
            
            return $zone;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteZone(Zone $zone): bool
    {
        DB::beginTransaction();
        try {
            $result = $this->zoneRepository->delete($zone->id);
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
    public function getZoneWithEvents(Zone $zone): array
    {
        // Load zone with its events
        $zone = $this->getZoneById($zone->id);
        
        // Get all events (for potential assignment)
        $allEvents = $this->eventRepository->all();
        
        return [
            'zone' => $zone,
            'events' => $zone->events,
            'allEvents' => $allEvents
        ];
    }
}
