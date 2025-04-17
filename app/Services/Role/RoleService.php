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
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Check if it's a system role
            if (in_array($role->name, ['admin', 'user', 'editor', 'viewer'])) {
                throw new \Exception('No se puede eliminar un rol del sistema');
            }
            
            // Remove permissions first
            $this->roleRepository->syncPermissions($role, []);
            
            // Delete the role
            $role->delete();
            
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
        $permissionNames = $role->permissions->pluck('name');
        
        return [
            'role' => $role,
            'permissions' => $this->getAllPermissions(),
            'rolePermissions' => $permissionNames
        ];
    }
}
