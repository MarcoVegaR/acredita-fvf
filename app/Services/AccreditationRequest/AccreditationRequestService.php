<?php

namespace App\Services\AccreditationRequest;

use App\Enums\AccreditationStatus;
use App\Events\AccreditationRequest\RequestSubmitted;
use App\Models\AccreditationRequest;
use App\Models\Event;
use App\Repositories\AccreditationRequest\AccreditationRequestRepositoryInterface;
use App\Repositories\Event\EventRepositoryInterface;
use App\Repositories\Zone\ZoneRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccreditationRequestService implements AccreditationRequestServiceInterface
{
    protected $requestRepository;
    protected $eventRepository;
    protected $zoneRepository;

    /**
     * AccreditationRequestService constructor.
     *
     * @param AccreditationRequestRepositoryInterface $requestRepository
     * @param EventRepositoryInterface $eventRepository
     * @param ZoneRepositoryInterface $zoneRepository
     */
    public function __construct(
        AccreditationRequestRepositoryInterface $requestRepository,
        EventRepositoryInterface $eventRepository,
        ZoneRepositoryInterface $zoneRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->eventRepository = $eventRepository;
        $this->zoneRepository = $zoneRepository;
    }

    /**
     * Obtener solicitudes paginadas con filtros
     * 
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedRequests(Request $request): LengthAwarePaginator
    {
        return $this->requestRepository->getPaginatedRequests(
            $request,
            Auth::user()
        );
    }

    /**
     * Crear una nueva solicitud de acreditación
     * 
     * @param array $data
     * @return AccreditationRequest
     */
    public function createRequest(array $data): AccreditationRequest
    {
        // Verificar que no exista una solicitud activa para este empleado y evento
        if ($this->hasActiveRequest($data['employee_id'], $data['event_id'])) {
            throw new Exception('Ya existe una solicitud activa para este empleado en este evento.');
        }

        DB::beginTransaction();
        try {
            // Preparar datos para la creación
            $requestData = [
                'employee_id' => $data['employee_id'],
                'event_id' => $data['event_id'],
                'comments' => $data['notes'] ?? $data['comments'] ?? null,
                'created_by' => Auth::id(),
                'status' => AccreditationStatus::Draft->value,
                // Solo asignamos requested_at cuando se envía la solicitud, no al crearla
            ];

            // Crear la solicitud
            $request = $this->requestRepository->create($requestData);

            // Asociar zonas
            if (isset($data['zones']) && is_array($data['zones'])) {
                $this->requestRepository->addZones($request, $data['zones']);
            }

            DB::commit();
            return $request;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar una solicitud existente
     * 
     * @param AccreditationRequest $request
     * @param array $data
     * @return AccreditationRequest
     */
    public function updateRequest(AccreditationRequest $request, array $data): AccreditationRequest
    {
        // Solo se pueden actualizar borradores
        if (!$request->isDraft()) {
            throw new Exception('Solo se pueden actualizar solicitudes en borrador.');
        }

        DB::beginTransaction();
        try {
            // Preparar datos para la actualización
            $requestData = [];
            
            if (isset($data['employee_id'])) {
                $requestData['employee_id'] = $data['employee_id'];
            }
            
            if (isset($data['event_id'])) {
                $requestData['event_id'] = $data['event_id'];
            }
            
            if (isset($data['comments'])) {
                $requestData['comments'] = $data['comments'];
            }

            // Actualizar la solicitud
            $request = $this->requestRepository->update($request->id, $requestData);

            // Actualizar zonas si están presentes
            if (isset($data['zones'])) {
                if (is_array($data['zones']) && count($data['zones']) > 0) {
                    $this->requestRepository->syncZones($request, $data['zones']);
                } else {
                    // Si no hay zonas, limpiar todas
                    $this->requestRepository->syncZones($request, []);
                }
            }

            DB::commit();
            return $request->fresh(['employee', 'event', 'zones']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Enviar una solicitud (cambiar de draft a submitted)
     * 
     * @param AccreditationRequest $request
     * @return AccreditationRequest
     */
    public function submitRequest(AccreditationRequest $request): AccreditationRequest
    {
        Log::info('[SERVICE SUBMIT] Iniciando envío de solicitud', [
            'request_id' => $request->id,
            'uuid' => $request->uuid,
            'current_status' => $request->status->value,
            'employee_id' => $request->employee_id
        ]);

        // Verificar que la solicitud esté en estado borrador
        if ($request->status !== AccreditationStatus::Draft) {
            Log::error('[SERVICE SUBMIT] Solicitud no está en borrador', [
                'current_status' => $request->status->value
            ]);
            throw new \InvalidArgumentException('Solo se pueden enviar solicitudes en estado borrador.');
        }

        // Cargar la relación del empleado y su proveedor si no están cargadas
        if (!$request->relationLoaded('employee') || !$request->employee->relationLoaded('provider')) {
            Log::info('[SERVICE SUBMIT] Cargando relación employee.provider');
            $request->load('employee.provider');
        }

        Log::info('[SERVICE SUBMIT] Provider cargado', [
            'provider_id' => $request->employee->provider->id,
            'provider_type' => $request->employee->provider->type,
            'is_internal' => $request->employee->provider->isInternal()
        ]);

        // Si es un proveedor interno, pasa directo a under_review
        if ($request->employee->provider->isInternal()) {
            Log::info('[SERVICE SUBMIT] Proveedor interno - pasando directo a under_review');
            $request->update([
                'status' => AccreditationStatus::UnderReview,
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_comments' => 'Proveedor interno - Visto bueno automático'
            ]);
        } else {
            Log::info('[SERVICE SUBMIT] Proveedor externo - pasando a submitted');
            // Proveedor externo - pasa a submitted para visto bueno del área
            $request->update([
                'status' => AccreditationStatus::Submitted,
                'submitted_at' => now()
            ]);
        }

        $updatedRequest = $request->fresh();
        Log::info('[SERVICE SUBMIT] Solicitud actualizada exitosamente', [
            'new_status' => $updatedRequest->status->value
        ]);

        // Disparar evento para notificaciones
        event(new RequestSubmitted($updatedRequest));
        
        return $updatedRequest;
    }

    /**
     * Eliminar una solicitud
     * 
     * @param AccreditationRequest $request
     * @return bool
     */
    public function deleteRequest(AccreditationRequest $request): bool
    {
        Log::info('[SERVICE DELETE] Iniciando eliminación de solicitud', [
            'request_id' => $request->id,
            'uuid' => $request->uuid,
            'status' => $request->status->value
        ]);

        // Solo se pueden eliminar borradores (comentado por ahora para permitir eliminación en cualquier estado por admin)
        // if ($request->status !== AccreditationStatus::DRAFT) {
        //     throw new Exception('Solo se pueden eliminar solicitudes en borrador.');
        // }

        $deleted = $request->delete();

        Log::info('[SERVICE DELETE] Solicitud eliminada exitosamente', [
            'deleted' => $deleted
        ]);
        
        return $deleted;
    }

    /**
     * Obtener eventos activos para el wizard
     * 
     * @return Collection
     */
    public function getActiveEvents(): Collection
    {
        return $this->eventRepository->getActive();
    }

    /**
     * Obtener zonas para un evento específico
     * 
     * @param int $eventId
     * @return Collection
     */
    public function getZonesForEvent(int $eventId): Collection
    {
        $event = $this->eventRepository->find($eventId);
        return $event->zones;
    }

    /**
     * Verificar si ya existe una solicitud activa para el empleado y evento
     * 
     * @param int $employeeId
     * @param int $eventId
     * @return bool
     */
    public function hasActiveRequest(int $employeeId, int $eventId): bool
    {
        $existingRequests = $this->requestRepository->getByEmployeeEvent($employeeId, $eventId);
        return $existingRequests->isNotEmpty();
    }

    /**
     * Aprobar una solicitud de acreditación
     * 
     * @param AccreditationRequest $request
     * @return AccreditationRequest
     */
    public function approveRequest(AccreditationRequest $request): AccreditationRequest
    {
        Log::info('[APPROVE SERVICE] ==> Iniciando aprobación en servicio', [
            'uuid' => $request->uuid,
            'id' => $request->id,
            'current_status' => $request->status->value,
            'employee' => $request->employee->first_name . ' ' . $request->employee->last_name,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
        
        Log::info('[APPROVE SERVICE] Verificando estado válido para aprobación', [
            'current_status' => $request->status->value,
            'allowed_statuses' => ['Submitted', 'UnderReview']
        ]);
        
        if (!in_array($request->status, [AccreditationStatus::Submitted, AccreditationStatus::UnderReview])) {
            Log::error('[APPROVE SERVICE] Estado no válido para aprobación', [
                'current_status' => $request->status->value,
                'allowed_statuses' => ['Submitted', 'UnderReview']
            ]);
            throw new Exception('Solo se pueden aprobar solicitudes enviadas o en revisión.');
        }

        Log::info('[APPROVE SERVICE] Estado válido, procediendo con la actualización...');
        
        $updateData = [
            'status' => AccreditationStatus::Approved,
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ];
        
        Log::info('[APPROVE SERVICE] Datos para actualización:', $updateData);
        
        $request->update($updateData);
        
        Log::info('[APPROVE SERVICE] Solicitud actualizada, obteniendo datos frescos...');
        $freshRequest = $request->fresh();
        
        Log::info('[APPROVE SERVICE] Aprobación completada exitosamente', [
            'uuid' => $freshRequest->uuid,
            'new_status' => $freshRequest->status->value,
            'approved_at' => $freshRequest->approved_at?->toISOString(),
            'approved_by' => $freshRequest->approved_by
        ]);
        
        // 🚀 NUEVA LÓGICA: Crear credencial inicial y disparar job asíncrono
        Log::info('[APPROVE SERVICE] Iniciando generación de credencial...');
        
        try {
            $credentialService = app(\App\Services\Credential\CredentialServiceInterface::class);
            $credential = $credentialService->createCredentialForRequest($freshRequest);
            
            // Disparar job asíncrono para generación completa
            \App\Jobs\GenerateCredentialJob::dispatch($credential);
            
            Log::info('[APPROVE SERVICE] Job de generación de credencial disparado', [
                'credential_id' => $credential->id,
                'credential_uuid' => $credential->uuid
            ]);
            
        } catch (Exception $e) {
            Log::error('[APPROVE SERVICE] Error creando credencial', [
                'request_uuid' => $freshRequest->uuid,
                'error' => $e->getMessage()
            ]);
            
            // No fallar la aprobación si hay error en credencial
            // La credencial se puede generar manualmente después
        }
        
        return $freshRequest;
    }

    /**
     * Suspender una credencial aprobada
     * 
     * @param AccreditationRequest $request
     * @param string|null $reason Motivo de la suspensión
     * @return AccreditationRequest
     */
    public function suspendRequest(AccreditationRequest $request, ?string $reason = null): AccreditationRequest
    {
        Log::info('[SUSPEND SERVICE] Iniciando suspensión de credencial', [
            'uuid' => $request->uuid,
            'id' => $request->id,
            'current_status' => $request->status->value,
            'employee' => $request->employee->first_name . ' ' . $request->employee->last_name,
            'user_id' => auth()->id(),
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]);
        
        // Verificar que la solicitud esté aprobada
        if ($request->status !== AccreditationStatus::Approved) {
            Log::error('[SUSPEND SERVICE] Estado no válido para suspensión', [
                'current_status' => $request->status->value,
                'required_status' => 'approved'
            ]);
            throw new Exception('Solo se pueden suspender credenciales aprobadas.');
        }

        // Validar que exista un motivo
        if (empty($reason)) {
            Log::error('[SUSPEND SERVICE] Motivo de suspensión requerido');
            throw new Exception('Se requiere un motivo para suspender la credencial.');
        }
        
        Log::info('[SUSPEND SERVICE] Estado válido, procediendo con la suspensión...');
        
        $updateData = [
            'status' => AccreditationStatus::Suspended,
            'suspended_at' => now(),
            'suspended_by' => auth()->id(),
            'suspension_reason' => $reason
        ];
        
        Log::info('[SUSPEND SERVICE] Datos para actualización:', $updateData);
        
        $request->update($updateData);
        
        Log::info('[SUSPEND SERVICE] Solicitud actualizada, obteniendo datos frescos...');
        $freshRequest = $request->fresh();
        
        Log::info('[SUSPEND SERVICE] Suspensión completada exitosamente', [
            'uuid' => $freshRequest->uuid,
            'new_status' => $freshRequest->status->value,
            'suspended_at' => $freshRequest->suspended_at?->toISOString(),
            'suspended_by' => $freshRequest->suspended_by
        ]);
        
        // Marcar la credencial como suspendida si existe
        if ($request->credential) {
            try {
                $credential = $request->credential;
                $credential->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'suspended_by' => auth()->id()
                ]);
                
                Log::info('[SUSPEND SERVICE] Credencial marcada como suspendida', [
                    'credential_id' => $credential->id,
                    'credential_uuid' => $credential->uuid
                ]);
            } catch (\Exception $e) {
                Log::error('[SUSPEND SERVICE] Error actualizando credencial', [
                    'request_uuid' => $freshRequest->uuid,
                    'error' => $e->getMessage()
                ]);
                // Continuamos aunque haya error en la actualización de la credencial
            }
        }
        
        return $freshRequest;
    }

    /**
     * Rechazar una solicitud de acreditación
     * 
     * @param AccreditationRequest $request
     * @param string|null $reason
     * @return AccreditationRequest
     */
    public function rejectRequest(AccreditationRequest $request, ?string $reason = null): AccreditationRequest
    {
        if (!in_array($request->status, [AccreditationStatus::Submitted, AccreditationStatus::UnderReview])) {
            throw new Exception('Solo se pueden rechazar solicitudes enviadas o en revisión.');
        }

        $request->update([
            'status' => AccreditationStatus::Rejected,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $reason
        ]);
        
        return $request->fresh();
    }

    /**
     * Devolver una solicitud a borrador para corrección
     * 
     * @param AccreditationRequest $request
     * @param string|null $reason
     * @return AccreditationRequest
     */
    public function returnToDraft(AccreditationRequest $request, ?string $reason = null): AccreditationRequest
    {
        if (!in_array($request->status, [AccreditationStatus::Submitted, AccreditationStatus::UnderReview])) {
            throw new Exception('Solo se pueden devolver a borrador solicitudes enviadas o en revisión.');
        }

        $request->update([
            'status' => AccreditationStatus::Draft,
            'requested_at' => null,
            'returned_at' => now(),
            'returned_by' => auth()->id(),
            'return_reason' => $reason
        ]);
        
        return $request->fresh();
    }

    /**
     * Dar visto bueno a una solicitud (solo para proveedores externos)
     * 
     * @param AccreditationRequest $request
     * @param string|null $comments
     * @return AccreditationRequest
     */
    public function reviewRequest(AccreditationRequest $request, ?string $comments = null): AccreditationRequest
    {
        if ($request->status !== AccreditationStatus::Submitted) {
            throw new Exception('Solo se pueden revisar solicitudes enviadas.');
        }

        $request->update([
            'status' => AccreditationStatus::UnderReview,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'review_comments' => $comments
        ]);
        
        return $request->fresh();
    }
    
    /**
     * Crear múltiples solicitudes de acreditación de forma masiva
     * 
     * @param array $data
     * @return array
     */
    public function createBulkRequests(array $data): array
    {
        Log::info('AccreditationRequestService::createBulkRequests - Iniciando creación masiva', [
            'event_id' => $data['event_id'],
            'employees_count' => count($data['employee_zones']),
            'user_id' => auth()->id()
        ]);
        
        $results = [
            'created' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        DB::beginTransaction();
        
        try {
            foreach ($data['employee_zones'] as $employeeId => $zones) {
                // Verificar si ya existe una solicitud activa
                if ($this->hasActiveRequest($employeeId, $data['event_id'])) {
                    $results['skipped'][] = [
                        'employee_id' => $employeeId,
                        'reason' => 'Ya existe una solicitud activa para este empleado y evento'
                    ];
                    continue;
                }
                
                try {
                    $requestData = [
                        'employee_id' => $employeeId,
                        'event_id' => $data['event_id'],
                        'zones' => $zones,
                        'notes' => $data['notes'] ?? null,
                        'status' => AccreditationStatus::Draft,
                        'created_by' => auth()->id()
                    ];
                    
                    $request = $this->createRequest($requestData);
                    
                    $results['created'][] = [
                        'employee_id' => $employeeId,
                        'request_id' => $request->id,
                        'request_uuid' => $request->uuid
                    ];
                    
                    Log::info('AccreditationRequestService::createBulkRequests - Solicitud creada', [
                        'employee_id' => $employeeId,
                        'request_uuid' => $request->uuid
                    ]);
                    
                } catch (Exception $e) {
                    Log::error('AccreditationRequestService::createBulkRequests - Error creando solicitud', [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results['errors'][] = [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            Log::info('AccreditationRequestService::createBulkRequests - Proceso completado', [
                'created_count' => count($results['created']),
                'skipped_count' => count($results['skipped']),
                'errors_count' => count($results['errors'])
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('AccreditationRequestService::createBulkRequests - Error en transacción', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
        
        return $results;
    }
}
