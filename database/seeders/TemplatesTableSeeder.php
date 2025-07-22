<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los eventos
        $events = Event::all();
        
        if ($events->isEmpty()) {
            $this->command->info('No hay eventos para crear plantillas');
            return;
        }
        
        // Crear directorio base para plantillas si no existe
        $baseDir = storage_path('app/public/templates');
        if (!File::exists($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
        }
        
        // Crear una plantilla por cada evento
        foreach ($events as $event) {
            // Crear directorio específico del evento (dentro de 'events')
            $eventDir = $baseDir . '/events/' . $event->uuid;
            if (!File::exists($eventDir)) {
                File::makeDirectory($eventDir, 0755, true);
            }
            
            // Crear una imagen dummy para la plantilla (un rectángulo blanco con el nombre del evento)
            $templateUuid = (string) Str::uuid();
            $imagePath = $eventDir . '/' . $templateUuid . '.png';
            $relativePath = 'events/' . $event->uuid . '/' . $templateUuid . '.png';
            
            // Si no existe, crear una imagen dummy
            if (!File::exists($imagePath)) {
                // Crear una imagen simple con el nombre del evento
                $img = imagecreatetruecolor(794, 1123); // Tamaño carta en pixeles a 72dpi
                $white = imagecolorallocate($img, 255, 255, 255);
                $black = imagecolorallocate($img, 0, 0, 0);
                
                // Llenar de blanco
                imagefill($img, 0, 0, $white);
                
                // Escribir título del evento
                $text = "Plantilla para: {$event->name}";
                $fontSize = 5;
                $x = 50;
                $y = 100;
                
                // Dibujar texto
                imagestring($img, $fontSize, $x, $y, $text, $black);
                
                // Guardar imagen
                imagepng($img, $imagePath);
                imagedestroy($img);
            }
            
            // Crear registro en la base de datos
            Template::create([
                'event_id' => $event->id,
                'name' => 'Plantilla estándar ' . $event->name,
                'file_path' => $relativePath,
                'layout_meta' => [
                    'fold_mm' => 139.7, // Línea de pliegue (mitad de la hoja carta)
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
                    ],
                    'text_blocks' => [
                        [
                            'id' => 'nombre',
                            'x' => 835,
                            'y' => 430,
                            'width' => 200,
                            'height' => 25,
                            'font_size' => 14,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'rol',
                            'x' => 835,
                            'y' => 475,
                            'width' => 200,
                            'height' => 20,
                            'font_size' => 12,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'proveedor',
                            'x' => 835,
                            'y' => 500,
                            'width' => 250,
                            'height' => 20,
                            'font_size' => 11,
                            'alignment' => 'center'
                        ],
                        // Zonas individuales (1-9) - Ajustadas a las posiciones de los recuadros
                        [
                            'id' => 'zona1',
                            'x' => 788,
                            'y' => 540,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona2',
                            'x' => 834,
                            'y' => 540,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona3',
                            'x' => 880,
                            'y' => 540,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona4',
                            'x' => 926,
                            'y' => 540,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona5',
                            'x' => 972,
                            'y' => 540,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona6',
                            'x' => 811,
                            'y' => 600,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona7',
                            'x' => 857,
                            'y' => 600,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona8',
                            'x' => 903,
                            'y' => 600,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona9',
                            'x' => 949,
                            'y' => 600,
                            'width' => 46,
                            'height' => 60,
                            'font_size' => 20,
                            'alignment' => 'center'
                        ]
                    ]
                ],
                'version' => 1,
                'is_default' => true
            ]);
            
            $this->command->info("Plantilla creada para evento: {$event->name}");
        }
    }
}
