<?php

namespace App\Http\Controllers;

use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\Area;
use App\Services\Area\AreaServiceInterface;
use Illuminate\Http\Request;
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
            // Delegar toda la lógica al servicio
            $this->areaService->deleteArea($area);
            
            // Registro de auditoría
            $this->logAction('eliminar', 'área', $area->id);
            
            return $this->redirectWithSuccess('areas.index', [], 'Área eliminada correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Eliminar área');
        }
    }
}
