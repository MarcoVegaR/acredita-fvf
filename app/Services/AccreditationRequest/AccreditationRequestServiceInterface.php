<?php

namespace App\Services\AccreditationRequest;

use App\Models\AccreditationRequest;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface AccreditationRequestServiceInterface
{
    /**
     * Obtener solicitudes paginadas con filtros
     * 
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedRequests(Request $request): LengthAwarePaginator;
    
    /**
     * Crear una nueva solicitud de acreditación
     * 
     * @param array $data
     * @return AccreditationRequest
     */
    public function createRequest(array $data): AccreditationRequest;
    
    /**
     * Actualizar una solicitud existente
     * 
     * @param AccreditationRequest $request
     * @param array $data
     * @return AccreditationRequest
     */
    public function updateRequest(AccreditationRequest $request, array $data): AccreditationRequest;
    
    /**
     * Enviar una solicitud (cambiar de draft a submitted)
     * 
     * @param AccreditationRequest $request
     * @return AccreditationRequest
     */
    public function submitRequest(AccreditationRequest $request): AccreditationRequest;
    
    /**
     * Eliminar una solicitud
     * 
     * @param AccreditationRequest $request
     * @return bool
     */
    public function deleteRequest(AccreditationRequest $request): bool;
    
    /**
     * Obtener eventos activos para el wizard
     * 
     * @return Collection
     */
    public function getActiveEvents(): Collection;
    
    /**
     * Obtener zonas para un evento específico
     * 
     * @param int $eventId
     * @return Collection
     */
    public function getZonesForEvent(int $eventId): Collection;
    
    /**
     * Verificar si ya existe una solicitud activa para el empleado y evento
     * 
     * @param int $employeeId
     * @param int $eventId
     * @return bool
     */
    public function hasActiveRequest(int $employeeId, int $eventId): bool;
    
    /**
     * Crear múltiples solicitudes de acreditación de forma masiva
     * 
     * @param array $data
     * @return array
     */
    public function createBulkRequests(array $data): array;

    /**
     * Suspender una credencial aprobada
     * 
     * @param AccreditationRequest $request
     * @param string|null $reason Motivo de la suspensión
     * @return AccreditationRequest
     */
    public function suspendRequest(AccreditationRequest $request, ?string $reason = null): AccreditationRequest;
}
