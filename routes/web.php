<?php

use App\Http\Controllers\RoleController;
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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Define fallback route for 404 errors with custom handling
Route::fallback(function () {
    return Inertia::render('errors/404');
});
