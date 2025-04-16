<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the roles and permissions seeder first
        $this->call(RolesAndPermissionsSeeder::class);
        
        // Create a test admin user (será el ID=1)
        $adminUser = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@mailinator.com',
        ]);
        $adminUser->assignRole('admin');
        
        // Create regular users
        User::factory(100)->create();
    }
}
