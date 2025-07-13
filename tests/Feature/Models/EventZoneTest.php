<?php

use App\Models\Event;
use App\Models\Zone;
use Database\Seeders\EventsTableSeeder;
use Database\Seeders\ZonesTableSeeder;
use Database\Seeders\EventZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('event has zones relationship', function () {
    // Crear evento y zonas
    $event = Event::factory()->create();
    $zones = Zone::factory()->count(3)->create();
    
    // Asociar zonas al evento
    $event->zones()->attach($zones->pluck('id')->toArray());
    
    // Comprobar que el evento tiene las zonas asociadas
    expect($event->zones()->count())->toBe(3);
    expect($event->zones->pluck('id')->toArray())->toEqual($zones->pluck('id')->toArray());
});

test('zone has events relationship', function () {
    // Crear zona y eventos
    $zone = Zone::factory()->create();
    $events = Event::factory()->count(2)->create();
    
    // Asociar eventos a la zona
    $zone->events()->attach($events->pluck('id')->toArray());
    
    // Comprobar que la zona tiene los eventos asociados
    expect($zone->events()->count())->toBe(2);
    expect($zone->events->pluck('id')->toArray())->toEqual($events->pluck('id')->toArray());
});

test('events and zones seeders work correctly', function () {
    // Ejecutar los seeders
    $this->seed(EventsTableSeeder::class);
    $this->seed(ZonesTableSeeder::class);
    $this->seed(EventZoneSeeder::class);
    
    // Verificar que se creó el evento Venezuela vs Colombia
    $event = Event::where('name', 'like', '%Venezuela vs Colombia%')->first();
    expect($event)->not->toBeNull();
    expect($event->description)->toContain('Eliminatorias al mundial 2026');
    expect($event->active)->toBeTrue();
    
    // Verificar que se crearon las 9 zonas
    expect(Zone::count())->toBe(9);
    
    // Verificar que la tabla pivote tiene registros (un evento con 9 zonas)
    $pivotCount = DB::table('event_zone')->count();
    expect($pivotCount)->toBe(9);
    
    // Verificar que el evento tiene todas las zonas asociadas
    expect($event->zones()->count())->toBe(9);
});

test('event_zone relationship is working correctly after seeding', function () {
    // Ejecutar los seeders
    $this->seed(EventsTableSeeder::class);
    $this->seed(ZonesTableSeeder::class);
    $this->seed(EventZoneSeeder::class);
    
    // Obtener el primer evento
    $event = Event::first();
    expect($event)->not->toBeNull();
    
    // Verificar que el evento tiene todas las zonas
    $zoneIds = Zone::pluck('id')->toArray();
    $eventZoneIds = $event->zones->pluck('id')->toArray();
    expect($eventZoneIds)->toEqual($zoneIds);
});
