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
        
        // Event management permissions
        $this->createPermission('events.index', 'Listar eventos');
        $this->createPermission('events.show', 'Ver detalles de evento');
        $this->createPermission('events.create', 'Crear eventos');
        $this->createPermission('events.edit', 'Editar eventos');
        $this->createPermission('events.delete', 'Eliminar eventos');
        
        // Zone management permissions
        $this->createPermission('zones.index', 'Listar zonas');
        $this->createPermission('zones.show', 'Ver detalles de zona');
        $this->createPermission('zones.create', 'Crear zonas');
        $this->createPermission('zones.edit', 'Editar zonas');
        $this->createPermission('zones.delete', 'Eliminar zonas');
        
        // Template management permissions
        $this->createPermission('templates.index', 'Listar plantillas');
        $this->createPermission('templates.show', 'Ver detalles de plantilla');
        $this->createPermission('templates.create', 'Crear plantillas');
        $this->createPermission('templates.edit', 'Editar plantillas');
        $this->createPermission('templates.delete', 'Eliminar plantillas');
        $this->createPermission('templates.set_default', 'Establecer plantilla predeterminada');
        $this->createPermission('templates.regenerate', 'Regenerar credenciales con plantilla');
        
        // Provider management permissions
        $this->createPermission('provider.view', 'Ver proveedores');
        $this->createPermission('provider.manage', 'Gestionar proveedores');
        $this->createPermission('provider.manage_own_area', 'Gestionar proveedores del área');
        
        // Employee management permissions
        $this->createPermission('employee.view', 'Ver empleados de proveedores');
        $this->createPermission('employee.manage', 'Gestionar empleados de proveedores');
        $this->createPermission('employee.manage_own_provider', 'Gestionar empleados del propio proveedor');
        
        // Accreditation Request permissions
        $this->createPermission('accreditation_request.index', 'Listar solicitudes de acreditación');
        $this->createPermission('accreditation_request.view', 'Ver solicitud de acreditación');
        $this->createPermission('accreditation_request.create', 'Crear solicitudes de acreditación');
        $this->createPermission('accreditation_request.update', 'Editar solicitudes de acreditación');
        $this->createPermission('accreditation_request.delete', 'Eliminar solicitudes de acreditación');
        $this->createPermission('accreditation_request.submit', 'Enviar solicitudes de acreditación');
        $this->createPermission('accreditation_request.approve', 'Aprobar solicitudes de acreditación');
        $this->createPermission('accreditation_request.reject', 'Rechazar solicitudes de acreditación');
        $this->createPermission('accreditation_request.return', 'Devolver solicitudes a borrador');
        $this->createPermission('accreditation_request.review', 'Dar visto bueno a solicitudes');
        
        // Credential management permissions
        $this->createPermission('credential.view', 'Ver credenciales');
        $this->createPermission('credential.download', 'Descargar credenciales');
        $this->createPermission('credential.regenerate', 'Regenerar credenciales fallidas');
        $this->createPermission('credentials.regenerate', 'Regenerar credenciales con nueva plantilla');
        $this->createPermission('credential.preview', 'Previsualizar credenciales');

        // Update cache to know about the newly created permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        
        // ADMIN ROLE - Acceso completo al sistema
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // AREA_MANAGER ROLE - Gestión de su área y visto bueno de solicitudes
        // Puede dar visto bueno, devolver solicitudes, ver todas las de su área
        $areaManagerRole = Role::create(['name' => 'area_manager']);
        $areaManagerRole->givePermissionTo([
            // Gestión de proveedores (solo su área)
            'provider.view', 'provider.manage_own_area',
            
            // Gestión de empleados (solo su área)
            'employee.view', 'employee.manage',
            
            // Gestión de eventos y zonas (lectura)
            'events.index', 'events.show',
            'zones.index', 'zones.show',
            
            // Plantillas (lectura)
            'templates.index', 'templates.show',
            
            // Solicitudes de acreditación (gestión completa de su área)
            'accreditation_request.index', 'accreditation_request.view',
            'accreditation_request.create', 'accreditation_request.update', 
            'accreditation_request.delete', 'accreditation_request.submit',
            'accreditation_request.review', 'accreditation_request.return',
            
            // Credenciales (solo visualización)
            'credential.view', 'credential.preview'
            // Los permisos se filtran por área a través de la policy
        ]);
        
        // PROVIDER ROLE - Gestión de empleados y solicitudes del proveedor
        // Solo puede crear borradores, editarlos, eliminarlos y enviarlos
        $providerRole = Role::create(['name' => 'provider']);
        $providerRole->givePermissionTo([
            // Gestión de empleados de su propio proveedor
            'employee.view', 'employee.manage_own_provider',
            
            // Gestión de eventos y zonas (solo lectura necesaria)
            'events.index', 'events.show',
            'zones.index', 'zones.show',
            
            // Plantillas (solo lectura)
            'templates.index', 'templates.show',
            
            // Solicitudes de acreditación (gestión de su propio proveedor)
            'accreditation_request.index', 'accreditation_request.view',
            'accreditation_request.create', 'accreditation_request.update',
            'accreditation_request.delete', 'accreditation_request.submit',
            
            // Credenciales (visualización y descarga de sus propias credenciales)
            'credential.view', 'credential.download', 'credential.preview'
            // Los permisos se filtran por proveedor a través de la policy
        ]);
        
    }

    /**
     * Create a permission with both technical name and display name
     */
    private function createPermission(string $name, string $description = '')
    {
        Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web'
        ]);
    }
}
