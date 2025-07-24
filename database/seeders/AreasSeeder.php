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
                'color' => '#3B82F6', // Azul
            ],
            [
                'code' => 'SEG',
                'name' => 'Gerencia de Seguridad',
                'description' => 'Responsable de la seguridad fisica y logica de las instalaciones',
                'active' => true,
                'color' => '#EF4444', // Rojo
            ],
            [
                'code' => 'PREN',
                'name' => 'Gerencia de Prensa',
                'description' => 'Responsable de la comunicación y relaciones públicas',
                'active' => true,
                'color' => '#F59E0B', // Naranja
            ],
            [
                'code' => 'RRHH',
                'name' => 'Gerencia de Recursos Humanos',
                'description' => 'Responsable de la gestión del personal',
                'active' => true,
                'color' => '#10B981', // Verde
            ],
            [
                'code' => 'LOG',
                'name' => 'Gerencia de Logistica',
                'description' => 'Responsable de la logistica y operaciones',
                'active' => true,
                'color' => '#6366F1', // Índigo
            ],
            [
                'code' => 'MKT',
                'name' => 'Gerencia de Marketing',
                'description' => 'Responsable de la promoción y marketing',
                'active' => true,
                'color' => '#8B5CF6', // Violeta
            ],
            [
                'code' => 'JUR',
                'name' => 'Gerencia Juridica',
                'description' => 'Responsable de aspectos legales y regulatorios',
                'active' => true,
                'color' => '#EC4899', // Rosa
            ],
            [
                'code' => 'FIN',
                'name' => 'Gerencia Financiera',
                'description' => 'Responsable de la gestión financiera y contable',
                'active' => true,
                'color' => '#14B8A6', // Verde azulado
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
                'color' => $areaData['color'],
            ]);
        }
        
        $this->command->info('Se crearon ' . count($areas) . ' áreas');
    }
}
