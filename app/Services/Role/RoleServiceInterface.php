<?php

namespace App\Services\Role;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleServiceInterface
{
    /**
     * Get paginated list of roles with filters
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedRoles(Request $request): LengthAwarePaginator;
    
    /**
     * Create new role
     *
     * @param array $data
     * @return Role
     */
    public function createRole(array $data): Role;
    
    /**
     * Get role by ID
     *
     * @param int $id
     * @return Role
     */
    public function getRoleById(int $id): Role;
    
    /**
     * Update existing role
     *
     * @param Role $role
     * @param array $data
     * @return Role
     */
    public function updateRole(Role $role, array $data): Role;
    
    /**
     * Delete role
     *
     * @param Role $role
     * @return bool
     */
    public function deleteRole(Role $role): bool;
    
    /**
     * Get all available permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPermissions();
    
    /**
     * Get role with its permissions
     *
     * @param Role $role
     * @return array
     */
    public function getRoleWithPermissions(Role $role): array;
}
