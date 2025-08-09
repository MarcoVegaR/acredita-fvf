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
            
            // Crear una imagen para la plantilla con nombre estándar template_<event_uuid>_<YYYYMMDD_HHMMSS>.png
            $timestamp = now()->format('Ymd_His');
            $fileName = 'template_' . $event->uuid . '_' . $timestamp . '.png';
            $imagePath = $eventDir . '/' . $fileName;
            $relativePath = 'events/' . $event->uuid . '/' . $fileName;
            
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
                            'x' => 986,
                            'y' => 273,
                            'width' => 464,
                            'height' => 22,
                            'font_size' => 40,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'federacion',
                            'x' => 990,
                            'y' => 318,
                            'width' => 464,
                            'height' => 22,
                            'font_size' => 20,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'rol',
                            'x' => 997,
                            'y' => 361,
                            'width' => 468,
                            'height' => 22,
                            'font_size' => 22,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'proveedor',
                            'x' => 978,
                            'y' => 457,
                            'width' => 473,
                            'height' => 20,
                            'font_size' => 30,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'credential_uuid',
                            'x' => 144,
                            'y' => 994,
                            'width' => 473,
                            'height' => 15,
                            'font_size' => 20,
                            'alignment' => 'left'
                        ],
                        // Bloque dinámico de zonas (reemplaza zona1-zona9)
                        [
                            'id' => 'zones',
                            'type' => 'zones',
                            'x' => 841,   // borde izquierdo de la cuadrícula original
                            'y' => 510,   // ajustado según layout actual
                            'width' => 555, // 1396 - 841 (derecha - izquierda)
                            'height' => 290, // 847 - 621 (abajo - arriba)
                            'padding' => 8,
                            'gap' => 10,
                            'font_family' => 'arial.ttf',
                            'font_color' => '#000000'
                        ]
                    ]
                ],
                'version' => 3,
                'is_default' => true
            ]);
            
            $this->command->info("Plantilla creada para evento: {$event->name}");
        }
    }
}
