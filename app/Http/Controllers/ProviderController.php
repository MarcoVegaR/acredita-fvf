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
        $this->authorizeResource(Provider::class, 'provider');
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
        $stats = [
            'total' => \App\Models\Provider::count(),
            'active' => \App\Models\Provider::where('active', true)->count(),
            'internal' => \App\Models\Provider::where('type', 'internal')->count(),
            'external' => \App\Models\Provider::where('type', 'external')->count(),
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
        try {
            $data = $request->validated();
            
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
