<?php

namespace Tests\Feature\Models;

use App\Models\Event;
use App\Models\Template;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Services\Template\TemplateServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_template_seeder_creates_templates_for_all_events()
    {
        // Ejecutar los seeders de eventos y plantillas
        $this->seed(\Database\Seeders\EventsTableSeeder::class);
        $this->seed(\Database\Seeders\TemplatesTableSeeder::class);
        
        // Obtener todos los eventos
        $events = Event::all();
        
        // Verificar que cada evento tiene al menos una plantilla
        foreach ($events as $event) {
            $templatesCount = Template::where('event_id', $event->id)->count();
            $this->assertGreaterThan(0, $templatesCount, "El evento {$event->name} no tiene plantillas asociadas");
            
            // Verificar que cada evento tiene una plantilla predeterminada
            $defaultTemplate = Template::where('event_id', $event->id)
                                     ->where('is_default', true)
                                     ->first();
            $this->assertNotNull($defaultTemplate, "El evento {$event->name} no tiene una plantilla predeterminada");
        }
    }
    
    public function test_template_repository_list_by_event()
    {
        // Crear un evento con dos plantillas
        $event = Event::factory()->create();
        
        Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla 1',
            'file_path' => 'templates/test1.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 20, 'y' => 20, 'width' => 35, 'height' => 45],
                'rect_qr' => ['x' => 170, 'y' => 20, 'width' => 25, 'height' => 25]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla 2',
            'file_path' => 'templates/test2.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 10, 'y' => 10, 'width' => 30, 'height' => 40],
                'rect_qr' => ['x' => 160, 'y' => 10, 'width' => 20, 'height' => 20]
            ],
            'version' => 2,
            'is_default' => false
        ]);
        
        // Usar el repositorio para listar las plantillas del evento
        $repository = $this->app->make(TemplateRepositoryInterface::class);
        $templates = $repository->listByEvent($event->id);
        
        // Verificar que se devuelven las 2 plantillas
        $this->assertCount(2, $templates);
        
        // Verificar que la plantilla predeterminada aparece primero
        $this->assertTrue($templates->first()->is_default);
    }
    
    public function test_template_service_set_as_default()
    {
        // Crear un evento con dos plantillas
        $event = Event::factory()->create();
        
        $template1 = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla 1',
            'file_path' => 'templates/test1.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 20, 'y' => 20, 'width' => 35, 'height' => 45],
                'rect_qr' => ['x' => 170, 'y' => 20, 'width' => 25, 'height' => 25]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        $template2 = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla 2',
            'file_path' => 'templates/test2.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 10, 'y' => 10, 'width' => 30, 'height' => 40],
                'rect_qr' => ['x' => 160, 'y' => 10, 'width' => 20, 'height' => 20]
            ],
            'version' => 2,
            'is_default' => false
        ]);
        
        // Usar el servicio para cambiar la plantilla predeterminada
        $service = $this->app->make(TemplateServiceInterface::class);
        $result = $service->setAsDefault($template2);
        
        // Verificar que la operación fue exitosa
        $this->assertTrue($result);
        
        // Recargar las plantillas desde la BD
        $template1->refresh();
        $template2->refresh();
        
        // Verificar que los valores de is_default se han actualizado correctamente
        $this->assertFalse($template1->is_default);
        $this->assertTrue($template2->is_default);
    }
    
    public function test_template_service_get_default_for_event()
    {
        // Crear un evento con dos plantillas
        $event = Event::factory()->create();
        
        $defaultTemplate = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla Predeterminada',
            'file_path' => 'templates/default.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 20, 'y' => 20, 'width' => 35, 'height' => 45],
                'rect_qr' => ['x' => 170, 'y' => 20, 'width' => 25, 'height' => 25]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla No Predeterminada',
            'file_path' => 'templates/nondefault.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 10, 'y' => 10, 'width' => 30, 'height' => 40],
                'rect_qr' => ['x' => 160, 'y' => 10, 'width' => 20, 'height' => 20]
            ],
            'version' => 2,
            'is_default' => false
        ]);
        
        // Usar el servicio para obtener la plantilla predeterminada
        $service = $this->app->make(TemplateServiceInterface::class);
        $template = $service->getDefaultForEvent($event->id);
        
        // Verificar que se obtiene la plantilla correcta
        $this->assertNotNull($template);
        $this->assertEquals($defaultTemplate->id, $template->id);
        $this->assertTrue($template->is_default);
        $this->assertEquals('Plantilla Predeterminada', $template->name);
    }
    
    public function test_template_layout_meta_structure()
    {
        // Ejecutar los seeders
        $this->seed(\Database\Seeders\EventsTableSeeder::class);
        $this->seed(\Database\Seeders\TemplatesTableSeeder::class);
        
        // Obtener una plantilla creada por el seeder
        $template = Template::first();
        
        // Verificar que layout_meta tiene la estructura correcta
        $this->assertIsArray($template->layout_meta);
        $this->assertArrayHasKey('fold_mm', $template->layout_meta);
        $this->assertArrayHasKey('rect_photo', $template->layout_meta);
        $this->assertArrayHasKey('rect_qr', $template->layout_meta);
        
        // Verificar que fold_mm es un número
        $this->assertEquals(139.7, $template->layout_meta['fold_mm']);
        
        // Verificar estructura de rect_photo
        $this->assertIsArray($template->layout_meta['rect_photo']);
        $this->assertArrayHasKey('x', $template->layout_meta['rect_photo']);
        $this->assertArrayHasKey('y', $template->layout_meta['rect_photo']);
        $this->assertArrayHasKey('width', $template->layout_meta['rect_photo']);
        $this->assertArrayHasKey('height', $template->layout_meta['rect_photo']);
        
        // Verificar estructura de rect_qr
        $this->assertIsArray($template->layout_meta['rect_qr']);
        $this->assertArrayHasKey('x', $template->layout_meta['rect_qr']);
        $this->assertArrayHasKey('y', $template->layout_meta['rect_qr']);
        $this->assertArrayHasKey('width', $template->layout_meta['rect_qr']);
        $this->assertArrayHasKey('height', $template->layout_meta['rect_qr']);
    }
}
