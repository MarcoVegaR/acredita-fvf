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
        
        // Call areas seeder
        $this->call(AreasSeeder::class);
        
        // Call templates seeder (después de eventos y zonas)
        $this->call(TemplatesTableSeeder::class);
        
        // Call providers seeders (después de áreas y usuarios)
        $this->call(ProviderPermissionsSeeder::class);
        $this->call(ProvidersInternalSeeder::class);
        $this->call(ProvidersExternalSeeder::class);
        
        // Call employees seeder (después de proveedores)
        $this->call(EmployeesSeeder::class);
    }
}
