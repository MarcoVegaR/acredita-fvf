<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Users routes grouped by controller and permissions
    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index')->middleware('permission:users.index')->name('users.index');
        Route::get('users/create', 'create')->middleware('permission:users.create')->name('users.create');
        Route::post('users', 'store')->middleware('permission:users.create')->name('users.store');
        Route::get('users/{user}', 'show')->middleware('permission:users.show')->name('users.show');
        Route::get('users/{user}/edit', 'edit')->middleware('permission:users.edit')->name('users.edit');
        Route::put('users/{user}', 'update')->middleware('permission:users.edit')->name('users.update');
        Route::delete('users/{user}', 'destroy')->middleware('permission:users.delete')->name('users.destroy');
        
    });
    
    // Rutas para gerentes de área (separada del controlador de usuarios para evitar conflictos de vinculación)
    Route::controller(\App\Http\Controllers\AreaManagerController::class)->group(function () {
        Route::get('area-managers/available', 'getAvailableManagers')
            ->middleware('permission:areas.edit')
            ->name('area-managers.available');
    });
    
    // Roles routes grouped by controller and permissions
    Route::controller(RoleController::class)->group(function () {
        Route::get('roles', 'index')->middleware('permission:roles.index')->name('roles.index');
        Route::get('roles/create', 'create')->middleware('permission:roles.create')->name('roles.create');
        Route::post('roles', 'store')->middleware('permission:roles.create')->name('roles.store');
        Route::get('roles/{role}', 'show')->middleware('permission:roles.show')->name('roles.show');
        Route::get('roles/{role}/edit', 'edit')->middleware('permission:roles.edit')->name('roles.edit');
        Route::put('roles/{role}', 'update')->middleware('permission:roles.edit')->name('roles.update');
        Route::delete('roles/{role}', 'destroy')->middleware('permission:roles.delete')->name('roles.destroy');
    });
    
    // Document management routes grouped by controller and permissions
    Route::controller(DocumentController::class)->group(function () {
        // Module-specific document routes
        Route::get('{module}/{entity_id}/documents', 'index')
            ->middleware('permission:documents.view')
            ->name('documents.index');
            
        Route::post('{module}/{entity_id}/documents', 'store')
            ->middleware('permission:documents.upload')
            ->name('documents.store');
            
        // Global document routes
        Route::delete('documents/{document_uuid}', 'destroy')
            ->middleware('permission:documents.delete')
            ->name('documents.destroy');
            
        Route::get('documents/{document_uuid}/download', 'download')
            ->middleware('permission:documents.download')
            ->name('documents.download');
    });
    
    // Image management routes grouped by controller and permissions
    Route::controller(ImageController::class)->group(function () {
        // Module-specific image routes - usando auth middleware y verificación de permisos en el controlador
        Route::get('{module}/{entity_id}/images', 'index')
            ->middleware('auth')
            ->name('images.index');
            
        Route::post('{module}/{entity_id}/images', 'store')
            ->middleware('auth')
            ->name('images.store');
        
        Route::delete('{module}/{entity_id}/images/{uuid}', 'destroy')
            ->middleware('auth')
            ->name('images.destroy');
    });
    
    // Template management routes grouped by controller and permissions
    Route::controller(TemplateController::class)->group(function () {
        Route::get('templates', 'index')
            ->middleware('permission:templates.index')
            ->name('templates.index');
            
        Route::get('templates/create', 'create')
            ->middleware('permission:templates.create')
            ->name('templates.create');
            
        Route::post('templates', 'store')
            ->middleware('permission:templates.create')
            ->name('templates.store');
            
        Route::get('templates/{template}', 'show')
            ->middleware('permission:templates.show')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.show');
            
        Route::get('templates/{template}/edit', 'edit')
            ->middleware('permission:templates.edit')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.edit');
            
        Route::put('templates/{template}', 'update')
            ->middleware('permission:templates.edit')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.update');
            
        Route::delete('templates/{template}', 'destroy')
            ->middleware('permission:templates.delete')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.destroy');
            
        Route::post('templates/{template}/set-default', 'setAsDefault')
            ->middleware('permission:templates.set_default')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.set_default');
    });
    
    // Area management routes grouped by controller and permissions
    Route::controller(AreaController::class)->group(function () {
        Route::get('areas', 'index')
            ->middleware('permission:areas.index')
            ->name('areas.index');
            
        Route::get('areas/create', 'create')
            ->middleware('permission:areas.create')
            ->name('areas.create');
            
        Route::post('areas', 'store')
            ->middleware('permission:areas.create')
            ->name('areas.store');
            
        Route::get('areas/{area}', 'show')
            ->middleware('permission:areas.show')
            ->name('areas.show');
            
        Route::get('areas/{area}/edit', 'edit')
            ->middleware('permission:areas.edit')
            ->name('areas.edit');
            
        Route::put('areas/{area}', 'update')
            ->middleware('permission:areas.edit')
            ->name('areas.update');
            
        Route::delete('areas/{area}', 'destroy')
            ->middleware('permission:areas.delete')
            ->name('areas.destroy');
            
        // Ruta para asignar gerente a un área
        Route::post('areas/{area}/assign-manager', 'assignManager')
            ->middleware('permission:areas.edit')
            ->name('areas.assign-manager');
    });
    
    // Provider management routes grouped by controller and permissions
    Route::controller(ProviderController::class)->group(function () {
        Route::get('providers', 'index')
            ->middleware('permission:provider.view')
            ->name('providers.index');
            
        Route::get('providers/create', 'create')
            ->middleware('permission:provider.manage')
            ->name('providers.create');
            
        Route::post('providers', 'store')
            ->middleware('permission:provider.manage')
            ->name('providers.store');
            
        Route::get('providers/{provider:uuid}', 'show')
            ->middleware('can:view,provider')
            ->name('providers.show');
            
        Route::get('providers/{provider:uuid}/edit', 'edit')
            ->middleware('can:update,provider')
            ->name('providers.edit');
            
        Route::put('providers/{provider:uuid}', 'update')
            ->middleware('can:update,provider')
            ->name('providers.update');
            
        Route::patch('providers/{provider:uuid}/toggle-active', 'toggleActive')
            ->middleware('can:toggleActive,provider')
            ->name('providers.toggle_active');
            
        Route::post('providers/{provider:uuid}/reset-password', 'resetPassword')
            ->middleware('can:resetPassword,provider')
            ->name('providers.reset_password');
    });
    
    // Employee management routes grouped by controller and permissions
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('employees', 'index')
            ->middleware('permission:employee.view')
            ->name('employees.index');
            
        Route::get('employees/create', 'create')
            ->middleware('permission:employee.manage|employee.manage_own_provider')
            ->name('employees.create');
            
        Route::post('employees', 'store')
            ->middleware('permission:employee.manage|employee.manage_own_provider')
            ->name('employees.store');
            
        Route::get('employees/{employee}', 'show')
            ->middleware('can:view,employee')
            ->name('employees.show');
            
        Route::get('employees/{employee}/edit', 'edit')
            ->middleware('can:update,employee')
            ->name('employees.edit');
            
        Route::put('employees/{employee}', 'update')
            ->middleware('can:update,employee')
            ->name('employees.update');
            
        Route::patch('employees/{employee}/toggle-active', 'toggleActive')
            ->middleware('can:toggleActive,employee')
            ->name('employees.toggle_active');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Define fallback route for 404 errors with custom handling
Route::fallback(function () {
    return Inertia::render('errors/404');
});
