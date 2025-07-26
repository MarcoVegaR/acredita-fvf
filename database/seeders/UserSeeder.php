<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test admin user (será el ID=1)
        $adminUser = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@mailinator.com',
            'password' => bcrypt('12345678'),
        ]);
        $adminUser->assignRole('admin');
        
        // Crear usuario Security Manager
        $securityManagerUser = User::factory()->create([
            'name' => 'Jorman Salazar',
            'email' => 'jorman.salazar@fvf.com.ve',
            'password' => bcrypt('12345678'),
        ]);
        $securityManagerUser->assignRole('security_manager');
        
        // Crear usuario Admin adicional
        $admin2User = User::factory()->create([
            'name' => 'Wilman Sánchez',
            'email' => 'wilman.sanchez@fvf.com.ve',
            'password' => bcrypt('12345678'),
        ]);
        $admin2User->assignRole('admin');
        
        // Create regular users (79 active with random roles, 20 inactive with random roles)
        //User::factory(79)->withRandomRole()->create();
        //User::factory(20)->inactive()->withRandomRole()->create();
    }
}
