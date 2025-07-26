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
     * Solo proveedores internos de Gerencia de Tecnologia y Gerencia de Seguridad sin usuarios asociados.
     */
    public function run(): void
    {
        // Obtener solo las áreas de Tecnología y Seguridad
        $areas = Area::whereIn('name', ['Gerencia de Tecnologia', 'Gerencia de Seguridad'])->get();
        $this->command->info("Encontradas {$areas->count()} áreas disponibles");
        
        // Para cada área, crear solo el proveedor interno sin usuario asociado
        foreach ($areas as $area) {
            /* Comentado según requerimiento - No crear usuarios gerentes
            $managerName = "Gerente {$area->name}";
            $managerEmail = "gerente." . strtolower(str_replace(' ', '.', $area->name)) . "@acredita.test";
            
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
            */
            
            // Verificar si ya existe un proveedor para esta área
            if (Provider::where('area_id', $area->id)->where('type', 'internal')->exists()) {
                $this->command->info("Ya existe un proveedor interno para el área {$area->name}");
                continue;
            }
            
            // Crear proveedor interno sin usuario asociado
            Provider::create([
                'area_id' => $area->id,
                'user_id' => null, // Sin usuario asociado
                'name' => 'Gerencia ' . $area->name,
                'rif' => 'INTERNAL-' . $area->id,
                'type' => 'internal',
                'active' => true,
            ]);
            
            $this->command->info("Proveedor interno creado para el área {$area->name} sin usuario asociado");
        }
    }
}
