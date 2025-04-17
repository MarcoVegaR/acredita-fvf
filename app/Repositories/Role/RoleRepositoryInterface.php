<?php

namespace App\Repositories\Role;

use App\Models\Role;
use App\Models\Permission;
use App\Repositories\RepositoryInterface;

interface RoleRepositoryInterface extends RepositoryInterface
{
    /**
     * Find role by name
     *
     * @param string $name
     * @return Role|null
     */
    public function findByName(string $name);
    
    /**
     * Sync permissions for a role
     *
     * @param Role $role
     * @param array $permissions
     * @return Role
     */
    public function syncPermissions(Role $role, array $permissions);
    
    /**
     * Get all permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection<Permission>
     */
    public function getAllPermissions();
    
    /**
     * Get roles with permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection<Role>
     */
    public function getRolesWithPermissions();
    
    /**
     * Get role with permissions
     *
     * @param int $id
     * @return Role
     */
    public function getRoleWithPermissions(int $id);
}
