<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class EmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = Provider::with('area')->get();
        
        if ($providers->isEmpty()) {
            $this->command->warn('No se encontraron proveedores. Ejecuta los seeders de proveedores primero.');
            return;
        }

        $this->command->info("Encontrados {$providers->count()} proveedores disponibles");

        // Comentado datos base para empleados - no se crean empleados según solicitud
        $employeeTemplates = [
            /*
            [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'document_type' => 'V',
                'document_number' => '12345678',
                'function' => 'Técnico'
            ],
            [
                'first_name' => 'María',
                'last_name' => 'González',
                'document_type' => 'V',
                'document_number' => '23456789',
                'function' => 'Coordinadora'
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'document_type' => 'V',
                'document_number' => '34567890',
                'function' => 'Supervisor'
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'document_type' => 'V',
                'document_number' => '45678901',
                'function' => 'Especialista'
            ]
            */
        ];

        $employeeCounter = 0;

        /* Comentado bucle de creación de empleados según solicitud
        foreach ($providers as $provider) {
            // Crear 2-4 empleados por proveedor
            $numEmployees = rand(2, 4);
            
            for ($i = 0; $i < $numEmployees; $i++) {
                $template = $employeeTemplates[$i % count($employeeTemplates)];
                
                // Personalizar datos según el proveedor
                $employeeData = [
                    'provider_id' => $provider->id,
                    'first_name' => $template['first_name'],
                    'last_name' => $template['last_name'] . ' ' . ($employeeCounter + 1),
                    'document_type' => $template['document_type'],
                    'document_number' => str_pad($template['document_number'] + $employeeCounter, 8, '0', STR_PAD_LEFT),
                    'function' => $template['function'] . ' de ' . $provider->area->name,
                    'active' => true,
                ];

                $employee = Employee::create($employeeData);
                $employeeCounter++;

                $this->command->info("Empleado creado: {$employee->first_name} {$employee->last_name} para {$provider->name} ({$provider->type})");
            }
        }
        */

        $this->command->info("Total de empleados creados: 0 (Creación de empleados comentada)");
    }
}
