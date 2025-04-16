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

        // Update cache to know about the newly created permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create admin role and assign all permissions
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());
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
