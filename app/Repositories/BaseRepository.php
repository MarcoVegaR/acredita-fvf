<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * Reset the query builder
     */
    protected function resetQuery()
    {
        $this->query = $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $relations = [])
    {
        return $this->model->with($relations)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = [])
    {
        $query = $this->model->with($relations);
        
        // Apply filters if any
        if (!empty($filters)) {
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
        }
        
        // Apply sorting
        if (!empty($sortOptions)) {
            $field = $sortOptions['field'] ?? 'id';
            $direction = $sortOptions['direction'] ?? 'desc';
            
            if (in_array($field, $this->model->getFillable()) || $field === 'id' || $field === 'created_at' || $field === 'updated_at') {
                $query->orderBy($field, $direction);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }
        
        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id, array $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid, array $relations = [])
    {
        if (!isset($this->model->uniqueIds) && !method_exists($this->model, 'uniqueIds')) {
            throw new \RuntimeException('Model does not support UUID lookups');
        }
        
        return $this->model->with($relations)->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateByUuid(string $uuid, array $data)
    {
        $record = $this->findByUuid($uuid);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id)
    {
        $record = $this->find($id);
        if ($record) {
            return $record->delete();
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteByUuid(string $uuid)
    {
        $record = $this->findByUuid($uuid);
        if ($record) {
            return $record->delete();
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function withScopes(array $scopes)
    {
        foreach ($scopes as $scope => $parameters) {
            if (is_numeric($scope)) {
                $this->query->$parameters();
            } else {
                $this->query->$scope($parameters);
            }
        }
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAll(array $relations = [])
    {
        return $this->model->with($relations)->get();
    }
}
