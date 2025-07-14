<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_template_belongs_to_event()
    {
        // Crear un evento
        $event = Event::factory()->create();
        
        // Crear una plantilla asociada al evento
        $template = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla de prueba',
            'file_path' => 'templates/test.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => [
                    'x' => 20,
                    'y' => 20,
                    'width' => 35,
                    'height' => 45
                ],
                'rect_qr' => [
                    'x' => 170,
                    'y' => 20,
                    'width' => 25,
                    'height' => 25
                ]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        // Comprobar que la relación funciona correctamente
        $this->assertInstanceOf(Event::class, $template->event);
        $this->assertEquals($event->id, $template->event->id);
    }
    
    public function test_layout_meta_is_cast_to_array()
    {
        // Crear un evento
        $event = Event::factory()->create();
        
        // Crear una plantilla con layout_meta como array
        $layout = [
            'fold_mm' => 139.7,
            'rect_photo' => [
                'x' => 20,
                'y' => 20,
                'width' => 35,
                'height' => 45
            ],
            'rect_qr' => [
                'x' => 170,
                'y' => 20,
                'width' => 25,
                'height' => 25
            ]
        ];
        
        $template = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla de prueba',
            'file_path' => 'templates/test.png',
            'layout_meta' => $layout,
            'version' => 1,
            'is_default' => true
        ]);
        
        // Recargar desde la BD para comprobar el cast
        $template->refresh();
        
        // Verificar que layout_meta es un array
        $this->assertIsArray($template->layout_meta);
        $this->assertEquals($layout, $template->layout_meta);
        $this->assertEquals(139.7, $template->layout_meta['fold_mm']);
    }
    
    public function test_validate_layout_meta_method()
    {
        // Crear un evento
        $event = Event::factory()->create();
        
        // Crear una plantilla con layout_meta completo y válido
        $template = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla válida',
            'file_path' => 'templates/valid.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => [
                    'x' => 20,
                    'y' => 20,
                    'width' => 35,
                    'height' => 45
                ],
                'rect_qr' => [
                    'x' => 170,
                    'y' => 20,
                    'width' => 25,
                    'height' => 25
                ]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        // Validación debe ser exitosa
        $this->assertTrue($template->validateLayoutMeta() === true);
        
        // Crear una plantilla con layout_meta incompleto
        $templateInvalid = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla inválida',
            'file_path' => 'templates/invalid.png',
            'layout_meta' => [
                'fold_mm' => 139.7
                // Faltan rect_photo y rect_qr
            ],
            'version' => 1,
            'is_default' => false
        ]);
        
        // Validación debe fallar y retornar los campos faltantes
        $result = $templateInvalid->validateLayoutMeta();
        $this->assertIsArray($result);
        $this->assertContains('rect_photo', $result);
        $this->assertContains('rect_qr', $result);
    }
    
    public function test_only_one_default_template_per_event()
    {
        // Este test verifica que el índice parcial funciona correctamente
        
        // Crear un evento
        $event = Event::factory()->create();
        
        // Crear una primera plantilla default
        $template1 = Template::create([
            'event_id' => $event->id,
            'name' => 'Plantilla Default 1',
            'file_path' => 'templates/default1.png',
            'layout_meta' => [
                'fold_mm' => 139.7,
                'rect_photo' => ['x' => 20, 'y' => 20, 'width' => 35, 'height' => 45],
                'rect_qr' => ['x' => 170, 'y' => 20, 'width' => 25, 'height' => 25]
            ],
            'version' => 1,
            'is_default' => true
        ]);
        
        // Verificar que la primera plantilla se guardó correctamente
        $this->assertDatabaseHas('templates', [
            'id' => $template1->id,
            'is_default' => true
        ]);
        
        // En lugar de esperar una excepción específica, vamos a intentar crear otra plantilla predeterminada
        // y luego verificar que el índice parcial no permitió dos plantillas predeterminadas para el mismo evento
        try {
            $template2 = Template::create([
                'event_id' => $event->id,
                'name' => 'Plantilla Default 2',
                'file_path' => 'templates/default2.png',
                'layout_meta' => [
                    'fold_mm' => 139.7,
                    'rect_photo' => ['x' => 10, 'y' => 10, 'width' => 30, 'height' => 40],
                    'rect_qr' => ['x' => 160, 'y' => 10, 'width' => 20, 'height' => 20]
                ],
                'version' => 2,
                'is_default' => true
            ]);
            
            $this->fail('Se esperaba que la creación de una segunda plantilla predeterminada fallara');
        } catch (\Exception $e) {
            // Verificar que se lanzó una excepción relacionada con la restricción única
            $this->assertStringContainsString('UNIQUE constraint failed', $e->getMessage());
            
            // Verificar que sólo hay una plantilla predeterminada para el evento
            $count = Template::where('event_id', $event->id)
                          ->where('is_default', true)
                          ->count();
            $this->assertEquals(1, $count);
        }
    }
}
