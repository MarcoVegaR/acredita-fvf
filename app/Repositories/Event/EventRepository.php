<?php

namespace App\Repositories\Event;

use App\Models\Event;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    /**
     * EventRepository constructor.
     *
     * @param Event $model
     */
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getInactive()
    {
        return $this->model->inactive()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountsByStatus()
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'inactive' => $this->model->inactive()->count(),
            'deleted' => $this->model->onlyTrashed()->count(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function syncZones(Event $event, array $zoneIds)
    {
        $event->zones()->sync($zoneIds);
        return $event;
    }

    /**
     * Extend the paginate method to handle specific event filters
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
                  ->orWhere('description', 'like', "%{$search}%");
            });
            unset($filters['search']);
        }
        
        // Handle active status filter
        if (isset($filters['active'])) {
            $isActive = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $isActive);
            unset($filters['active']);
        }
        
        // Handle date range filter
        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
            unset($filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
            unset($filters['end_date']);
        }
        
        // Handle zone filter
        if (isset($filters['zone_id'])) {
            $zoneId = $filters['zone_id'];
            $query->whereHas('zones', function($q) use ($zoneId) {
                $q->where('zones.id', $zoneId);
            });
            unset($filters['zone_id']);
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
        $direction = $sortOptions['direction'] ?? 'desc';
        
        if (in_array($field, ['id', 'name', 'start_date', 'end_date', 'created_at'])) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('id', 'desc');
        }
        
        return $query->paginate($perPage);
    }
}
