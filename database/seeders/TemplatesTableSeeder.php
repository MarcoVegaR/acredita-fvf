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
                            'x' => 70,
                            'y' => 30,
                            'width' => 90,
                            'height' => 10,
                            'font_size' => 12,
                            'alignment' => 'left'
                        ],
                        [
                            'id' => 'rol',
                            'x' => 70,
                            'y' => 45,
                            'width' => 90,
                            'height' => 8,
                            'font_size' => 10,
                            'alignment' => 'left'
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
