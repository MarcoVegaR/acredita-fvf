<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PrintBatchPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permiso para gestión de lotes de impresión
        $permission = Permission::firstOrCreate([
            'name' => 'print_batch.manage',
            'guard_name' => 'web'
        ]);

        // Asignar permiso al rol admin
        $adminRole = Role::findByName('admin');
        if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        // Asignar permiso al rol security_manager
        $securityManagerRole = Role::findByName('security_manager');
        if ($securityManagerRole && !$securityManagerRole->hasPermissionTo($permission)) {
            $securityManagerRole->givePermissionTo($permission);
        }

        $this->command->info('✅ Permiso print_batch.manage creado y asignado a los roles admin y security_manager');
    }
}
