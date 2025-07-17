<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create user management permissions
        $this->createPermission('users.index', 'Listar usuarios');
        $this->createPermission('users.show', 'Ver detalles de usuario');
        $this->createPermission('users.create', 'Crear usuarios');
        $this->createPermission('users.edit', 'Editar usuarios');
        $this->createPermission('users.delete', 'Eliminar usuarios');

        // Create role management permissions
        $this->createPermission('roles.index', 'Listar roles');
        $this->createPermission('roles.show', 'Ver detalles de rol');
        $this->createPermission('roles.create', 'Crear roles');
        $this->createPermission('roles.edit', 'Editar roles');
        $this->createPermission('roles.delete', 'Eliminar roles');

        // Create area management permissions
        $this->createPermission('areas.index', 'Listar áreas');
        $this->createPermission('areas.show', 'Ver detalles de área');
        $this->createPermission('areas.create', 'Crear áreas');
        $this->createPermission('areas.edit', 'Editar áreas');
        $this->createPermission('areas.delete', 'Eliminar áreas');

        // Document management global permissions
        $this->createPermission('documents.view', 'Ver documentos');
        $this->createPermission('documents.upload', 'Subir documentos');
        $this->createPermission('documents.delete', 'Eliminar documentos');
        $this->createPermission('documents.download', 'Descargar documentos');
        
        // Document management for users module
        $this->createPermission('documents.view.users', 'Ver documentos de usuarios');
        $this->createPermission('documents.upload.users', 'Subir documentos de usuarios');
        $this->createPermission('documents.delete.users', 'Eliminar documentos de usuarios');
        $this->createPermission('documents.download.users', 'Descargar documentos de usuarios');
        
        // Image management global permissions
        $this->createPermission('images.view', 'Ver imágenes');
        $this->createPermission('images.upload', 'Subir imágenes');
        $this->createPermission('images.delete', 'Eliminar imágenes');
        
        // Image management for users module
        $this->createPermission('images.view.users', 'Ver imágenes de usuarios');
        $this->createPermission('images.upload.users', 'Subir imágenes de usuarios');
        $this->createPermission('images.delete.users', 'Eliminar imágenes de usuarios');
        
        // Template management permissions
        $this->createPermission('templates.index', 'Listar plantillas');
        $this->createPermission('templates.show', 'Ver detalles de plantilla');
        $this->createPermission('templates.create', 'Crear plantillas');
        $this->createPermission('templates.edit', 'Editar plantillas');
        $this->createPermission('templates.delete', 'Eliminar plantillas');
        $this->createPermission('templates.set_default', 'Establecer plantilla predeterminada');
        
        // Provider management permissions
        $this->createPermission('provider.view', 'Ver proveedores');
        $this->createPermission('provider.manage', 'Gestionar proveedores');
        $this->createPermission('provider.manage_own_area', 'Gestionar proveedores del área');
        
        // Employee management permissions
        $this->createPermission('employee.view', 'Ver empleados de proveedores');
        $this->createPermission('employee.manage', 'Gestionar empleados de proveedores');
        $this->createPermission('employee.manage_own_provider', 'Gestionar empleados del propio proveedor');

        // Update cache to know about the newly created permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // Create editor role with limited permissions
        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'users.index', 'users.show',
            'users.edit', // Can edit but not create or delete users
        ]);
        
        // Create viewer role with read-only permissions
        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo([
            'users.index', 'users.show',
            'roles.index', 'roles.show',
        ]);
        
        // Create area_manager role with specific permissions
        $areaManagerRole = Role::create(['name' => 'area_manager']);
        $areaManagerRole->givePermissionTo([
            'areas.index', 'areas.show',
            // Otros permisos específicos para gerentes de área se pueden añadir aquí
        ]);
        
        // Create user role with minimal permissions
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([]);
        
    }

    /**
     * Create a permission with both technical name and display name
     */
    private function createPermission(string $name, string $nameshow): void
    {
        Permission::create([
            'name' => $name,
            'nameshow' => $nameshow,
            'guard_name' => 'web'
        ]);
    }
}
