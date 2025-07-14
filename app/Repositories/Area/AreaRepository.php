<?php

namespace App\Repositories\Area;

use App\Models\Area;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AreaRepository extends BaseRepository implements AreaRepositoryInterface
{
    /**
     * AreaRepository constructor.
     *
     * @param Area $model
     */
    public function __construct(Area $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(bool $active): Collection
    {
        return $this->model->where('active', $active)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountsByStatus(): array
    {
        $active = $this->model->where('active', true)->count();
        $inactive = $this->model->where('active', false)->count();
        $total = $active + $inactive;
        $deleted = $this->model->onlyTrashed()->count();
        
        return [
            'active' => $active,
            'inactive' => $inactive,
            'total' => $total,
            'deleted' => $deleted
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid, array $relations = []): ?Area
    {
        $query = $this->model->where('uuid', $uuid);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?Area
    {
        return $this->model->where('code', $code)->first();
    }
    
    /**
     * Aplicar filtros a la consulta
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        if (isset($filters['active']) && $filters['active'] !== '') {
            $active = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $active);
        }
        
        if (isset($filters['code']) && !empty($filters['code'])) {
            $query->where('code', 'LIKE', "%{$filters['code']}%");
        }
        
        return $query;
    }
}
