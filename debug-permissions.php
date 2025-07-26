<?php

// Script para depurar permisos de area_manager
// Ejecutar con: php debug-permissions.php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Buscamos un usuario area_manager
$areaManager = \App\Models\User::role('area_manager')->first();

if (!$areaManager) {
    echo "⚠️ No se encontró un usuario con rol area_manager\n";
    exit(1);
}

echo "🔍 Verificando permisos para usuario: {$areaManager->name} (ID: {$areaManager->id})\n";
echo "   Email: {$areaManager->email}\n";
echo "   Rol: " . $areaManager->roles->first()->name . "\n\n";

// Verificar permisos directamente
echo "📋 PERMISOS ASIGNADOS:\n";
$permissions = $areaManager->getAllPermissions()->pluck('name')->toArray();
echo implode(", ", $permissions) . "\n\n";

// Verificar permisos específicos
$providerPermissions = [
    'provider.view', 
    'provider.manage', 
    'provider.manage_own_area'
];

$employeePermissions = [
    'employee.view',
    'employee.manage',
    'employee.manage_own_provider'
];

echo "🔐 VERIFICACIÓN DE PERMISOS CRÍTICOS:\n";

foreach ($providerPermissions as $permission) {
    echo "- " . $permission . ": " . ($areaManager->can($permission) ? "✅ SÍ" : "❌ NO") . "\n";
}

echo "\n";

foreach ($employeePermissions as $permission) {
    echo "- " . $permission . ": " . ($areaManager->can($permission) ? "✅ SÍ" : "❌ NO") . "\n";
}

echo "\n";

// Verificar políticas
echo "📜 VERIFICACIÓN DE POLÍTICAS:\n";

// Provider
echo "- Provider::create(): " . (Gate::forUser($areaManager)->allows('create', \App\Models\Provider::class) ? "✅ PERMITIDO" : "❌ DENEGADO") . "\n";

// Obtener un proveedor del área que maneja
$managedAreaIds = $areaManager->managedAreas()->pluck('id')->toArray();
if (!empty($managedAreaIds)) {
    $provider = \App\Models\Provider::whereIn('area_id', $managedAreaIds)->first();
    if ($provider) {
        echo "- Provider::update() para proveedor #{$provider->id}: " . (Gate::forUser($areaManager)->allows('update', $provider) ? "✅ PERMITIDO" : "❌ DENEGADO") . "\n";
    } else {
        echo "- Provider::update(): ⚠️ No se encontraron proveedores en sus áreas para probar\n";
    }
} else {
    echo "- Provider::update(): ⚠️ El usuario no tiene áreas asignadas\n";
}

// Employee
echo "- Employee::create(): " . (Gate::forUser($areaManager)->allows('create', \App\Models\Employee::class) ? "✅ PERMITIDO" : "❌ DENEGADO") . "\n";

// Rutas
echo "\n📍 VERIFICACIÓN DE RUTAS (middleware):\n";
$router = app('router');
$routes = collect($router->getRoutes()->getRoutesByMethod()['GET']);

$testRoutes = [
    '/providers/create' => 'providers.create',
    '/providers' => 'providers.index',
    '/employees/create' => 'employees.create',
    '/employees' => 'employees.index'
];

foreach ($testRoutes as $path => $name) {
    $route = $routes->first(function ($route) use ($name) {
        return $route->getName() === $name;
    });
    
    if (!$route) {
        echo "- {$path}: ⚠️ Ruta no encontrada\n";
        continue;
    }
    
    $middlewares = $route->middleware();
    $permissionMiddleware = collect($middlewares)->first(function ($middleware) {
        return strpos($middleware, 'permission:') === 0;
    });
    
    if ($permissionMiddleware) {
        $permission = str_replace('permission:', '', $permissionMiddleware);
        $allowed = false;
        
        foreach (explode('|', $permission) as $perm) {
            if ($areaManager->can($perm)) {
                $allowed = true;
                break;
            }
        }
        
        echo "- {$path} ({$permission}): " . ($allowed ? "✅ PERMITIDO" : "❌ DENEGADO") . "\n";
    } else {
        echo "- {$path}: ⚠️ No tiene middleware de permisos\n";
    }
}

echo "\n";
echo "✅ Depuración completada.\n";
