<?php

namespace App\Repositories\Template;

use App\Models\Template;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TemplateRepository extends BaseRepository implements TemplateRepositoryInterface
{
    /**
     * TemplateRepository constructor.
     *
     * @param Template $model
     */
    public function __construct(Template $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function listByEvent(int $eventId, array $relations = [])
    {
        return $this->model->with($relations)
                          ->where('event_id', $eventId)
                          ->orderBy('is_default', 'desc') // Default templates first
                          ->orderBy('version', 'desc') // Then newest versions
                          ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid, array $relations = [])
    {
        return $this->model->with($relations)
                          ->where('uuid', $uuid)
                          ->first();
    }
    
    /**
     * Paginate templates with specific filters handling
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
        
        // Handle search filter for templates
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
            unset($filters['search']);
        }
        
        // Handle event filter
        if (isset($filters['event_id'])) {
            $eventId = $filters['event_id'];
            $query->where('event_id', $eventId);
            unset($filters['event_id']);
        }
        
        // Apply remaining filters
        foreach ($filters as $field => $value) {
            if (is_callable($value)) {
                $value($query);
            } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                $query->where($field, $value);
            } elseif (is_array($value) && count($value) === 3) {
                $query->where($value[0], $value[1], $value[2]);
            } elseif (is_array($value) && count($value) === 2) {
                $query->where($value[0], $value[1]);
            }
        }
        
        // Apply sorting
        if (!empty($sortOptions)) {
            $field = $sortOptions['field'] ?? 'id';
            $direction = $sortOptions['direction'] ?? 'desc';
            
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDefaultForEvent(int $eventId, array $relations = [])
    {
        return $this->model->with($relations)
                          ->where('event_id', $eventId)
                          ->where('is_default', true)
                          ->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function setAsDefault(Template $template)
    {
        try {
            DB::beginTransaction();
            
            // Unset any existing default for this event
            $this->model->where('event_id', $template->event_id)
                       ->where('is_default', true)
                       ->where('id', '!=', $template->id)
                       ->update(['is_default' => false]);
            
            // Set the new default
            $template->is_default = true;
            $result = $template->save();
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
