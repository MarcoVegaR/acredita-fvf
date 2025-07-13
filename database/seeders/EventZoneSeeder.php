<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Zone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first event
        $event = Event::first();
        
        if ($event) {
            // Get all zones
            $zoneIds = Zone::pluck('id')->toArray();
            
            // Sync all zones with the event
            $event->zones()->sync($zoneIds);
        }
    }
}
