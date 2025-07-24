<?php

use App\Http\Controllers\AccreditationRequestController;
use App\Http\Controllers\AccreditationRequestBulkController;
use App\Http\Controllers\AccreditationRequestDraftController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\PrintBatchController;
use Illuminate\Http\Request;
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
            
        Route::post('templates/{template}/regenerate-credentials', 'regenerateCredentials')
            ->middleware('permission:credentials.regenerate')
            ->where('template', '[0-9]+|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
            ->name('templates.regenerate_credentials');
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
    
    // Accreditation Request routes grouped by controller and permissions
    // Accreditation Request Draft routes for wizard workflow
    Route::controller(AccreditationRequestDraftController::class)->group(function () {
        // Paso 1: Selección de evento
        Route::get('accreditation-requests/create/step-1', 'wizardStep1')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.wizard.step1');
            
        Route::post('accreditation-requests/create/step-1', function(Request $request) {
            return app(AccreditationRequestDraftController::class)->storeStep($request, 1, 2);
        })->middleware('permission:accreditation_request.create')
          ->name('accreditation-requests.wizard.store1');
            
        // Paso 2: Selección de empleado
        Route::get('accreditation-requests/create/step-2', 'wizardStep2')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.wizard.step2');
            
        Route::post('accreditation-requests/create/step-2', function(Request $request) {
            return app(AccreditationRequestDraftController::class)->storeStep($request, 2, 3);
        })->middleware('permission:accreditation_request.create')
          ->name('accreditation-requests.wizard.store2');
            
        // Paso 3: Selección de zonas
        Route::get('accreditation-requests/create/step-3', 'wizardStep3')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.wizard.step3');
            
        Route::post('accreditation-requests/create/step-3', function(Request $request) {
            return app(AccreditationRequestDraftController::class)->storeStep($request, 3, 4);
        })->middleware('permission:accreditation_request.create')
          ->name('accreditation-requests.wizard.store3');
            
        // Paso 4: Confirmación y comentarios adicionales
        Route::get('accreditation-requests/create/step-4', 'wizardStep4')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.wizard.step4');
            
        Route::post('accreditation-requests/create/step-4', function(Request $request) {
            return app(AccreditationRequestDraftController::class)->storeStep($request, 4, 5);
        })->middleware('permission:accreditation_request.create')
          ->name('accreditation-requests.wizard.store4');
            
        // Guardar la solicitud completa
        Route::post('accreditation-requests', 'store')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.store');
    });

    Route::controller(AccreditationRequestController::class)->group(function () {
        // Listar todas las solicitudes (con filtros)
        Route::get('accreditation-requests', 'index')
            ->middleware('permission:accreditation_request.index')
            ->name('accreditation-requests.index');
            
        // Crear nueva solicitud (redirección al wizard)
        Route::get('accreditation-requests/create', function() {
            return redirect()->route('accreditation-requests.wizard.step1');
        })->middleware('permission:accreditation_request.create')
          ->name('accreditation-requests.create');

        // Editar una solicitud existente (solo en estado borrador)
        Route::get('accreditation-requests/{accreditation_request:uuid}/edit', 'edit')
            ->middleware('permission:accreditation_request.update')
            ->name('accreditation-requests.edit');
            
        Route::put('accreditation-requests/{accreditation_request:uuid}', 'update')
            ->middleware('permission:accreditation_request.update')
            ->name('accreditation-requests.update');
            
        // Enviar la solicitud para aprobación
        Route::post('accreditation-requests/{accreditation_request:uuid}/submit', 'submit')
            ->middleware('permission:accreditation_request.submit')
            ->name('accreditation-requests.submit');
            
        // Aprobar la solicitud
        Route::post('accreditation-requests/{accreditation_request:uuid}/approve', 'approve')
            ->middleware('permission:accreditation_request.approve')
            ->name('accreditation-requests.approve');
            
        // Rechazar la solicitud
        Route::post('accreditation-requests/{accreditation_request:uuid}/reject', 'reject')
            ->middleware('permission:accreditation_request.reject')
            ->name('accreditation-requests.reject');
            
        // Devolver a borrador para corrección
        Route::post('accreditation-requests/{accreditation_request:uuid}/return-to-draft', 'returnToDraft')
            ->middleware('permission:accreditation_request.return')
            ->name('accreditation-requests.return-to-draft');
            
        // Suspender una credencial aprobada
        Route::post('accreditation-requests/{accreditation_request:uuid}/suspend', 'suspend')
            ->middleware('permission:accreditation_request.approve')
            ->name('accreditation-requests.suspend');
            
        // Dar visto bueno (area manager)
        Route::post('accreditation-requests/{accreditation_request:uuid}/review', 'review')
            ->middleware('permission:accreditation_request.review')
            ->name('accreditation-requests.review');
            
        // Eliminar una solicitud (solo en estado borrador)
        Route::delete('accreditation-requests/{accreditation_request:uuid}', 'destroy')
            ->middleware('permission:accreditation_request.delete')
            ->name('accreditation-requests.destroy');

        // Ver detalles de una solicitud específica (SIEMPRE AL FINAL)
        Route::get('accreditation-requests/{accreditation_request:uuid}', 'show')
            ->middleware('permission:accreditation_request.view')
            ->name('accreditation-requests.show');
    });
        
    // Credential management routes
    Route::controller(\App\Http\Controllers\CredentialController::class)->group(function () {
        // Removed redundant credential show route - use tabs instead
        
        Route::get('accreditation-requests/{request:uuid}/credential/preview', 'preview')
            ->name('accreditation-requests.credential.preview');
            
        Route::get('accreditation-requests/{request:uuid}/credential/download/image', 'downloadImage')
            ->name('accreditation-requests.credential.download.image');
            
        Route::get('accreditation-requests/{request:uuid}/credential/download/pdf', 'downloadPdf')
            ->name('accreditation-requests.credential.download.pdf');
            
        Route::get('accreditation-requests/{request:uuid}/credential/status', 'status')
            ->name('accreditation-requests.credential.status');
            
        Route::post('accreditation-requests/{request:uuid}/credential/regenerate', 'regenerate')
            ->name('accreditation-requests.credential.regenerate');
    });
    
    // Rutas para solicitudes masivas de acreditación
    Route::controller(AccreditationRequestBulkController::class)->group(function () {
        // Paso 1: Selección de evento
        Route::get('accreditation-requests/bulk/step-1', 'step1')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-1');
            
        Route::post('accreditation-requests/bulk/step-1', 'step2')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-2');
            
        // Paso 2: Selección de empleados
        Route::get('accreditation-requests/bulk/step-2', 'showStep2')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-2.show');
            
        Route::post('accreditation-requests/bulk/step-2', 'step3')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-3');
            
        // Paso 3: Configuración de zonas
        Route::get('accreditation-requests/bulk/step-3', 'showStep3')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-3.show');
            
        Route::post('accreditation-requests/bulk/step-3', 'step4')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-4');
            
        // Paso 4: Confirmación y creación
        Route::get('accreditation-requests/bulk/step-4', 'showStep4')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.step-4.show');
            
        Route::post('accreditation-requests/bulk/step-4', 'store')
            ->middleware('permission:accreditation_request.create')
            ->name('accreditation-requests.bulk.store');
    });

    // Print Batch management routes
    Route::controller(PrintBatchController::class)->group(function () {
        // Listar lotes de impresión
        Route::get('print-batches', 'index')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.index');
            
        // Crear nuevo lote
        Route::get('print-batches/create', 'create')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.create');
            
        // Vista previa de lote
        Route::post('print-batches/preview', 'preview')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.preview');
            
        Route::post('print-batches', 'store')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.store');
            
        // Ver detalles del lote
        Route::get('print-batches/{printBatch:uuid}', 'show')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.show');
            
        // Descargar PDF del lote
        Route::get('print-batches/{printBatch:uuid}/download', 'download')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.download');
            
        // Reintentar lote fallido
        Route::post('print-batches/{printBatch:uuid}/retry', 'retry')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.retry');
            
        // API endpoints para frontend
        Route::get('api/print-batches/processing', 'processing')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.processing');
            
        Route::post('api/print-batches/preview', 'preview')
            ->middleware('permission:print_batch.manage')
            ->name('print-batches.api.preview');
    });
}); // Close auth middleware group

// Rutas públicas para verificación de QR (sin autenticación)
use App\Http\Controllers\QRVerificationController;

// Página web para verificar credenciales por QR (usa Inertia)
Route::get('/verify-qr', [QRVerificationController::class, 'page'])
    ->name('verify-qr')
    ->middleware(['throttle:120,1']); // Rate limiting: 120 requests per minute

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
Route::middleware(['throttle:30,1'])->group(function () {
    Route::get('/verify/{qrCode}', [\App\Http\Controllers\CredentialController::class, 'verify'])
         ->name('credentials.verify');
});

// Test de permisos
Route::get('/test-permissions', function () {
    $user = Auth::user();
    
    if (!$user) {
        return response()->json(['error' => 'Usuario no logueado']);
    }
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ],
        'credential_permissions' => [
            'credential.view' => $user->can('credential.view'),
            'credential.download' => $user->can('credential.download'),
            'credential.preview' => $user->can('credential.preview'),
            'credential.regenerate' => $user->can('credential.regenerate'),
        ]
    ]);
})->middleware('auth');

// Define fallback route for 404 errors with custom handling
Route::fallback(function () {
    return Inertia::render('errors/404');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
