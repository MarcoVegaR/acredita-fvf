<?php

namespace App\Repositories\AccreditationRequest;

use App\Enums\AccreditationStatus;
use App\Models\AccreditationRequest;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccreditationRequestRepository extends BaseRepository implements AccreditationRequestRepositoryInterface
{
    /**
     * AccreditationRequestRepository constructor.
     *
     * @param AccreditationRequest $model
     */
    public function __construct(AccreditationRequest $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener solicitudes paginadas con filtros aplicados
     * 
     * @param Request $request
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function getPaginatedRequests(Request $request, User $user): LengthAwarePaginator
    {
        $query = $this->model->with(['employee.provider', 'event', 'zones']);
        
        // Aplicar filtros por evento
        if ($request->has('event_id') && $request->event_id) {
            $query->forEvent($request->event_id);
        }
        
        // Aplicar filtros por estado
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Aplicar scope para filtrar por permisos del usuario
        $query = $this->scopeForUser($query, $user);
        
        // Ordenar resultados
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($request->input('per_page', 15));
    }

    /**
     * Encontrar una solicitud por su UUID
     * 
     * @param string $uuid
     * @param array $relations
     * @return AccreditationRequest
     */
    public function findByUuid(string $uuid, array $relations = []): AccreditationRequest
    {
        $query = $this->model->where('uuid', $uuid);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->firstOrFail();
    }

    /**
     * Obtener solicitudes por empleado y evento
     * 
     * @param int $employeeId
     * @param int $eventId
     * @return Collection
     */
    public function getByEmployeeEvent(int $employeeId, int $eventId): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('event_id', $eventId)
            ->whereNotIn('status', [AccreditationStatus::Draft->value])
            ->get();
    }

    /**
     * Obtener solicitudes por evento y estado
     * 
     * @param int $eventId
     * @param AccreditationStatus|null $status
     * @return Collection
     */
    public function getByEventAndStatus(int $eventId, ?AccreditationStatus $status): Collection
    {
        $query = $this->model->where('event_id', $eventId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }

    /**
     * Agregar zonas a una solicitud
     * 
     * @param AccreditationRequest $request
     * @param array $zoneIds
     * @return void
     */
    public function addZones(AccreditationRequest $request, array $zoneIds): void
    {
        $request->zones()->sync($zoneIds);
    }

    /**
     * Sincronizar zonas de una solicitud
     * 
     * @param AccreditationRequest $request
     * @param array $zoneIds
     * @return void
     */
    public function syncZones(AccreditationRequest $request, array $zoneIds): void
    {
        $request->zones()->sync($zoneIds);
    }

    /**
     * Cambiar el estado a Submitted
     * 
     * @param AccreditationRequest $request
     * @return AccreditationRequest
     */
    public function submit(AccreditationRequest $request): AccreditationRequest
    {
        $request->status = AccreditationStatus::Submitted;
        $request->requested_at = now();
        $request->save();

        return $request;
    }

    /**
     * Aplicar scope para filtrar según permisos del usuario
     * 
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeForUser($query, User $user)
    {
        // El filtrado de solicitudes por rol de usuario está activo
        
        // Los administradores pueden ver todo
        if ($user->hasRole('admin')) {
            return $query;
        }
        
        // Los gestores de área pueden ver solicitudes de su área
        if ($user->hasRole('area_manager')) {
            if ($user->managedArea) {
                $areaId = $user->managedArea->id;
                return $query->whereHas('employee.provider', function($q) use ($areaId) {
                    $q->where('area_id', $areaId);
                });
            } else {
                // Si no tiene área asignada, no debe ver nada
                return $query->whereRaw('1 = 0');
            }
        }
        
        // Los proveedores solo ven sus propias solicitudes
        if ($user->hasRole('provider')) {
            $providerId = $user->provider->id;
            return $query->whereHas('employee', function($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            });
        }
        
        // Otros roles no ven nada (devolver consulta vacía)
        return $query->whereRaw('1 = 0');
    }
}
