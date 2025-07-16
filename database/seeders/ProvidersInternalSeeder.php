<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ProvidersInternalSeeder extends Seeder
{
    /**
     * Seed the internal providers.
     * This will create area manager users and internal providers for them.
     */
    public function run(): void
    {
        // Obtener todas las áreas
        $areas = Area::all();
        $this->command->info("Encontradas {$areas->count()} áreas disponibles");
        
        // Para cada área, crear un usuario gerente y su proveedor interno
        foreach ($areas as $area) {
            // Crear un usuario con rol de gerente de área
            $managerName = "Gerente {$area->name}";
            $managerEmail = "gerente." . strtolower(str_replace(' ', '.', $area->name)) . "@acredita.test";
            
            // Verificar si ya existe un usuario con este email
            $existingUser = User::where('email', $managerEmail)->first();
            
            if ($existingUser) {
                $managerUser = $existingUser;
                $this->command->info("Usuario gerente ya existe para el área {$area->name}");
            } else {
                $managerUser = User::factory()->create([
                    'name' => $managerName,
                    'email' => $managerEmail,
                ]);
                $managerUser->assignRole('area_manager');
                $this->command->info("Creado usuario gerente para el área {$area->name}");
            }
            
            // Verificar si ya tiene un proveedor
            if (Provider::where('user_id', $managerUser->id)->exists()) {
                $this->command->info("El usuario {$managerUser->name} ya tiene un proveedor asociado");
                continue;
            }
            
            // Create internal provider for this area manager and set the user as area manager
            DB::transaction(function () use ($area, $managerUser) {
                // Create internal provider
                Provider::create([
                    'area_id' => $area->id,
                    'user_id' => $managerUser->id,
                    'name' => 'Gerencia ' . $area->name,
                    'rif' => 'INTERNAL-' . $area->id,
                    'type' => 'internal',
                    'active' => true,
                ]);
                
                // Set this user as the manager of the area
                $area->manager_user_id = $managerUser->id;
                $area->save();
                
                $this->command->info("Proveedor interno creado para el área {$area->name} con gerente {$managerUser->name}");
            });
        }
    }
}
