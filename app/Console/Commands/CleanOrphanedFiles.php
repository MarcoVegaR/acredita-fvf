<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CleanOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-orphaned-files 
                            {--dry-run : Solo mostrar archivos a eliminar sin ejecutar}
                            {--type=all : Especificar el tipo (images, documents, o all)}
                            {--mode=all : Especificar el modo de limpieza (db, files, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina archivos huérfanos (imágenes y documentos sin asociación a entidades)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $type = $this->option('type') ?: 'all';
        $mode = $this->option('mode') ?: 'all';
        
        // Agregar encabezado con información sobre la operación
        $this->info('LIMPIEZA DE ARCHIVOS HUÉRFANOS');
        if ($dryRun) {
            $this->warn('Ejecutando en modo de prueba (--dry-run). No se eliminarán archivos.');
        }
        $this->info('Tipo seleccionado: ' . $type);
        $this->info('Modo seleccionado: ' . $mode);
        $this->newLine();
        
        // Inicializar contadores
        $imageCount = 0;
        $documentCount = 0;
        $filesImageCount = 0;
        $filesDocumentCount = 0;
        
        // Limpiar imágenes huérfanas en la base de datos si se especifica
        if (($mode === 'all' || $mode === 'db') && ($type === 'all' || $type === 'images')) {
            $this->info('VERIFICANDO REGISTROS HUÉRFANOS EN BASE DE DATOS:');
            $imageCount = $this->cleanOrphanedImagesInDb($dryRun);
        }
        
        // Limpiar documentos huérfanos en la base de datos si se especifica
        if (($mode === 'all' || $mode === 'db') && ($type === 'all' || $type === 'documents')) {
            $documentCount = $this->cleanOrphanedDocumentsInDb($dryRun);
        }
        
        // Limpiar archivos físicos huérfanos si se especifica
        if ($mode === 'all' || $mode === 'files') {
            $this->info('VERIFICANDO ARCHIVOS FÍSICOS HUÉRFANOS:');
            if ($type === 'all' || $type === 'images') {
                $filesImageCount = $this->cleanOrphanedImageFiles($dryRun);
            }
            
            if ($type === 'all' || $type === 'documents') {
                $filesDocumentCount = $this->cleanOrphanedDocumentFiles($dryRun);
            }
        }
        
        // Mostrar resumen
        $this->newLine();
        $this->info('RESUMEN DE LIMPIEZA:');
        if ($mode === 'all' || $mode === 'db') {
            $this->info("- Registros de imágenes huérfanos: {$imageCount}");
            $this->info("- Registros de documentos huérfanos: {$documentCount}");
        }
        if ($mode === 'all' || $mode === 'files') {
            $this->info("- Archivos de imágenes huérfanos: {$filesImageCount}");
            $this->info("- Archivos de documentos huérfanos: {$filesDocumentCount}");
        }
        $this->info("Total: " . ($imageCount + $documentCount + $filesImageCount + $filesDocumentCount));
        
        Log::info('Limpieza de archivos huérfanos completada', [
            'type' => $type,
            'mode' => $mode,
            'dry_run' => $dryRun,
            'db_images_cleaned' => $imageCount,
            'db_documents_cleaned' => $documentCount,
            'files_images_cleaned' => $filesImageCount,
            'files_documents_cleaned' => $filesDocumentCount
        ]);
        
        return Command::SUCCESS;
    }
    
    /**
     * Limpia las imágenes huérfanas (sin relación imageable)
     *
     * @param bool $dryRun Si es true, no elimina nada, solo muestra qué se eliminaría
     * @return int Número de imágenes limpiadas
     */
    private function cleanOrphanedImagesInDb(bool $dryRun): int
    {
        $this->info('Buscando imágenes huérfanas...');
        
        // Buscar imágenes sin relación 'imageables'
        $orphanedImages = Image::unattached()->get();
        
        $count = $orphanedImages->count();
        $this->info("Encontradas {$count} imágenes huérfanas");
        
        if ($count === 0) {
            return 0;
        }
        
        $this->newLine();
        $this->info('PROCESANDO IMÁGENES HUÉRFANAS:');
        
        $counter = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($orphanedImages as $image) {
            $details = "ID: {$image->id}, Nombre: {$image->name}, Ruta: {$image->path}";
            
            if ($dryRun) {
                // En modo de prueba solo mostramos la información
                if ($counter < 5) { // Limitar la cantidad de detalles mostrados
                    $this->info("  [DRY-RUN] Se eliminaría: {$details}");
                }
            } else {
                // Eliminar archivo principal
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
                
                // Eliminar thumbnail si existe
                if (!empty($image->thumbnail_path) && Storage::disk('public')->exists($image->thumbnail_path)) {
                    Storage::disk('public')->delete($image->thumbnail_path);
                }
                
                // Eliminar registro
                $image->delete();
                
                // Registrar en logs
                Log::info("Limpieza: Imagen huérfana eliminada", [
                    'id' => $image->id,
                    'name' => $image->name,
                    'path' => $image->path
                ]);
            }
            
            $counter++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        if (!$dryRun) {
            $this->info("Se han eliminado {$count} imágenes huérfanas");
        } else {
            $this->info("Se encontraron {$count} imágenes huérfanas para eliminar (modo prueba)");
        }
        
        return $count;
    }
    
    /**
     * Limpia los documentos huérfanos (sin relación documentable)
     *
     * @param bool $dryRun Si es true, no elimina nada, solo muestra qué se eliminaría
     * @return int Número de documentos limpiados
     */
    private function cleanOrphanedDocumentsInDb(bool $dryRun): int
    {
        $this->info('Buscando documentos huérfanos...');
        
        // Buscar documentos sin relación 'documentables'
        $orphanedDocuments = Document::whereDoesntHave('documentables')->get();
        
        $count = $orphanedDocuments->count();
        $this->info("Encontrados {$count} documentos huérfanos");
        
        if ($count === 0) {
            return 0;
        }
        
        $this->newLine();
        $this->info('PROCESANDO DOCUMENTOS HUÉRFANOS:');
        
        $counter = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($orphanedDocuments as $document) {
            $details = "ID: {$document->id}, Nombre: {$document->name}, Ruta: {$document->path}";
            
            if ($dryRun) {
                // En modo de prueba solo mostramos la información
                if ($counter < 5) { // Limitar la cantidad de detalles mostrados
                    $this->info("  [DRY-RUN] Se eliminaría: {$details}");
                }
            } else {
                // Eliminar archivo
                if (Storage::disk('public')->exists($document->path)) {
                    Storage::disk('public')->delete($document->path);
                }
                
                // Eliminar registro
                $document->delete();
                
                // Registrar en logs
                Log::info("Limpieza: Documento huérfano eliminado", [
                    'id' => $document->id,
                    'name' => $document->name,
                    'path' => $document->path
                ]);
            }
            
            $counter++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        if (!$dryRun) {
            $this->info("Se han eliminado {$count} documentos huérfanos");
        } else {
            $this->info("Se encontraron {$count} documentos huérfanos para eliminar (modo prueba)");
        }
        
        return $count;
    }
    
    /**
     * Limpia los archivos de imágenes huérfanos (sin registro en la BD)
     *
     * @param bool $dryRun Si es true, no elimina nada, solo muestra qué se eliminaría
     * @return int Número de archivos limpiados
     */
    private function cleanOrphanedImageFiles(bool $dryRun): int
    {
        $this->info('Buscando archivos de imágenes huérfanos...');
        $count = 0;
        
        // Probamos diferentes discos y rutas para encontrar los archivos
        $physicalFiles = [];
        
        // 1. Intento con el disco 'local' y la ruta 'public/images'
        if (Storage::disk('local')->exists('public/images')) {
            $physicalFiles = Storage::disk('local')->allFiles('public/images');
            $this->info("Se encontraron " . count($physicalFiles) . " archivos físicos en storage/app/public/images");
        }
        
        // 2. Si no hay resultados, intento con el disco 'public' y la ruta 'images'
        if (empty($physicalFiles) && Storage::disk('public')->exists('images')) {
            $physicalFiles = Storage::disk('public')->allFiles('images');
            $this->info("Se encontraron " . count($physicalFiles) . " archivos físicos en storage/public/images");
        }
        
        // Si aún no hay resultados, es posible que no existan archivos de imágenes
        if (empty($physicalFiles)) {
            $this->warn("No se encontraron archivos de imágenes en ninguna ubicación");
            return 0;
        }
        
        // Obtener todos los paths registrados en la base de datos
        $dbPaths = DB::table('images')->pluck('path')->toArray();
        $dbThumbnails = [];
        
        // Generar las rutas de thumbnails basadas en los registros existentes
        foreach ($dbPaths as $path) {
            $pathInfo = pathinfo($path);
            $thumbnail = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $dbThumbnails[] = $thumbnail;
        }
        
        // Combinar paths y thumbnails registrados
        $registeredFiles = array_merge($dbPaths, $dbThumbnails);
        $this->info("Se encontraron " . count($registeredFiles) . " archivos registrados (incluyendo thumbnails)");
        
        // Encontrar archivos huérfanos (que existen físicamente pero no están registrados)
        $orphanedFiles = [];
        foreach ($physicalFiles as $file) {
            if (!in_array($file, $registeredFiles)) {
                $orphanedFiles[] = $file;
            }
        }
        
        $count = count($orphanedFiles);
        $this->info("Encontrados {$count} archivos de imágenes huérfanos");
        
        if ($count === 0) {
            return 0;
        }
        
        $this->newLine();
        $this->info('PROCESANDO ARCHIVOS DE IMÁGENES HUÉRFANOS:');
        
        $counter = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($orphanedFiles as $file) {
            // Extraer información útil del path para mostrar
            $filename = basename($file);
            $fullPath = 'storage/' . $file;
            
            if ($dryRun) {
                // En modo de prueba solo mostramos la información
                if ($counter < 10) { // Limitar la cantidad de detalles mostrados
                    $realPath = str_starts_with($file, 'public/') ? 
                        'storage/app/' . $file : 'storage/' . $file;
                    $this->info("  [DRY-RUN] Se eliminaría: {$realPath}");
                }
            } else {
                // Eliminar archivo - determinamos el disco correcto
                $disk = 'public';
                $path = $file;
                
                // Si es un archivo en 'storage/app/public/...'
                if (str_starts_with($file, 'public/')) {
                    $disk = 'local';
                    $path = $file;
                }
                
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    
                    // Registrar en logs
                    Log::info("Limpieza: Archivo de imagen huérfano eliminado", [
                        'disk' => $disk,
                        'path' => $path,
                        'full_path' => $fullPath
                    ]);
                    
                    // Mostrar información de eliminación al ejecutar
                    $this->line("  <fg=red>[ELIMINADO]</> {$fullPath}");
                }
            }
            
            $counter++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        if (!$dryRun) {
            $this->info("Se han eliminado {$count} archivos de imágenes huérfanos");
        } else {
            $this->info("Se encontraron {$count} archivos de imágenes huérfanos para eliminar (modo prueba)");
        }
        
        return $count;
    }
    
    /**
     * Limpia los archivos de documentos huérfanos (sin registro en la BD)
     *
     * @param bool $dryRun Si es true, no elimina nada, solo muestra qué se eliminaría
     * @return int Número de archivos limpiados
     */
    private function cleanOrphanedDocumentFiles(bool $dryRun): int
    {
        $this->info('Buscando archivos de documentos huérfanos...');
        $count = 0;
        
        // Probamos diferentes discos y rutas para encontrar los archivos
        $physicalFiles = [];
        
        // 1. Intento con el disco 'local' y la ruta 'public/documents'
        if (Storage::disk('local')->exists('public/documents')) {
            $physicalFiles = Storage::disk('local')->allFiles('public/documents');
            $this->info("Se encontraron " . count($physicalFiles) . " archivos físicos en storage/app/public/documents");
        }
        
        // 2. Si no hay resultados, intento con el disco 'public' y la ruta 'documents'
        if (empty($physicalFiles) && Storage::disk('public')->exists('documents')) {
            $physicalFiles = Storage::disk('public')->allFiles('documents');
            $this->info("Se encontraron " . count($physicalFiles) . " archivos físicos en storage/public/documents");
        }
        
        // Si aún no hay resultados, es posible que no existan archivos de documentos
        if (empty($physicalFiles)) {
            $this->warn("No se encontraron archivos de documentos en ninguna ubicación");
            return 0;
        }
        
        // Obtener todos los paths registrados en la base de datos
        $dbPaths = DB::table('documents')->pluck('path')->toArray();
        $this->info("Se encontraron " . count($dbPaths) . " documentos registrados");
        
        // Encontrar archivos huérfanos (que existen físicamente pero no están registrados)
        $orphanedFiles = [];
        foreach ($physicalFiles as $file) {
            if (!in_array($file, $dbPaths)) {
                $orphanedFiles[] = $file;
            }
        }
        
        $count = count($orphanedFiles);
        $this->info("Encontrados {$count} archivos de documentos huérfanos");
        
        if ($count === 0) {
            return 0;
        }
        
        $this->newLine();
        $this->info('PROCESANDO ARCHIVOS DE DOCUMENTOS HUÉRFANOS:');
        
        $counter = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($orphanedFiles as $file) {
            // Extraer información útil del path para mostrar
            $filename = basename($file);
            $fullPath = 'storage/' . $file;
            
            if ($dryRun) {
                // En modo de prueba solo mostramos la información
                if ($counter < 10) { // Limitar la cantidad de detalles mostrados
                    $realPath = str_starts_with($file, 'public/') ? 
                        'storage/app/' . $file : 'storage/' . $file;
                    $this->info("  [DRY-RUN] Se eliminaría: {$realPath}");
                }
            } else {
                // Eliminar archivo - determinamos el disco correcto
                $disk = 'public';
                $path = $file;
                
                // Si es un archivo en 'storage/app/public/...'
                if (str_starts_with($file, 'public/')) {
                    $disk = 'local';
                    $path = $file;
                }
                
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    
                    // Registrar en logs
                    Log::info("Limpieza: Archivo de documento huérfano eliminado", [
                        'disk' => $disk,
                        'path' => $path,
                        'full_path' => $fullPath
                    ]);
                    
                    // Mostrar información de eliminación al ejecutar
                    $this->line("  <fg=red>[ELIMINADO]</> {$fullPath}");
                }
            }
            
            $counter++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        if (!$dryRun) {
            $this->info("Se han eliminado {$count} archivos de documentos huérfanos");
        } else {
            $this->info("Se encontraron {$count} archivos de documentos huérfanos para eliminar (modo prueba)");
        }
        
        return $count;
    }
}
