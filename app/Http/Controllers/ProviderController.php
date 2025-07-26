<?php

namespace App\Http\Controllers;

use App\Http\Requests\Provider\StoreProviderRequest;
use App\Http\Requests\Provider\UpdateProviderRequest;
use App\Models\Provider;
use App\Services\Provider\ProviderServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProviderController extends Controller
{
    /**
     * @var ProviderServiceInterface
     */
    protected $providerService;

    /**
     * Create a new controller instance.
     *
     * @param ProviderServiceInterface $providerService
     */
    public function __construct(ProviderServiceInterface $providerService)
    {
        $this->providerService = $providerService;
        // No usamos authorizeResource para permitir más flexibilidad en los permisos
        // $this->authorizeResource(Provider::class, 'provider');
    }

    /**
     * Display a listing of the providers.
     */
    public function index(Request $request)
    {
        $providers = $this->providerService->getPaginatedProviders($request);
        
        // Obtener todas las áreas para el filtro
        $areas = \App\Models\Area::select('id', 'name')->orderBy('name')->get();
        
        // Obtener estadísticas de proveedores
        $user = auth()->user();
        $query = \App\Models\Provider::query();
        
        // Filtrar por área si el usuario es area_manager
        if ($user && $user->hasRole('area_manager')) {
            $managedAreas = $user->managedAreas()->pluck('id')->toArray();
            if (!empty($managedAreas)) {
                $query->whereIn('area_id', $managedAreas);
            }
        }
        
        $stats = [
            'total' => $query->count(),
            'active' => (clone $query)->where('active', true)->count(),
            'internal' => (clone $query)->where('type', 'internal')->count(),
            'external' => (clone $query)->where('type', 'external')->count(),
        ];

        // Procesar los filtros para manejar el valor 'all'
        $filters = $request->only(['search', 'area_id', 'type', 'active', 'per_page', 'sort', 'order']);
        
        // Si el tipo o el área es 'all', lo convertimos a null para que el frontend lo maneje como "todos"
        if (isset($filters['type']) && $filters['type'] === 'all') {
            $filters['type'] = null;
        }
        
        if (isset($filters['area_id']) && $filters['area_id'] === 'all') {
            $filters['area_id'] = null;
        }
        
        return Inertia::render('providers/index', [
            'providers' => $providers,
            'areas' => $areas,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new provider.
     */
    public function create()
    {
        // Verificar permisos manualmente (permitiendo ambos permisos)
        if (!auth()->user()->can('provider.manage') && !auth()->user()->can('provider.manage_own_area')) {
            abort(403, 'No tiene permisos para crear proveedores.');
        }
        
        // Obtener todas las áreas activas y sus gerentes asignados
        $areas = \App\Models\Area::select('id', 'name', 'description', 'manager_user_id')
            ->with(['manager:id,name,email'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
        
        // Obtener las áreas que ya tienen proveedores internos activos
        $areasWithInternalProviders = \App\Models\Provider::where('type', 'internal')
            ->where('active', true)
            ->pluck('area_id')
            ->toArray();
            
        // Log para depuración
        \Log::info('Areas con proveedores internos: ' . json_encode($areasWithInternalProviders));
            
        return Inertia::render('providers/create', [
            'provider' => null,
            'areas' => $areas,
            'areasWithInternalProviders' => $areasWithInternalProviders,
        ]);
    }

    /**
     * Store a newly created provider in storage.
     */
    public function store(StoreProviderRequest $request)
    {
        // Verificar permisos manualmente (permitiendo ambos permisos)
        $user = auth()->user();
        if (!$user->can('provider.manage') && !$user->can('provider.manage_own_area')) {
            abort(403, 'No tiene permisos para crear proveedores.');
        }
        
        try {
            $data = $request->validated();
            
            // Si es area_manager, aplicar validaciones especiales
            if ($user->hasRole('area_manager') && !$user->can('provider.manage')) {
                // 1. Validar que el área seleccionada sea una que gestiona
                $managedAreaIds = $user->managedAreas()->pluck('id')->toArray();
                
                if (empty($managedAreaIds)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'No tiene áreas asignadas para gestionar.');
                }
                
                if (!in_array($data['area_id'], $managedAreaIds)) {
                    // Registrar intento de acceso no autorizado
                    \Illuminate\Support\Facades\Log::warning('Intento de crear proveedor en área no autorizada', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'role' => 'area_manager',
                        'attempted_area_id' => $data['area_id'],
                        'managed_areas' => $managedAreaIds
                    ]);
                    
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Solo puede crear proveedores en las áreas que usted gestiona.');
                }
                
                // 2. Validar que solo cree proveedores externos (no internos)
                if ($data['type'] !== 'external') {
                    // Registrar intento de crear proveedor interno
                    \Illuminate\Support\Facades\Log::warning('Intento de crear proveedor interno por area_manager', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'attempted_type' => $data['type']
                    ]);
                    
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Como gerente de área, solo puede crear proveedores externos.');
                }
            }
            
            // Determinar si es un proveedor interno o externo
            if ($data['type'] === 'internal') {
                // Crear proveedor interno (posiblemente sin usuario asignado)
                $provider = $this->providerService->createInternalProvider(
                    $data['area_id'], 
                    $data['user_id'] ?? null,
                    $data
                );
            } else {
                // Crear proveedor externo (con usuario asignado)
                $provider = $this->providerService->createExternalProvider($data);
            }
            
            return redirect()->route('providers.index')
                ->with('success', 'Proveedor creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified provider.
     */
    public function show(Provider $provider)
    {
        // Obtener los datos del proveedor
        $providerData = $this->providerService->getProviderForDisplay($provider->uuid);
        
        // Obtener tipos de documentos para el módulo de proveedores
        $documentTypes = [];
        if (class_exists('\App\Models\DocumentType')) {
            $documentTypes = \App\Models\DocumentType::where('module', 'providers')
                ->orWhereNull('module')
                ->get();
        }
        
        // Obtener tipos de imágenes para el módulo de proveedores
        $imageTypes = [];
        if (class_exists('\App\Models\ImageType')) {
            $imageTypes = \App\Models\ImageType::where('module', 'providers')
                ->orWhereNull('module')
                ->get();
        }
        
        // Obtener todos los permisos del usuario autenticado
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
        
        return Inertia::render('providers/show', [
            'provider' => $providerData['provider'],
            'documentTypes' => $documentTypes,
            'imageTypes' => $imageTypes,
            'userPermissions' => $userPermissions,
        ]);
    }

    /**
     * Show the form for editing the specified provider.
     */
    public function edit(Provider $provider)
    {
        // Cargar relaciones necesarias
        $provider->load(['area', 'user']);
        
        // Obtener todas las áreas activas y sus gerentes asignados
        $areas = \App\Models\Area::select('id', 'name', 'description', 'manager_user_id')
            ->with(['manager:id,name,email'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
        
        // Obtener las áreas que ya tienen proveedores internos activos (excluyendo el área del proveedor actual si es interno)
        $areasWithInternalProvidersQuery = \App\Models\Provider::where('type', 'internal')
            ->where('active', true);
        
        // Si estamos editando un proveedor interno, excluimos su área para permitir mantenerla
        if ($provider->type === 'internal') {
            $areasWithInternalProvidersQuery->where('providers.id', '!=', $provider->id);
        }
        
        $areasWithInternalProviders = $areasWithInternalProvidersQuery->pluck('area_id')->toArray();
        
        // Log para depuración
        \Log::info('Areas con proveedores internos (edit): ' . json_encode($areasWithInternalProviders));
            
        return Inertia::render('providers/edit', [
            'provider' => $provider,
            'areas' => $areas,
            'areasWithInternalProviders' => $areasWithInternalProviders,
        ]);
    }

    /**
     * Update the specified provider in storage.
     */
    public function update(UpdateProviderRequest $request, Provider $provider)
    {
        try {
            $this->providerService->updateProvider($provider->uuid, $request->validated());
            
            return redirect()->route('providers.index')
                ->with('success', 'Proveedor actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Toggle provider active status.
     */
    public function toggleActive(Request $request, Provider $provider)
    {
        $this->authorize('toggleActive', $provider);
        
        try {
            $active = $request->boolean('active', !$provider->active);
            $this->providerService->toggleActiveProvider($provider->uuid, $active);
            
            $message = $active ? 'Proveedor activado exitosamente.' : 'Proveedor desactivado exitosamente.';
            
            return redirect()->route('providers.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al cambiar estado del proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Reset provider password.
     */
    public function resetPassword(Provider $provider)
    {
        $this->authorize('resetPassword', $provider);
        
        try {
            $this->providerService->resetProviderPassword($provider->uuid);
            
            return redirect()->route('providers.index')
                ->with('success', 'Contraseña restablecida exitosamente. Se ha enviado un correo con la nueva contraseña.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al restablecer contraseña: ' . $e->getMessage());
        }
    }
}
