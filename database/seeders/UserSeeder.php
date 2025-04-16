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
        ]);
        $adminUser->assignRole('admin');
        
        // Create regular users (79 active with random roles, 20 inactive with random roles)
        User::factory(79)->withRandomRole()->create();
        User::factory(20)->inactive()->withRandomRole()->create();
    }
}
