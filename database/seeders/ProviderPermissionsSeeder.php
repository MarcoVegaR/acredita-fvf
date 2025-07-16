<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProviderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Provider management permissions
        $this->createPermission('provider.view', 'Ver proveedores');
        $this->createPermission('provider.manage', 'Gestionar proveedores');
        $this->createPermission('provider.manage_own_area', 'Gestionar proveedores del área');

        // Update cache to know about the newly created permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Asignar permisos al rol admin
        if ($adminRole = Role::where('name', 'admin')->first()) {
            $adminRole->givePermissionTo([
                'provider.view',
                'provider.manage',
                'provider.manage_own_area'
            ]);
        }

        // Asignar permiso de vista al rol editor y viewer
        if ($editorRole = Role::where('name', 'editor')->first()) {
            $editorRole->givePermissionTo(['provider.view']);
        }

        if ($viewerRole = Role::where('name', 'viewer')->first()) {
            $viewerRole->givePermissionTo(['provider.view']);
        }

        // Asignar permisos de área al rol area_manager
        if ($areaManagerRole = Role::where('name', 'area_manager')->first()) {
            $areaManagerRole->givePermissionTo([
                'provider.view',
                'provider.manage_own_area'
            ]);
        }
    }

    /**
     * Create a permission with both technical name and display name
     */
    private function createPermission(string $name, string $nameshow): void
    {
        Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['nameshow' => $nameshow]
        );
    }
}
