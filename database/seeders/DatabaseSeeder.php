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
        
        // Call the user seeder
        $this->call(UserSeeder::class);
        
        // Call document types seeder
        $this->call(DocumentTypesSeeder::class);
        
        // Call image types seeder
        $this->call(ImageTypesSeeder::class);
        
        // Call events and zones seeders
        $this->call(EventsTableSeeder::class);
        $this->call(ZonesTableSeeder::class);
        $this->call(EventZoneSeeder::class);
    }
}
