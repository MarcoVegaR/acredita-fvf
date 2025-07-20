<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CredentialPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'credential.view' => 'Ver credenciales',
            'credential.download' => 'Descargar credenciales',
            'credential.regenerate' => 'Regenerar credenciales'
        ];

        // Crear permisos
        foreach ($permissions as $permission => $description) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Asignar permisos a roles
        $roles = [
            'admin' => [
                'credential.view',
                'credential.download', 
                'credential.regenerate'
            ],
            'area_manager' => [
                'credential.view',
                'credential.download'
            ],
            'editor' => [
                'credential.view',
                'credential.download'
            ],
            'provider' => [
                'credential.view',
                'credential.download'
            ],
            'viewer' => [
                'credential.view'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($rolePermissions as $permission) {
                    $permissionModel = Permission::where('name', $permission)->first();
                    if ($permissionModel && !$role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        $this->command->info('Permisos de credenciales creados y asignados exitosamente.');
    }
}
