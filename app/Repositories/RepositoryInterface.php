<?php

namespace App\Repositories;

interface RepositoryInterface
{
    /**
     * Get all resources with optional relations
     *
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $relations = []);
    
    /**
     * Get paginated resources
     *
     * @param int $perPage
     * @param array $relations
     * @param array $filters
     * @param array $sortOptions
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = []);
    
    /**
     * Find resource by id
     *
     * @param int $id
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id, array $relations = []);
    
    /**
     * Find resource by UUID
     *
     * @param string $uuid
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findByUuid(string $uuid, array $relations = []);
    
    /**
     * Create new resource
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);
    
    /**
     * Update resource
     *
     * @param int $id
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(int $id, array $data);
    
    /**
     * Update resource by UUID
     *
     * @param string $uuid
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateByUuid(string $uuid, array $data);
    
    /**
     * Delete resource
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id);
    
    /**
     * Delete resource by UUID
     *
     * @param string $uuid
     * @return bool
     */
    public function deleteByUuid(string $uuid);
    
    /**
     * Apply query scopes and extensions
     *
     * @param array $scopes
     * @return $this
     */
    public function withScopes(array $scopes);
    
    /**
     * Get all resources with specified relations
     *
     * @param array $relations Relations to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $relations = []);
}
