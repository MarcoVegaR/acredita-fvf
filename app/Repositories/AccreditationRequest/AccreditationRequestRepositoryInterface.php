<?php

namespace App\Repositories\AccreditationRequest;

use App\Enums\AccreditationStatus;
use App\Models\AccreditationRequest;
use App\Models\User;
use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface AccreditationRequestRepositoryInterface extends RepositoryInterface
{
    /**
     * Obtener solicitudes paginadas con filtros aplicados
     * 
     * @param Request $request
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function getPaginatedRequests(Request $request, User $user): LengthAwarePaginator;

    /**
     * Encontrar una solicitud por su UUID
     * 
     * @param string $uuid
     * @param array $relations
     * @return AccreditationRequest
     */
    public function findByUuid(string $uuid, array $relations = []): AccreditationRequest;

    /**
     * Obtener solicitudes por empleado y evento
     * 
     * @param int $employeeId
     * @param int $eventId
     * @return Collection
     */
    public function getByEmployeeEvent(int $employeeId, int $eventId): Collection;

    /**
     * Obtener solicitudes por evento y estado
     * 
     * @param int $eventId
     * @param AccreditationStatus|null $status
     * @return Collection
     */
    public function getByEventAndStatus(int $eventId, ?AccreditationStatus $status): Collection;

    /**
     * Agregar zonas a una solicitud
     * 
     * @param AccreditationRequest $request
     * @param array $zoneIds
     * @return void
     */
    public function addZones(AccreditationRequest $request, array $zoneIds): void;

    /**
     * Sincronizar zonas de una solicitud
     * 
     * @param AccreditationRequest $request
     * @param array $zoneIds
     * @return void
     */
    public function syncZones(AccreditationRequest $request, array $zoneIds): void;

    /**
     * Cambiar el estado a Submitted
     * 
     * @param AccreditationRequest $request
     * @return AccreditationRequest
     */
    public function submit(AccreditationRequest $request): AccreditationRequest;

    /**
     * Aplicar scope para filtrar según permisos del usuario
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, User $user);
}
