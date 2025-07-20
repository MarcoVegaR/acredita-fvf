<?php

namespace App\Http\Controllers;

use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Http\Requests\Template\DeleteTemplateRequest;
use App\Http\Requests\Template\SetAsDefaultRequest;
use App\Models\Template;
use App\Models\Event;
use App\Services\Template\TemplateServiceInterface;
use App\Services\Event\EventServiceInterface;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TemplateController extends BaseController
{
    /**
     * The template service instance.
     *
     * @var TemplateServiceInterface
     */
    protected $templateService;
    
    /**
     * The event service instance.
     *
     * @var EventServiceInterface
     */
    protected $eventService;

    /**
     * Create a new controller instance.
     *
     * @param TemplateServiceInterface $templateService
     * @param EventServiceInterface $eventService
     */
    public function __construct(
        TemplateServiceInterface $templateService,
        EventServiceInterface $eventService
    )
    {
        $this->templateService = $templateService;
        $this->eventService = $eventService;
    }

    /**
     * Muestra un listado de plantillas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $this->authorize('templates.index');
        
        // Filtros de búsqueda y paginación
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $eventId = $request->input('event_id', null);
        
        // Si el valor es 'all', tratarlo como null (sin filtro)
        if ($eventId === 'all') {
            $eventId = null;
        }
        
        // Obtener plantillas
        $templates = $this->templateService->getPaginatedTemplates(
            $perPage,
            $page,
            $search,
            $sortBy,
            $sortOrder,
            $eventId
        );
        
        // Obtener estadísticas
        $stats = [
            'total' => Template::count(),
            'default' => Template::where('is_default', true)->count(),
        ];
        
        // Obtener todos los eventos para el filtro
        $events = $this->eventService->getAllActive();
        
        return Inertia::render('templates/index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'per_page', 'page', 'sort_by', 'sort_order', 'event_id']),
            'stats' => $stats,
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name
                ];
            })
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva plantilla.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function create(Request $request)
    {
        $this->authorize('templates.create');
        
        // Obtener los eventos disponibles
        $events = $this->eventService->getAllActive();
        
        // Si se proporciona un ID de evento, preseleccionarlo
        $selectedEventId = $request->input('event_id');
        
        return Inertia::render('templates/create', [
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name
                ];
            }),
            'selectedEventId' => $selectedEventId,
            'defaultLayout' => [
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
            ]
        ]);
    }

    /**
     * Almacena una nueva plantilla en la base de datos.
     *
     * @param  \App\Http\Requests\Template\StoreTemplateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTemplateRequest $request)
    {

        
        DB::beginTransaction();
        
        try {
            // Obtener evento
            $event = Event::findOrFail($request->event_id);
            
            // Procesar archivo
            $filePath = null;
            if ($request->hasFile('template_file') && $request->file('template_file') !== null) {
                $file = $request->file('template_file');
                $filePath = $this->templateService->saveTemplateFile($file, $event->uuid);
            }
            
            // Crear plantilla
            $template = $this->templateService->create([
                'event_id' => $request->event_id,
                'name' => $request->name,
                'file_path' => $filePath,
                'layout_meta' => $request->layout_meta,
                'version' => $request->version ?? 1,
                'is_default' => $request->is_default ?? false
            ]);
            
            // Si es plantilla predeterminada, actualizar todas las demás
            if ($template->is_default) {
                $this->templateService->setAsDefault($template);
            }
            
            DB::commit();
            
            return redirect()->route('templates.index')
                ->with('success', 'Plantilla creada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Muestra los detalles de una plantilla específica.
     *
     * @param  mixed  $template_id_or_uuid
     * @return \Inertia\Response
     */
    public function show($template_id_or_uuid)
    {
        $this->authorize('templates.show');
        
        // Detectar si el parámetro es un ID numérico o un UUID
        if (is_numeric($template_id_or_uuid)) {
            // Es un ID numérico
            $template = Template::with('event')->find($template_id_or_uuid);
        } else {
            // Es un UUID
            $template = $this->templateService->findByUuid($template_id_or_uuid);
            // Cargar explícitamente la relación del evento
            if ($template) {
                $template->load('event');
            }
        }
        
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }
        
        // Intentar cargar manualmente el evento si existe event_id pero no se cargó la relación
        $eventData = null;
        if ($template->event_id) {
            \Log::debug('Buscando evento por ID: ' . $template->event_id);
            
            // Intentar obtener el evento directamente de la base de datos
            $eventRow = \DB::table('events')->where('id', $template->event_id)->first();
            \Log::debug('Resultado de DB::table: ', ['exists' => !is_null($eventRow)]);
            
            // Intentar con el modelo
            $event = \App\Models\Event::find($template->event_id);
            \Log::debug('Resultado de Event::find: ', ['exists' => !is_null($event)]);
            
            if ($event) {
                $eventData = [
                    'id' => $event->id,
                    'name' => $event->name
                ];
                \Log::debug('Usando datos del modelo Event');
            } elseif ($eventRow) {
                $eventData = [
                    'id' => $eventRow->id,
                    'name' => $eventRow->name
                ];
                \Log::debug('Usando datos directos de la tabla events');
            }
        }
        
        // Si tenemos el evento desde la relación, usarlo; de lo contrario usar el que cargamos manualmente
        if (!$eventData && $template->event) {
            $eventData = [
                'id' => $template->event->id,
                'name' => $template->event->name
            ];
        }
        
        return Inertia::render('templates/show', [
            'template' => [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'event' => $eventData,
                'file_url' => $this->templateService->getTemplateUrl($template),
                'layout_meta' => $template->layout_meta,
                'version' => $template->version,
                'is_default' => $template->is_default,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at
            ],
            'can_set_default' => !$template->is_default && Auth::user()->can('templates.set_default'),
        'can_regenerate_credentials' => Auth::user()->can('credentials.regenerate')
        ]);
    }

    /**
     * Muestra el formulario para editar una plantilla.
     *
     * @param  mixed  $template_id_or_uuid
     * @return \Inertia\Response
     */
    public function edit($template_id_or_uuid)
    {
        $this->authorize('templates.edit');
        
        // Detectar si el parámetro es un ID numérico o un UUID
        if (is_numeric($template_id_or_uuid)) {
            // Es un ID numérico
            $template = Template::find($template_id_or_uuid);
        } else {
            // Es un UUID
            $template = $this->templateService->findByUuid($template_id_or_uuid);
        }
        
        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }
        
        return Inertia::render('templates/edit', [
            'template' => [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'event_id' => $template->event_id,
                'file_url' => $this->templateService->getTemplateUrl($template),
                'layout_meta' => $template->layout_meta,
                'version' => $template->version,
                'is_default' => $template->is_default,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at
            ],
            'events' => Event::where('active', true)->get()->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name
                ];
            })
        ]);
    }

    /**
     * Actualiza una plantilla en la base de datos.
     *
     * @param  \App\Http\Requests\Template\UpdateTemplateRequest  $request
     * @param  mixed  $template_id_or_uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateTemplateRequest $request, $template_id_or_uuid)
    {

        
        DB::beginTransaction();
        
        try {
            // Detectar si el parámetro es un ID numérico o un UUID
            if (is_numeric($template_id_or_uuid)) {
                // Es un ID numérico
                $template = Template::find($template_id_or_uuid);
            } else {
                // Es un UUID
                $template = $this->templateService->findByUuid($template_id_or_uuid);
            }
            
            if (!$template) {
                abort(404, 'Plantilla no encontrada');
            }
            
            // Datos para actualizar
            $data = [
                'name' => $request->name,
                'layout_meta' => $request->layout_meta,
                'is_default' => $request->is_default ?? $template->is_default,
                'version' => $request->version ?? ($template->version + 1)
            ];
            

            
            // Procesar archivo si se proporciona uno nuevo
            if ($request->hasFile('template_file')) {
                $file = $request->file('template_file');
                $filePath = $this->templateService->saveTemplateFile($file, $template->event->uuid);
                $data['file_path'] = $filePath;
            }
            
            // Actualizar plantilla
            $updated = $this->templateService->update($template, $data);
            

            
            // Si se actualizó a predeterminada, actualizar las demás
            if ($updated && $data['is_default'] && !$template->is_default) {
                $this->templateService->setAsDefault($template);
            }
            
            DB::commit();
            
            return redirect()->route('templates.index')
                ->with('success', 'Plantilla actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una plantilla.
     *
     * @param  \App\Http\Requests\Template\DeleteTemplateRequest  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DeleteTemplateRequest $request, $template_id_or_uuid)
    {
        try {
            // Detectar si el parámetro es un ID numérico o un UUID
            if (is_numeric($template_id_or_uuid)) {
                // Es un ID numérico
                $template = Template::find($template_id_or_uuid);
            } else {
                // Es un UUID
                $template = $this->templateService->findByUuid($template_id_or_uuid);
            }
            
            if (!$template) {
                abort(404, 'Plantilla no encontrada');
            }
            
            // No permitir eliminar la plantilla predeterminada
            if ($template->is_default) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar la plantilla predeterminada.');
            }
            
            // Eliminar plantilla
            $this->templateService->delete($template);
            
            return redirect()->route('templates.index')
                ->with('success', 'Plantilla eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Establece una plantilla como predeterminada.
     *
     * @param  \App\Http\Requests\Template\SetAsDefaultRequest  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setAsDefault(SetAsDefaultRequest $request, $uuid)
    {
        try {
            // Obtener plantilla
            $template = $this->templateService->findByUuid($uuid);
            
            if (!$template) {
                abort(404, 'Plantilla no encontrada');
            }
            
            // Si ya es la predeterminada, no hacer nada
            if ($template->is_default) {
                return redirect()->back()
                    ->with('info', 'Esta plantilla ya es la predeterminada.');
            }
            
            // Establecer como predeterminada
            $this->templateService->setAsDefault($template);
            
            return redirect()->back()
                ->with('success', 'Plantilla establecida como predeterminada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al establecer la plantilla como predeterminada: ' . $e->getMessage());
        }
    }

    /**
     * Regenerar todas las credenciales usando esta plantilla.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerateCredentials($uuid)
    {
        try {
            // Obtener plantilla
            $template = $this->templateService->findByUuid($uuid);
            
            if (!$template) {
                abort(404, 'Plantilla no encontrada');
            }
            
            // Obtener el evento de la plantilla
            $event = $template->event;
            if (!$event) {
                return redirect()->back()
                    ->with('error', 'La plantilla no tiene un evento asociado.');
            }
            
            // Obtener todas las credenciales activas del evento
            $credentials = \App\Models\Credential::whereHas('accreditationRequest', function ($query) use ($event) {
                $query->where('event_id', $event->id)
                      ->where('status', 'approved');
            })->get();
            
            if ($credentials->isEmpty()) {
                return redirect()->back()
                    ->with('info', 'No hay credenciales activas para regenerar en este evento.');
            }
            
            // Despachar job para regenerar credenciales en segundo plano
            \App\Jobs\RegenerateCredentialsJob::dispatch($event, $template);
            
            return redirect()->back()
                ->with('success', "Se están regenerando {$credentials->count()} credenciales usando esta plantilla. El proceso puede tomar varios minutos.");
                
        } catch (\Exception $e) {
            \Log::error('Error regenerando credenciales', [
                'template_uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al regenerar credenciales: ' . $e->getMessage());
        }
    }
}
