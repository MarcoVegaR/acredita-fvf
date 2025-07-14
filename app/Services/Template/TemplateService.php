<?php

namespace App\Services\Template;

use App\Models\Template;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Repositories\Event\EventRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TemplateService implements TemplateServiceInterface
{
    /**
     * @var TemplateRepositoryInterface
     */
    protected $templateRepository;
    
    /**
     * @var EventRepositoryInterface
     */
    protected $eventRepository;
    
    /**
     * Cache TTL in minutes
     */
    const CACHE_TTL = 60;

    /**
     * TemplateService constructor.
     *
     * @param TemplateRepositoryInterface $templateRepository
     * @param EventRepositoryInterface $eventRepository
     */
    public function __construct(
        TemplateRepositoryInterface $templateRepository,
        EventRepositoryInterface $eventRepository
    )
    {
        $this->templateRepository = $templateRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getForEvent(int $eventId)
    {
        return $this->templateRepository->listByEvent($eventId, ['event']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $uuid)
    {
        // Usar caché para mejorar rendimiento en generación masiva
        return Cache::remember("template_{$uuid}", now()->addMinutes(self::CACHE_TTL), function() use ($uuid) {
            return $this->templateRepository->findByUuid($uuid, ['event']);
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDefaultForEvent(int $eventId)
    {
        // Usar caché para mejorar rendimiento en generación masiva
        return Cache::remember("template_default_{$eventId}", now()->addMinutes(self::CACHE_TTL), function() use ($eventId) {
            return $this->templateRepository->getDefaultForEvent($eventId, ['event']);
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $this->validateTemplateData($data);
        
        // Si se establece como predeterminada, validar que el evento exista
        if (isset($data['is_default']) && $data['is_default']) {
            $event = $this->eventRepository->find($data['event_id']);
            if (!$event) {
                throw ValidationException::withMessages([
                    'event_id' => ['El evento especificado no existe'],
                ]);
            }
        }
        
        // Crear la plantilla
        $template = $this->templateRepository->create($data);
        
        // Si es predeterminada, actualizar la caché
        if ($template->is_default) {
            Cache::forget("template_default_{$template->event_id}");
        }
        
        return $template;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(Template $template, array $data)
    {
        if (count($data) > 0) {
            $this->validateTemplateData($data);
        }
        
        // Actualizar la plantilla
        $template = $this->templateRepository->update($template->id, $data);
        
        // Actualizar la caché
        Cache::forget("template_{$template->uuid}");
        if ($template->is_default) {
            Cache::forget("template_default_{$template->event_id}");
        }
        
        return $template;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setAsDefault(Template $template)
    {
        $result = $this->templateRepository->setAsDefault($template);
        
        // Actualizar la caché
        Cache::forget("template_{$template->uuid}");
        Cache::forget("template_default_{$template->event_id}");
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(Template $template)
    {
        $result = $this->templateRepository->delete($template->id);
        
        // Actualizar la caché
        Cache::forget("template_{$template->uuid}");
        if ($template->is_default) {
            Cache::forget("template_default_{$template->event_id}");
        }
        
        return $result;
    }
    
    /**
     * Validate template data
     *
     * @param array $data
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateTemplateData(array $data)
    {
        // Validar los datos básicos
        $validator = Validator::make($data, [
            'event_id' => 'sometimes|required|integer|exists:events,id',
            'name' => 'sometimes|required|string|max:100',
            'file_path' => 'sometimes|required|string|max:255',
            'version' => 'sometimes|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'layout_meta' => 'sometimes|array',
        ]);
        
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
        
        // Validar estructura mínima de layout_meta si está presente
        if (isset($data['layout_meta']) && is_array($data['layout_meta'])) {
            $requiredFields = ['fold_mm', 'rect_photo', 'rect_qr'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data['layout_meta'][$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                throw ValidationException::withMessages([
                    'layout_meta' => ['Los campos obligatorios faltan en layout_meta: ' . implode(', ', $missingFields)],
                ]);
            }
        }
    }
    
    /**
     * Get the full path for a template file
     *
     * @param Template $template
     * @return string
     */
    protected function getTemplatePath(Template $template)
    {
        return Storage::disk('templates')->path($template->file_path);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPaginatedTemplates(int $perPage = 10, int $page = 1, ?string $search = '', string $sortBy = 'created_at', string $sortOrder = 'desc', ?int $eventId = null)
    {
        // Aplicar el mismo patrón que en UserService
        $filters = [];
        
        // Aplicar filtro de búsqueda
        if ($search !== null && $search !== '') {
            // En UserRepository, 'search' es un caso especial que maneja el repositorio
            $filters['search'] = $search;
        }
        
        // Aplicar filtro de evento
        if ($eventId) {
            $filters['event_id'] = $eventId;
        }
        
        // Configurar opciones de ordenamiento
        $sortOptions = [
            'field' => $sortBy,
            'direction' => $sortOrder
        ];
        
        // Establecer manualmente la página en la request global
        // Esto permite que el método paginate del BaseRepository use la página correcta
        request()->merge(['page' => $page]);
        
        // Usar el método paginate del repositorio base como lo hace UserService
        $templates = $this->templateRepository->paginate(
            $perPage,
            ['event'],  // relaciones como segundo parámetro
            $filters,
            $sortOptions
        );
        
        // Devolver el objeto de paginación completo como lo hace UserService
        return $templates;
    }
    
    /**
     * {@inheritdoc}
     */
    public function saveTemplateFile(\Illuminate\Http\UploadedFile $file, string $eventUuid)
    {
        // Generar nombre de archivo único usando timestamp
        $timestamp = now()->format('Ymd_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "template_{$eventUuid}_{$timestamp}.{$extension}";
        
        // Guardar el archivo en el disco 'templates'
        $path = $file->storeAs("events/{$eventUuid}", $filename, 'templates');
        
        return $path;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTemplateUrl(Template $template)
    {
        if ($template->file_path) {
            // Las imágenes se guardan en storage/app/public/templates/events/...
            // pero se acceden a través de public/storage/templates/events/...
            $relativePath = $template->file_path;
            $storageFullPath = storage_path('app/public/templates/' . $relativePath);
            $publicUrl = url('storage/templates/' . $relativePath);
            
            // Si la ruta ya tiene 'templates/' como prefijo, corregirla
            if (strpos($relativePath, 'templates/') === 0) {
                $relativePath = substr($relativePath, strlen('templates/'));
                $storageFullPath = storage_path('app/public/templates/' . $relativePath);
                $publicUrl = url('storage/templates/' . $relativePath);
            }
            
            // Verificar si el archivo existe físicamente
            if (!file_exists($storageFullPath)) {
                \Log::warning("Template file not found: {$storageFullPath}");
            } else {
                // Verificar permisos del archivo
                $perms = substr(sprintf('%o', fileperms($storageFullPath)), -4);
                \Log::info("Template file exists: {$storageFullPath} with permissions {$perms}");
            }
            
            // Verificar si el directorio public/storage existe y es accesible
            $publicStoragePath = public_path('storage');
            if (!is_dir($publicStoragePath)) {
                \Log::warning("Public storage directory not found: {$publicStoragePath}");
            } else {
                \Log::info("Public storage directory exists: {$publicStoragePath}");
            }
            
            // Log de la URL que se está devolviendo
            \Log::info("Returning template URL: {$publicUrl}");
            
            return $publicUrl;
        }
        
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid)
    {
        return $this->get($uuid);
    }
    // Método ya implementado como getTemplatePath
    
    /**
     * Get the public URL for a template file
     *
     * @param Template $template
     * @return string|null
     */
    public function getTemplateFileUrl(Template $template)
    {
        return Storage::disk('templates')->url($template->file_path);
    }
}
