<?php

namespace App\Repositories\Zone;

use App\Models\Zone;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ZoneRepository extends BaseRepository implements ZoneRepositoryInterface
{
    /**
     * ZoneRepository constructor.
     *
     * @param Zone $model
     */
    public function __construct(Zone $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function syncEvents(Zone $zone, array $eventIds)
    {
        $zone->events()->sync($eventIds);
        return $zone;
    }

    /**
     * Extend the paginate method to handle specific zone filters
     *
     * @param int $perPage
     * @param array $relations
     * @param array $filters
     * @param array $sortOptions
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = [])
    {
        $query = $this->model->with($relations);
        
        // Handle search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
            unset($filters['search']);
        }
        
        // Handle code filter
        if (isset($filters['code'])) {
            $query->where('code', $filters['code']);
            unset($filters['code']);
        }
        
        // Handle event filter
        if (isset($filters['event_id'])) {
            $eventId = $filters['event_id'];
            $query->whereHas('events', function($q) use ($eventId) {
                $q->where('events.id', $eventId);
            });
            unset($filters['event_id']);
        }
        
        // Apply remaining filters
        foreach ($filters as $field => $value) {
            if (is_callable($value)) {
                $value($query);
            } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                $query->where($field, $value);
            }
        }
        
        // Apply sorting
        $field = $sortOptions['field'] ?? 'id';
        $direction = $sortOptions['direction'] ?? 'asc';
        
        if (in_array($field, ['id', 'code', 'name', 'created_at'])) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('code', 'asc');
        }
        
        return $query->paginate($perPage);
    }
}
