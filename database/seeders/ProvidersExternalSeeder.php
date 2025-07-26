<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProvidersExternalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::all();
        
        if ($areas->isEmpty()) {
            $this->command->warn('No se encontraron áreas. Ejecuta AreasSeeder primero.');
            return;
        }

        $this->command->info("Encontradas {$areas->count()} áreas disponibles");

        // Proveedores externos comentados según solicitud
        $externalProviders = [
            /*
            [
                'name' => 'Seguridad Total S.A.',
                'rif' => 'J-40123456-7',
                'phone' => '+58-212-555-0001',
                'area' => 'Gerencia de Seguridad',
                'contact_name' => 'Carlos Seguridad',
                'contact_email' => 'carlos.seguridad@seguridadtotal.com'
            ],
            [
                'name' => 'TechSolutions C.A.',
                'rif' => 'J-40234567-8',
                'phone' => '+58-212-555-0002',
                'area' => 'Gerencia de Tecnologia',
                'contact_name' => 'Ana Tecnología',
                'contact_email' => 'ana.tech@techsolutions.com'
            ],
            [
                'name' => 'Logística Express',
                'rif' => 'J-40345678-9',
                'phone' => '+58-212-555-0003',
                'area' => 'Gerencia de Logistica',
                'contact_name' => 'Miguel Logística',
                'contact_email' => 'miguel.log@logisticaexpress.com'
            ],
            [
                'name' => 'Marketing Pro',
                'rif' => 'J-40456789-0',
                'phone' => '+58-212-555-0004',
                'area' => 'Gerencia de Marketing',
                'contact_name' => 'Laura Marketing',
                'contact_email' => 'laura.mkt@marketingpro.com'
            ],
            [
                'name' => 'Comunicaciones Media',
                'rif' => 'J-40567890-1',
                'phone' => '+58-212-555-0005',
                'area' => 'Gerencia de Prensa',
                'contact_name' => 'Roberto Prensa',
                'contact_email' => 'roberto.prensa@comunicacionesmedia.com'
            ]
            */
        ];

        foreach ($externalProviders as $providerData) {
            $area = $areas->where('name', $providerData['area'])->first();
            
            if (!$area) {
                $this->command->warn("Área '{$providerData['area']}' no encontrada. Saltando proveedor {$providerData['name']}");
                continue;
            }

            // Verificar si el usuario ya existe
            $contactUser = User::where('email', $providerData['contact_email'])->first();
            
            if (!$contactUser) {
                // Crear usuario de contacto para el proveedor externo
                $contactUser = User::create([
                    'name' => $providerData['contact_name'],
                    'email' => $providerData['contact_email'],
                    'password' => Hash::make('12345678'),
                    'email_verified_at' => now(),
                ]);
                
                // Asignar rol de proveedor
                $contactUser->assignRole('provider');
            }

            // Crear el proveedor externo
            $provider = Provider::create([
                'area_id' => $area->id,
                'user_id' => $contactUser->id,
                'name' => $providerData['name'],
                'rif' => $providerData['rif'],
                'phone' => $providerData['phone'],
                'type' => 'external',
                'active' => true,
            ]);

            // El usuario queda asociado al proveedor a través de la relación

            $this->command->info("Creado usuario contacto para el proveedor externo {$providerData['name']}");
            $this->command->info("Proveedor externo creado para el área {$area->name} con contacto {$providerData['contact_name']}");
        }
    }
}
