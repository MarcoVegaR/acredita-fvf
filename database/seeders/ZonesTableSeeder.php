<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'code' => '1',
                'name' => 'Campo de Juego',
                'description' => null,
            ],
            [
                'code' => '2',
                'name' => 'Areas de Competicion',
                'description' => null,
            ],
            [
                'code' => '3',
                'name' => 'Areas de Circulacion',
                'description' => null,
            ],
            [
                'code' => '4',
                'name' => 'Areas de Operaciones',
                'description' => null,
            ],
            [
                'code' => '5',
                'name' => 'Palco Presidencial',
                'description' => null,
            ],
            [
                'code' => '6',
                'name' => 'Palcos Vinotinto Club',
                'description' => null,
            ],
            [
                'code' => '7',
                'name' => 'Area de Prensa o Medios',
                'description' => null,
            ],
            [
                'code' => '8',
                'name' => 'Area de Broadcast',
                'description' => null,
            ],
            [
                'code' => '9',
                'name' => 'Hospitalidad',
                'description' => null,
            ],
        ];

        foreach ($zones as $zone) {
            Zone::create($zone);
        }
    }
}
