<?php

namespace App\Http\Controllers;

use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\Area;
use App\Models\User;
use App\Models\Provider;
use App\Services\Area\AreaServiceInterface;
use App\Repositories\Provider\ProviderRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AreaController extends BaseController
{
    /**
     * @var AreaServiceInterface
     */
    protected $areaService;

    /**
     * AreaController constructor.
     *
     * @param AreaServiceInterface $areaService
     */
    public function __construct(AreaServiceInterface $areaService)
    {
        $this->areaService = $areaService;
    }

    /**
     * Display a listing of areas.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            $areas = $this->areaService->getPaginatedAreas($request);
            $stats = $this->areaService->getAreaStats();
            
            // Registro de auditoría
            $this->logAction('listar', 'áreas', null, [
                'filters' => $request->all()
            ]);
            
            return $this->respondWithSuccess('areas/index', [
                'areas' => $areas,
                'stats' => $stats,
                'filters' => $request->only(['search', 'active', 'code', 'sort', 'order', 'per_page'])
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar áreas');
        }
    }

    /**
     * Show the form for creating a new area.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        try {
            // Registro de auditoría
            $this->logAction('formulario_crear', 'área');
            
            return $this->respondWithSuccess('areas/create');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Formulario de creación de área');
        }
    }

    /**
     * Store a newly created area in storage.
     *
     * @param StoreAreaRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAreaRequest $request)
    {
        try {
            // La validación ya se ha realizado en el Form Request
            $data = $request->validated();
            
            // Delegar toda la lógica al servicio
            $area = $this->areaService->createArea($data);
            
            // Registro de auditoría
            $this->logAction('crear', 'área', $area->id);
            
            return $this->redirectWithSuccess('areas.index', [], 'Área creada correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear área');
        }
    }

    /**
     * Display the specified area.
     *
     * @param Area $area
     * @return \Inertia\Response
     */
    public function show(Area $area)
    {
        try {
            // Obtener el área por ID
            $area = $this->areaService->getAreaById($area->id);
            
            // Registro de auditoría
            $this->logAction('ver', 'área', $area->id);
            
            return $this->respondWithSuccess('areas/show', [
                'area' => $area
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Ver área');
        }
    }

    /**
     * Show the form for editing the specified area.
     *
     * @param Area $area
     * @return \Inertia\Response
     */
    public function edit(Area $area)
    {
        try {
            // Obtener el área por ID
            $area = $this->areaService->getAreaById($area->id);
            
            // Registro de auditoría
            $this->logAction('formulario_editar', 'área', $area->id);
            
            return $this->respondWithSuccess('areas/edit', [
                'area' => $area
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Formulario de edición de área');
        }
    }

    /**
     * Update the specified area in storage.
     *
     * @param UpdateAreaRequest $request
     * @param Area $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAreaRequest $request, Area $area)
    {
        try {
            // La validación ya se ha realizado en el Form Request
            $data = $request->validated();
            
            // Delegar toda la lógica al servicio
            $this->areaService->updateArea($area, $data);
            
            // Registro de auditoría
            $this->logAction('actualizar', 'área', $area->id);
            
            return $this->redirectWithSuccess('areas.index', [], 'Área actualizada correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Actualizar área');
        }
    }

    /**
     * Remove the specified area from storage.
     *
     * @param Area $area
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Area $area)
    {
        try {
            // Eliminar el área
            $this->areaService->deleteArea($area);
            
            // Registro de auditoría
            $this->logAction('eliminar', 'área', $area->id);
            
            return $this->redirectWithSuccess('areas.index', [], 'Área eliminada correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Eliminar área');
        }
    }
    
    /**
     * Asigna un gerente al área y crea/actualiza un proveedor interno.
     *
     * @param Request $request
     * @param Area $area
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignManager(Request $request, Area $area)
    {
        try {
            $validatedData = $request->validate([
                'manager_user_id' => 'nullable|exists:users,id'
            ]);
            
            $managerId = $validatedData['manager_user_id'];
            
            // Validate that the user is not already managing another area
            if ($managerId) {
                $user = User::findOrFail($managerId);
                $managedArea = $user->managedArea;
                
                if ($managedArea && $managedArea->id != $area->id) {
                    throw new \Exception("Este usuario ya es gerente del área '{$managedArea->name}'. Un gerente solo puede gestionar un área a la vez.");
                }
            }
            
            DB::transaction(function () use ($area, $managerId) {
                // Actualizar manager_user_id en el área
                $area->manager_user_id = $managerId;
                $area->save();
                
                // Si hay un gerente asignado, crear/actualizar proveedor interno
                if ($managerId) {
                    // Asegurarse que el usuario tenga rol area_manager
                    $user = User::findOrFail($managerId);
                    if (!$user->hasRole('area_manager')) {
                        $user->assignRole('area_manager');
                    }
                    
                    // Crear o actualizar el proveedor interno
                    app(ProviderRepositoryInterface::class)
                        ->createOrUpdateInternal($area, $managerId);
                } else {
                    // Si se quitó el gerente, establecer user_id a NULL en el proveedor interno
                    // y marcarlo como inactivo si existe
                    Provider::where([
                        'area_id' => $area->id, 
                        'type' => 'internal'
                    ])->update([
                        'user_id' => null,
                        'active' => false
                    ]);
                }
            });
            
            // Registrar acción para auditoría
            $this->logAction(
                $managerId ? 'asignar' : 'quitar', 
                'gerente_area', 
                $area->id, 
                ['area_id' => $area->id, 'manager_id' => $managerId]
            );
            
            return response()->json([
                'success' => true,
                'message' => $managerId ? 'Gerente asignado correctamente' : 'Gerente removido correctamente'
            ]);
            
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al asignar gerente',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
