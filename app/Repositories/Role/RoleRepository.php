<?php

namespace App\Repositories\Role;

use App\Models\Role;
use App\Models\Permission;
use App\Repositories\BaseRepository;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    /**
     * RoleRepository constructor.
     *
     * @param Role $model
     */
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByName(string $name)
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function syncPermissions(Role $role, array $permissions)
    {
        $role->syncPermissions($permissions);
        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPermissions()
    {
        return Permission::all();
    }

    /**
     * {@inheritdoc}
     */
    public function getRolesWithPermissions()
    {
        return $this->model->with('permissions')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleWithPermissions(int $id)
    {
        return $this->model->with('permissions')->findOrFail($id);
    }
}
