<?php
/**
 * Rutas de diagnóstico para verificar permisos
 * ¡IMPORTANTE! Este archivo es solo para diagnóstico y debe eliminarse después de resolver el problema
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

// Ruta para reiniciar la caché de permisos
Route::get('/debug/clear-permission-cache', function() {
    Artisan::call('permission:cache-reset');
    return response()->json(['message' => 'Caché de permisos reiniciada exitosamente', 'output' => Artisan::output()]);
});

Route::get('/debug/permissions', function() {
    // Verificar si el usuario está autenticado
    if (!auth()->check()) {
        return response()->json(['error' => 'No autenticado'], 401);
    }
    
    // Obtener el usuario actual
    $user = auth()->user();
    
    // Verificar si el usuario tiene el rol 'area_manager'
    $hasAreaManagerRole = $user->hasRole('area_manager');
    
    // Obtener todos los permisos del usuario
    $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
    
    // Verificar permisos específicos
    $hasManagePermission = $user->hasPermissionTo('employee.manage');
    $hasManageOwnProviderPermission = $user->hasPermissionTo('employee.manage_own_provider');
    
    // Obtener todos los roles del usuario
    $userRoles = $user->getRoleNames()->toArray();
    
    // Información del rol area_manager
    $areaManagerRole = Role::where('name', 'area_manager')->first();
    $areaManagerPermissions = [];
    
    if ($areaManagerRole) {
        $areaManagerPermissions = $areaManagerRole->permissions->pluck('name')->toArray();
    }
    
    // Obtener información sobre el área gestionada por el usuario
    $managedArea = null;
    if ($user->managedArea) {
        $managedArea = [
            'id' => $user->managedArea->id,
            'name' => $user->managedArea->name
        ];
    }
    
    // Verificar si el permiso existe en la base de datos
    $employeeManageOwnProviderPermission = Permission::where('name', 'employee.manage_own_provider')->first();
    
    // Sincronizar caché de permisos para este usuario
    $user->syncPermissions($userPermissions);
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'roles' => $userRoles,
        'is_area_manager' => $hasAreaManagerRole,
        'permissions' => [
            'all' => $userPermissions,
            'specific' => [
                'employee.manage' => $hasManagePermission,
                'employee.manage_own_provider' => $hasManageOwnProviderPermission,
            ]
        ],
        'area_manager_role' => [
            'exists' => $areaManagerRole !== null,
            'permissions' => $areaManagerPermissions
        ],
        'managed_area' => $managedArea,
        'permission_check' => [
            'employee.manage_own_provider_exists' => $employeeManageOwnProviderPermission !== null,
            'employee.manage_own_provider_details' => $employeeManageOwnProviderPermission ? [
                'id' => $employeeManageOwnProviderPermission->id,
                'guard_name' => $employeeManageOwnProviderPermission->guard_name,
                'created_at' => $employeeManageOwnProviderPermission->created_at
            ] : null
        ]
    ]);
});

// Ruta para ver y actualizar los permisos de un usuario específico
Route::get('/debug/fix-permissions/{userId}', function($userId) {
    // Verificar si el usuario está autenticado y es admin
    if (!auth()->check() || !auth()->user()->hasRole('admin')) {
        return response()->json(['error' => 'No autorizado'], 403);
    }
    
    // Buscar el usuario
    $user = User::findOrFail($userId);
    
    // Asegurarse de que el rol area_manager tenga el permiso employee.manage_own_provider
    $areaManagerRole = Role::where('name', 'area_manager')->first();
    
    if ($areaManagerRole) {
        $permission = Permission::firstOrCreate(['name' => 'employee.manage_own_provider']);
        $areaManagerRole->givePermissionTo($permission);
    }
    
    // Si el usuario es area_manager, forzar la sincronización de permisos
    if ($user->hasRole('area_manager')) {
        $user->syncRoles(['area_manager']);
        // Limpiar caché de permisos del usuario
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    
    return response()->json([
        'message' => 'Permisos actualizados para el usuario',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name')
    ]);
});
