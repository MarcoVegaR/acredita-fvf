<?php

namespace Database\Seeders;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'name' => 'Venezuela vs Colombia  9 de Septiembre del 2025',
            'description' => 'Jornada 18 Venezuela vs Colombia Eliminatorias al mundial 2026',
            'start_date' => Carbon::createFromFormat('d/m/Y', '09/09/2025'),
            'end_date' => Carbon::createFromFormat('d/m/Y', '09/09/2025'),
            'active' => true,
        ]);
    }
}
