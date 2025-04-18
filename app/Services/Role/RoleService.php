<?php

namespace App\Services\Role;

use App\Models\Role;
use App\Repositories\Role\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleService implements RoleServiceInterface
{
    /**
     * @var RoleRepositoryInterface
     */
    protected $roleRepository;

    /**
     * RoleService constructor.
     *
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedRoles(Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');
        $sortField = $request->input('sort', 'id');
        $sortDirection = $request->input('order', 'desc');
        
        $filters = [];
        if ($search) {
            $filters[] = function($query) use ($search) {
                return $query->where('name', 'LIKE', "%{$search}%");
            };
        }
        
        // Filtro booleano: con o sin permisos
        $hasPermissions = $request->input('has_permissions');
        if ($hasPermissions !== null) {
            // Convertir string 'true'/'false' a booleano si es necesario
            $hasPermissionsBool = filter_var($hasPermissions, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            if ($hasPermissionsBool !== null) {
                if ($hasPermissionsBool) {
                    // Con permisos
                    $filters[] = function($query) {
                        return $query->whereHas('permissions');
                    };
                } else {
                    // Sin permisos
                    $filters[] = function($query) {
                        return $query->whereDoesntHave('permissions');
                    };
                }
            }
        }
        
        // Filtrar por módulo de permisos (users, roles, etc.)
        $permissionModule = $request->input('permission_module');
        if ($permissionModule) {
            $filters[] = function($query) use ($permissionModule) {
                return $query->whereHas('permissions', function($q) use ($permissionModule) {
                    $q->where('name', 'like', $permissionModule.'.%');
                });
            };
        }
        
        // El filtro permission_type ya no se utiliza, ahora usamos has_permissions y permission_module
        
        $sortOptions = [
            'field' => $sortField,
            'direction' => $sortDirection
        ];
        
        $roles = $this->roleRepository->paginate(
            $perPage,
            ['permissions'],
            $filters,
            $sortOptions
        );
        
        // Transform the roles collection to include permission names
        $roles->getCollection()->transform(function ($role) {
            // Extraer nombres descriptivos (nameshow) o nombres técnicos (name) como respaldo
            $permissionNames = $role->permissions->map(function ($permission) {
                return $permission->nameshow ?? $permission->name;
            });
            
            // Convertir directamente a string para evitar problemas de serialización
            $permissionString = $permissionNames->implode(', ');
            if (empty($permissionString)) {
                $permissionString = 'Sin permisos';
            }
            
            // Reemplazar la colección de permisos original con el string
            $role->permissions = $permissionString;
            $role->permissions_count = $permissionNames->count();
            
            return $role;
        });
        
        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function createRole(array $data): Role
    {
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Create the role
            $role = $this->roleRepository->create([
                'name' => $data['name'],
                'guard_name' => 'web'
            ]);
            
            // Sync permissions
            if (isset($data['permissions'])) {
                $this->roleRepository->syncPermissions($role, $data['permissions']);
            }
            
            DB::commit();
            return $role;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleById(int $id): Role
    {
        return $this->roleRepository->getRoleWithPermissions($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRole(Role $role, array $data): Role
    {
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Update role name
            $role->update([
                'name' => $data['name']
            ]);
            
            // Sync permissions
            if (isset($data['permissions'])) {
                $this->roleRepository->syncPermissions($role, $data['permissions']);
            }
            
            DB::commit();
            return $role;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRole(Role $role): bool
    {
        // No permitir eliminar el rol admin
        if ($role->name === 'admin') {
            throw new \Exception("No se puede eliminar el rol administrador");
        }
        
        // No permitir eliminar roles con usuarios asignados
        if ($role->users()->count() > 0) {
            throw new \Exception("No se puede eliminar un rol que tiene usuarios asignados");
        }
        
        DB::beginTransaction();
        try {
            $result = $this->roleRepository->delete($role->id);
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
    public function getAllRoles()
    {
        return $this->roleRepository->getAll(['permissions']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function validateRoleNames(array $roleNames): array
    {
        // Obtener todos los roles existentes
        $existingRoles = $this->getAllRoles()->pluck('name')->toArray();
        
        // Filtrar solo roles válidos que existen en la base de datos
        $validRoles = array_filter($roleNames, function($roleName) use ($existingRoles) {
            return in_array($roleName, $existingRoles);
        });
        
        // Si no hay roles válidos, devolver al menos el rol 'user' si existe
        if (empty($validRoles) && in_array('user', $existingRoles)) {
            return ['user'];
        }
        
        return $validRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPermissions()
    {
        return $this->roleRepository->getAllPermissions()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'nameshow' => $permission->nameshow
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleWithPermissions(Role $role): array
    {
        // Obtener permisos asociados al rol
        $permissionNames = $role->permissions->pluck('name');
        
        // Obtener usuarios asociados a este rol con datos básicos
        $usersWithRole = $role->users()->select('id', 'name', 'email')->get();
        
        return [
            'role' => $role,
            'permissions' => $this->getAllPermissions(),
            'rolePermissions' => $permissionNames,
            'usersWithRole' => $usersWithRole
        ];
    }
}
