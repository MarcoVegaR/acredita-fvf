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
                    'fold_mm' => 776, // Línea de pliegue actualizada según servidor EC2
                    'rect_photo' => [
                        'x' => 812,
                        'y' => 229,
                        'width' => 192,
                        'height' => 194
                    ],
                    'rect_qr' => [
                        'x' => 852,
                        'y' => 434,
                        'width' => 114,
                        'height' => 58
                    ],
                    'text_blocks' => [
                        [
                            'id' => 'nombre',
                            'x' => 1042,
                            'y' => 273,
                            'width' => 464,
                            'height' => 22,
                            'font_size' => 40,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'rol',
                            'x' => 1042,
                            'y' => 368,
                            'width' => 468,
                            'height' => 22,
                            'font_size' => 22,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'proveedor',
                            'x' => 1034,
                            'y' => 457,
                            'width' => 473,
                            'height' => 20,
                            'font_size' => 30,
                            'alignment' => 'left'
                        ],
                        // Zonas individuales (1-9) - Ajustadas según servidor EC2
                        [
                            'id' => 'zona1',
                            'x' => 841,
                            'y' => 623,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona2',
                            'x' => 976,
                            'y' => 621,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona3',
                            'x' => 1111,
                            'y' => 623,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona4',
                            'x' => 1242,
                            'y' => 623,
                            'width' => 84,
                            'height' => 84,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona5',
                            'x' => 1366,
                            'y' => 624,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona6',
                            'x' => 912,
                            'y' => 760,
                            'width' => 84,
                            'height' => 84,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona7',
                            'x' => 1045,
                            'y' => 762,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona8',
                            'x' => 1182,
                            'y' => 760,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
                            'alignment' => 'center'
                        ],
                        [
                            'id' => 'zona9',
                            'x' => 1312,
                            'y' => 759,
                            'width' => 84,
                            'height' => 85,
                            'font_size' => 100,
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
