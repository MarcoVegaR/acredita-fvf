<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionHelper
{
    /**
     * Get all permissions with their display names
     *
     * @param bool $refresh Force a cache refresh
     * @return array<int, array<string, mixed>>
     */
    public static function getAllPermissions(bool $refresh = false): array
    {
        $cacheKey = 'all_permissions';
        
        if ($refresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addHour(), function () {
            return Permission::all()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'nameshow' => $permission->nameshow ?? self::formatPermissionName($permission->name)
                ];
            })->toArray();
        });
    }

    /**
     * Format a permission name for display
     *
     * @param string $name Permission name
     * @return string Formatted name
     */
    public static function formatPermissionName(string $name): string
    {
        // Handle resource.action format (e.g. users.create → Crear usuarios)
        if (Str::contains($name, '.')) {
            [$resource, $action] = explode('.', $name, 2);
            
            $actionNames = [
                'index' => 'Listar',
                'show' => 'Ver',
                'create' => 'Crear', 
                'store' => 'Guardar',
                'edit' => 'Editar',
                'update' => 'Actualizar', 
                'delete' => 'Eliminar',
                'destroy' => 'Eliminar'
            ];
            
            $resourceNames = [
                'users' => 'usuarios',
                'roles' => 'roles',
                'documents' => 'documentos',
                'images' => 'imágenes',
                'templates' => 'plantillas',
                'events' => 'eventos',
                'zones' => 'zonas',
                'employees' => 'empleados'
                // Añadir más recursos según sea necesario
            ];
            
            $actionName = $actionNames[$action] ?? ucfirst($action);
            $resourceName = $resourceNames[$resource] ?? $resource;
            
            return $actionName . ' ' . $resourceName;
        }
        
        // Fallback for older permission format
        return ucfirst(str_replace(['_', '.'], ' ', $name));
    }

    /**
     * Get all permissions grouped by resource
     *
     * @param bool $refresh Force a cache refresh
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getPermissionsByResource(bool $refresh = false): array
    {
        $cacheKey = 'permissions_by_resource';
        
        if ($refresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addHour(), function () {
            $permissions = self::getAllPermissions();
            $grouped = [];
            
            foreach ($permissions as $permission) {
                // Extract resource from permission name (e.g., "users.edit" -> "users")
                $parts = explode('.', $permission['name']);
                $resource = $parts[0] ?? 'general';
                
                if (!isset($grouped[$resource])) {
                    $grouped[$resource] = [];
                }
                
                $grouped[$resource][] = $permission;
            }
            
            return $grouped;
        });
    }

    /**
     * Get all roles with their permissions
     *
     * @param bool $refresh Force a cache refresh 
     * @return array<int, array<string, mixed>>
     */
    public static function getAllRoles(bool $refresh = false): array
    {
        $cacheKey = 'all_roles';
        
        if ($refresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addHour(), function () {
            return Role::with('permissions')->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name, 
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            })->toArray();
        });
    }

    /**
     * Get permissions required for DataTable actions
     * 
     * @param string $resource Resource name (e.g., 'users', 'roles')
     * @return array<string, string> Map of actions to permission names
     */
    public static function getDataTableActionPermissions(string $resource): array
    {
        return [
            'view' => "$resource.show",
            'edit' => "$resource.edit",
            'delete' => "$resource.delete"
        ];
    }

    /**
     * Check if the current user has any of the given permissions
     *
     * @param array|string $permissions
     * @return bool
     */
    public static function hasAnyPermission(array|string $permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->hasAnyPermission($permissions);
    }

    /**
     * Check if the current user has all of the given permissions
     *
     * @param array|string $permissions
     * @return bool
     */
    public static function hasAllPermissions(array|string $permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->hasAllPermissions($permissions);
    }
    
    /**
     * Clear all permission and role caches
     *
     * @return void
     */
    public static function clearCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::forget('all_permissions');
        Cache::forget('permissions_by_resource');
        Cache::forget('all_roles');
    }
}
