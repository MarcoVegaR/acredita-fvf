<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos iniciales para áreas/gerencias de la FVF
        $areas = [
            [
                'code' => 'TEC',
                'name' => 'Gerencia de Tecnologia',
                'description' => 'Responsable de la infraestructura tecnologica y sistemas de la FVF',
                'active' => true,
            ],
            [
                'code' => 'SEG',
                'name' => 'Gerencia de Seguridad',
                'description' => 'Responsable de la seguridad fisica y logica de las instalaciones',
                'active' => true,
            ],
            [
                'code' => 'PREN',
                'name' => 'Gerencia de Prensa',
                'description' => 'Responsable de la comunicación y relaciones públicas',
                'active' => true,
            ],
            [
                'code' => 'RRHH',
                'name' => 'Gerencia de Recursos Humanos',
                'description' => 'Responsable de la gestión del personal',
                'active' => true,
            ],
            [
                'code' => 'LOG',
                'name' => 'Gerencia de Logistica',
                'description' => 'Responsable de la logistica y operaciones',
                'active' => true,
            ],
            [
                'code' => 'MKT',
                'name' => 'Gerencia de Marketing',
                'description' => 'Responsable de la promoción y marketing',
                'active' => true,
            ],
            [
                'code' => 'JUR',
                'name' => 'Gerencia Juridica',
                'description' => 'Responsable de aspectos legales y regulatorios',
                'active' => true,
            ],
            [
                'code' => 'FIN',
                'name' => 'Gerencia Financiera',
                'description' => 'Responsable de la gestión financiera y contable',
                'active' => true,
            ],
        ];

        // Crear las áreas en la base de datos
        foreach ($areas as $areaData) {
            Area::create([
                'uuid' => Str::uuid()->toString(),
                'code' => $areaData['code'],
                'name' => $areaData['name'],
                'description' => $areaData['description'],
                'active' => $areaData['active'],
            ]);
        }
        
        $this->command->info('Se crearon ' . count($areas) . ' áreas');
    }
}
