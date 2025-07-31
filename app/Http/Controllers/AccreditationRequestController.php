<?php

namespace App\Http\Controllers;

use App\Enums\AccreditationStatus;
use App\Models\AccreditationRequest;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use App\Services\Event\EventServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AccreditationRequestController extends BaseController
{
    protected $accreditationRequestService;
    protected $eventService;

    /**
     * Create a new controller instance.
     *
     * @param AccreditationRequestServiceInterface $accreditationRequestService
     * @param EventServiceInterface $eventService
     */
    public function __construct(
        AccreditationRequestServiceInterface $accreditationRequestService,
        EventServiceInterface $eventService
    ) {
        $this->accreditationRequestService = $accreditationRequestService;
        $this->eventService = $eventService;
    }

    /**
     * Display a listing of accreditation requests.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            Gate::authorize('index', AccreditationRequest::class);

            $requests = $this->accreditationRequestService->getPaginatedRequests($request);
            $events = $this->eventService->getAllActive();
            
            // Obtener estadísticas de solicitudes filtradas por rol
            $query = AccreditationRequest::query();
            $user = auth()->user();
            
            // Filtrado según el rol del usuario
            if ($user->hasRole(['admin', 'security_manager']) || $user->can('accreditation_request.approve')) {
                // Admin y security_manager ven todas las solicitudes
                // No aplicar filtro adicional al query
            } elseif ($user->hasRole('area_manager')) {
                // Area manager solo ve solicitudes de proveedores de su área
                if ($user->managedArea) {
                    $areaId = $user->managedArea->id;
                    $query->whereHas('employee.provider', function ($q) use ($areaId) {
                        $q->where('area_id', $areaId);
                    });
                }
            } elseif ($user->hasRole('provider')) {
                // Provider solo ve sus propias solicitudes - usando la relación provider()
                // Verificar si el usuario tiene un proveedor asociado
                if ($user->provider) {
                    $providerId = $user->provider->id;
                    
                    // Aplicar filtro de proveedor para asegurar que solo vea sus propias solicitudes
                    $query->whereHas('employee', function ($q) use ($providerId) {
                        $q->where('provider_id', $providerId);
                    });
                } else {
                    // Si no tiene proveedor asociado, no mostrar ninguna solicitud
                    // hasta que se le asigne un proveedor válido
                    $query->where('id', 0);
                }
            }
            
            // Contar solicitudes filtradas según los permisos del usuario
            $totalRequests = (clone $query)->count();
            $draftRequests = (clone $query)->where('status', 'draft')->count();
            $submittedRequests = (clone $query)->where('status', 'submitted')->count();
            
            $stats = [
                'total' => $totalRequests,
                'draft' => $draftRequests,
                'submitted' => $submittedRequests
            ];
            
            $this->logAction('listar', 'solicitudes de acreditación', null, [
                'filters' => $request->all()
            ]);
            
            return $this->respondWithSuccess('accreditation-requests/index', [
                'accreditation_requests' => $requests,
                'events' => $events,
                'filters' => $request->only(['event_id', 'status', 'sort', 'direction', 'per_page']),
                'stats' => $stats
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar solicitudes de acreditación');
        }
    }

    /**
     * Display the specified accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Inertia\Response
     */
    public function show(AccreditationRequest $accreditationRequest)
    {
        try {
            Log::info('[SHOW] Iniciando visualización de solicitud', [
                'uuid' => $accreditationRequest->uuid,
                'status' => $accreditationRequest->status,
                'user_id' => auth()->id()
            ]);

            Gate::authorize('view', $accreditationRequest);
            
            Log::info('[SHOW] Autorización exitosa');

            $this->logAction('ver', 'solicitud de acreditación', $accreditationRequest->id);
            
            // Cargar relaciones necesarias para el timeline
            $accreditationRequest->load([
                'employee.provider', 
                'event', 
                'zones', 
                'credential',
                'creator',
                'reviewedBy',
                'approvedBy', 
                'rejectedBy',
                'returnedBy',
                'suspendedBy'
            ]);
            
            return $this->respondWithSuccess('accreditation-requests/show', [
                'request' => $accreditationRequest,
                'timeline' => $accreditationRequest->getTimeline(),
                'canDownload' => $accreditationRequest->credential && $accreditationRequest->credential->is_ready && auth()->user()->can('credential.download'),
                'canRegenerate' => $accreditationRequest->credential && auth()->user()->can('templates.regenerate'),
                'canViewCredential' => $accreditationRequest->status->value === 'approved' && auth()->user()->can('credential.view'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[SHOW] Error en visualización de solicitud', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Ver solicitud de acreditación');
        }
    }

    /**
     * Show the form for editing the specified accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Inertia\Response
     */
    public function edit(AccreditationRequest $accreditationRequest)
    {
        Log::info('[EDIT] Iniciando edición de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'status' => $accreditationRequest->status,
            'user_id' => auth()->id()
        ]);

        try {
            Gate::authorize('update', $accreditationRequest);
            Log::info('[EDIT] Autorización exitosa');
            
            // Verificar restricciones de estado según el rol del usuario
            $user = auth()->user();
            $isPrivilegedUser = $user->hasRole('admin') || $user->hasRole('security_manager');
            
            // Solo admin y security_manager pueden editar solicitudes en cualquier estado
            if (!$isPrivilegedUser && $accreditationRequest->status->value !== 'draft') {
                Log::warning('[EDIT] Intento de editar solicitud no borrador sin permisos privilegiados', [
                    'status' => $accreditationRequest->status->value,
                    'user_role' => $user->getRoleNames()->first(),
                    'is_privileged' => $isPrivilegedUser
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('warning', 'No se puede editar esta solicitud porque ya fue enviada para revisión. Solo se pueden editar solicitudes en estado borrador.');
            }
            
            Log::info('[EDIT] Validación de estado aprobada', [
                'status' => $accreditationRequest->status->value,
                'user_role' => $user->getRoleNames()->first(),
                'is_privileged' => $isPrivilegedUser
            ]);
            
            // Obtener los eventos activos para el selector
            $events = $this->eventService->getAllActive();
            
            // Obtener empleados del mismo proveedor (si es proveedor) o todos (si es admin)
            if ($user->hasRole('provider')) {
                $employees = $user->provider->employees()->active()->get();
            } else {
                $employees = \App\Models\Employee::with('provider')->active()->get();
            }
            
            // Obtener todas las zonas disponibles
            $zones = \App\Models\Zone::orderBy('name')->get();
            
            // Cargar las relaciones necesarias
            $accreditationRequest->load(['employee.provider', 'event', 'zones']);
            
            $this->logAction('editar', 'solicitud de acreditación', $accreditationRequest->id);
            
            Log::info('[EDIT] Datos preparados exitosamente');
            
            return $this->respondWithSuccess('accreditation-requests/edit', [
                'request' => $accreditationRequest,
                'events' => $events,
                'employees' => $employees,
                'zones' => $zones,
            ]);
        } catch (\Throwable $e) {
            Log::error('[EDIT] Error en edición', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Editar solicitud de acreditación');
        }
    }

    /**
     * Update the specified accreditation request.
     *
     * @param Request $request
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AccreditationRequest $accreditationRequest)
    {
        Log::info('[UPDATE] Iniciando actualización de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'status' => $accreditationRequest->status,
            'user_id' => auth()->id()
        ]);

        try {
            Gate::authorize('update', $accreditationRequest);
            Log::info('[UPDATE] Autorización exitosa');

            // Verificar restricciones de estado según el rol del usuario
            $user = auth()->user();
            $isPrivilegedUser = $user->hasRole('admin') || $user->hasRole('security_manager');
            
            // Solo admin y security_manager pueden actualizar solicitudes en cualquier estado
            if (!$isPrivilegedUser && $accreditationRequest->status !== AccreditationStatus::Draft) {
                Log::warning('[UPDATE] Intento de actualizar solicitud no borrador sin permisos privilegiados', [
                    'status' => $accreditationRequest->status->value,
                    'user_role' => $user->getRoleNames()->first(),
                    'is_privileged' => $isPrivilegedUser
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Solo se pueden actualizar solicitudes en estado borrador.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            Log::info('[UPDATE] Validación de estado aprobada', [
                'status' => $accreditationRequest->status->value,
                'user_role' => $user->getRoleNames()->first(),
                'is_privileged' => $isPrivilegedUser
            ]);

            // Validar los datos de entrada
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'event_id' => 'required|exists:events,id',
                'comments' => 'nullable|string|max:1000',
                'zones' => 'nullable|array',
                'zones.*' => 'exists:zones,id'
            ]);

            // Actualizar la solicitud usando el servicio
            $updatedRequest = $this->accreditationRequestService->updateRequest($accreditationRequest, $validated);
            
            $this->logAction('actualizar', 'solicitud de acreditación', $accreditationRequest->id);
            
            Log::info('[UPDATE] Solicitud actualizada exitosamente');
            
            return redirect()
                ->route('accreditation-requests.index')
                ->with('flash.banner', 'Solicitud actualizada correctamente')
                ->with('flash.bannerStyle', 'success');
                
        } catch (\Throwable $e) {
            Log::error('[UPDATE] Error en actualización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Actualizar solicitud de acreditación');
        }
    }

    /**
     * Remove the specified accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AccreditationRequest $accreditationRequest)
    {
        Log::info('[DELETE] Iniciando eliminación de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'status' => $accreditationRequest->status,
            'user_id' => auth()->id()
        ]);

        try {
            Gate::authorize('delete', $accreditationRequest);
            Log::info('[DELETE] Autorización exitosa');

            $this->accreditationRequestService->deleteRequest($accreditationRequest);
            Log::info('[DELETE] Solicitud eliminada exitosamente');

            $this->logAction('eliminar', 'solicitud de acreditación', $accreditationRequest->id);
            
            return $this->redirectWithSuccess(
                'accreditation-requests.index', 
                [], 
                'Solicitud eliminada correctamente'
            );
        } catch (\Throwable $e) {
            Log::error('[DELETE] Error en eliminación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Eliminar solicitud de acreditación');
        }
    }

    /**
     * Submit the accreditation request for approval.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(AccreditationRequest $accreditationRequest)
    {
        Log::info('[SUBMIT] Iniciando envío de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'status' => $accreditationRequest->status,
            'user_id' => auth()->id(),
            'employee_id' => $accreditationRequest->employee_id
        ]);

        try {
            Gate::authorize('update', $accreditationRequest);
            Log::info('[SUBMIT] Autorización exitosa');

            // Verificar que la solicitud esté en estado borrador
            if ($accreditationRequest->status !== AccreditationStatus::Draft) {
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Solo se pueden enviar solicitudes que están en estado borrador. Esta solicitud ya fue enviada anteriormente.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            $this->accreditationRequestService->submitRequest($accreditationRequest);
            Log::info('[SUBMIT] Servicio ejecutado exitosamente');

            $this->logAction('enviar', 'solicitud de acreditación', $accreditationRequest->id);
            
            return redirect()
                ->route('accreditation-requests.index')
                ->with('flash.banner', 'Solicitud enviada correctamente para su revisión')
                ->with('flash.bannerStyle', 'success');
                
        } catch (\Throwable $e) {
            Log::error('[SUBMIT] Error en envío', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Enviar solicitud de acreditación');
        }
    }

    /**
     * Approve the accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(AccreditationRequest $accreditationRequest)
    {
        Log::info('[APPROVE CONTROLLER] ==> Iniciando aprobación de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'id' => $accreditationRequest->id,
            'status' => $accreditationRequest->status->value,
            'employee' => $accreditationRequest->employee->first_name . ' ' . $accreditationRequest->employee->last_name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'timestamp' => now()->toISOString()
        ]);

        try {
            Log::info('[APPROVE CONTROLLER] Verificando autorización...');
            Gate::authorize('update', $accreditationRequest);
            Log::info('[APPROVE CONTROLLER] Autorización exitosa');

            Log::info('[APPROVE CONTROLLER] Verificando estado de la solicitud', [
                'current_status' => $accreditationRequest->status->value,
                'allowed_statuses' => ['submitted', 'under_review']
            ]);
            
            if (!in_array($accreditationRequest->status->value, ['submitted', 'under_review'])) {
                Log::warning('[APPROVE CONTROLLER] Estado no válido para aprobación', [
                    'current_status' => $accreditationRequest->status->value
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('warning', 'Solo se pueden aprobar solicitudes enviadas o en revisión.');
            }
            
            Log::info('[APPROVE CONTROLLER] Llamando al servicio approveRequest...');
            $this->accreditationRequestService->approveRequest($accreditationRequest);
            Log::info('[APPROVE CONTROLLER] Servicio approveRequest ejecutado exitosamente');

            Log::info('[APPROVE CONTROLLER] Registrando acción en log...');
            $this->logAction('aprobar', 'solicitud de acreditación', $accreditationRequest->id);
            Log::info('[APPROVE CONTROLLER] Acción registrada exitosamente');
            
            Log::info('[APPROVE CONTROLLER] Redirigiendo con mensaje de éxito');
            return $this->redirectWithSuccess(
                'accreditation-requests.index', 
                [], 
                'Solicitud aprobada correctamente'
            );
                
        } catch (\Throwable $e) {
            Log::error('[APPROVE CONTROLLER] Error durante la aprobación', [
                'uuid' => $accreditationRequest->uuid,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, 'Aprobar solicitud de acreditación');
        }
    }

    /**
     * Reject the accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(AccreditationRequest $accreditationRequest, Request $request)
    {
        try {
            Gate::authorize('update', $accreditationRequest);
            
            if (!in_array($accreditationRequest->status->value, ['submitted', 'under_review'])) {
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Solo se pueden rechazar solicitudes enviadas o en revisión.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            $this->accreditationRequestService->rejectRequest($accreditationRequest, $request->get('reason'));
            
            $this->logAction('rechazar', 'solicitud de acreditación', $accreditationRequest->id);
            
            return redirect()
                ->route('accreditation-requests.index')
                ->with('flash.banner', 'Solicitud rechazada correctamente')
                ->with('flash.bannerStyle', 'success');
                
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Rechazar solicitud de acreditación');
        }
    }

    /**
     * Return the accreditation request to draft status.
     *
     * @param AccreditationRequest $accreditationRequest
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnToDraft(AccreditationRequest $accreditationRequest, Request $request)
    {
        Log::info('[RETURN CONTROLLER] Iniciando devolución a borrador', [
            'uuid' => $accreditationRequest->uuid,
            'id' => $accreditationRequest->id,
            'status' => $accreditationRequest->status->value,
            'employee' => $accreditationRequest->employee->first_name . ' ' . $accreditationRequest->employee->last_name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            Log::info('[RETURN CONTROLLER] Verificando autorización...');
            Gate::authorize('update', $accreditationRequest);
            Log::info('[RETURN CONTROLLER] Autorización exitosa');
            
            Log::info('[RETURN CONTROLLER] Verificando estado de la solicitud', [
                'current_status' => $accreditationRequest->status->value,
                'required_statuses' => ['submitted', 'under_review']
            ]);
            
            if (!in_array($accreditationRequest->status->value, ['submitted', 'under_review'])) {
                Log::warning('[RETURN CONTROLLER] Estado no válido para devolución', [
                    'current_status' => $accreditationRequest->status->value
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Solo se pueden devolver a borrador solicitudes enviadas o en revisión.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            // Validar que exista una razón de devolución
            $reason = $request->get('reason');
            if (empty($reason)) {
                Log::warning('[RETURN CONTROLLER] Motivo de devolución requerido');
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Se requiere un motivo para devolver la solicitud.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            Log::info('[RETURN CONTROLLER] Llamando al servicio returnToDraft...');
            $this->accreditationRequestService->returnToDraft($accreditationRequest, $reason);
            Log::info('[RETURN CONTROLLER] Servicio returnToDraft ejecutado exitosamente');
            
            Log::info('[RETURN CONTROLLER] Registrando acción en log...');
            $this->logAction('devolver a borrador', 'solicitud de acreditación', $accreditationRequest->id);
            Log::info('[RETURN CONTROLLER] Acción registrada exitosamente');
            
            Log::info('[RETURN CONTROLLER] Redirigiendo con mensaje de éxito');
            return $this->redirectWithSuccess(
                'accreditation-requests.index', 
                [], 
                'Solicitud devuelta a borrador para corrección'
            );
                
        } catch (\Throwable $e) {
            Log::error('[RETURN CONTROLLER] Error durante la devolución', [
                'uuid' => $accreditationRequest->uuid,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, 'Devolver solicitud a borrador');
        }
    }

    /**
     * Suspender una credencial aprobada
     *
     * @param AccreditationRequest $accreditationRequest
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend(AccreditationRequest $accreditationRequest, Request $request)
    {
        Log::info('[SUSPEND CONTROLLER] Iniciando suspensión de credencial', [
            'uuid' => $accreditationRequest->uuid,
            'id' => $accreditationRequest->id,
            'status' => $accreditationRequest->status->value,
            'employee' => $accreditationRequest->employee->first_name . ' ' . $accreditationRequest->employee->last_name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'timestamp' => now()->toISOString()
        ]);

        try {
            Log::info('[SUSPEND CONTROLLER] Verificando autorización...');
            Gate::authorize('update', $accreditationRequest);
            Log::info('[SUSPEND CONTROLLER] Autorización exitosa');

            Log::info('[SUSPEND CONTROLLER] Verificando estado de la solicitud', [
                'current_status' => $accreditationRequest->status->value,
                'required_status' => 'approved'
            ]);
            
            if ($accreditationRequest->status->value !== 'approved') {
                Log::warning('[SUSPEND CONTROLLER] Estado no válido para suspensión', [
                    'current_status' => $accreditationRequest->status->value
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Solo se pueden suspender credenciales aprobadas.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            // Validar que exista una razón de suspensión
            $reason = $request->get('reason');
            if (empty($reason)) {
                Log::warning('[SUSPEND CONTROLLER] Motivo de suspensión requerido');
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('flash.banner', 'Se requiere un motivo para suspender la credencial.')
                    ->with('flash.bannerStyle', 'warning');
            }
            
            Log::info('[SUSPEND CONTROLLER] Llamando al servicio suspendRequest...');
            $this->accreditationRequestService->suspendRequest($accreditationRequest, $reason);
            Log::info('[SUSPEND CONTROLLER] Servicio suspendRequest ejecutado exitosamente');

            Log::info('[SUSPEND CONTROLLER] Registrando acción en log...');
            $this->logAction('suspender', 'credencial de acreditación', $accreditationRequest->id);
            Log::info('[SUSPEND CONTROLLER] Acción registrada exitosamente');
            
            Log::info('[SUSPEND CONTROLLER] Redirigiendo con mensaje de éxito');
            return $this->redirectWithSuccess(
                'accreditation-requests.index', 
                [], 
                'Credencial suspendida correctamente'
            );
                
        } catch (\Throwable $e) {
            Log::error('[SUSPEND CONTROLLER] Error durante la suspensión', [
                'uuid' => $accreditationRequest->uuid,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, 'Suspender credencial');
        }
    }

    /**
     * Give approval to the accreditation request (area manager).
     *
     * @param AccreditationRequest $accreditationRequest
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function review(AccreditationRequest $accreditationRequest, Request $request)
    {
        Log::info('[REVIEW CONTROLLER] ==> Iniciando visto bueno de solicitud', [
            'uuid' => $accreditationRequest->uuid,
            'id' => $accreditationRequest->id,
            'status' => $accreditationRequest->status->value,
            'employee' => $accreditationRequest->employee->first_name . ' ' . $accreditationRequest->employee->last_name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
            'comments' => $request->get('comments'),
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            Log::info('[REVIEW CONTROLLER] Verificando autorización...');
            Gate::authorize('update', $accreditationRequest);
            Log::info('[REVIEW CONTROLLER] Autorización exitosa');
            
            Log::info('[REVIEW CONTROLLER] Verificando estado de la solicitud', [
                'current_status' => $accreditationRequest->status->value,
                'required_status' => 'submitted'
            ]);
            
            if ($accreditationRequest->status->value !== 'submitted') {
                Log::warning('[REVIEW CONTROLLER] Estado no válido para visto bueno', [
                    'current_status' => $accreditationRequest->status->value
                ]);
                return redirect()
                    ->route('accreditation-requests.index')
                    ->with('warning', 'Solo se pueden dar visto bueno a solicitudes enviadas.');
            }
            
            Log::info('[REVIEW CONTROLLER] Llamando al servicio reviewRequest...');
            $this->accreditationRequestService->reviewRequest($accreditationRequest, $request->get('comments'));
            Log::info('[REVIEW CONTROLLER] Servicio reviewRequest ejecutado exitosamente');
            
            Log::info('[REVIEW CONTROLLER] Registrando acción en log...');
            $this->logAction('dar visto bueno', 'solicitud de acreditación', $accreditationRequest->id);
            Log::info('[REVIEW CONTROLLER] Acción registrada exitosamente');
            
            Log::info('[REVIEW CONTROLLER] Redirigiendo con mensaje de éxito');
            return $this->redirectWithSuccess(
                'accreditation-requests.index', 
                [], 
                'Visto bueno otorgado correctamente'
            );
                
        } catch (\Throwable $e) {
            Log::error('[REVIEW CONTROLLER] Error durante el visto bueno', [
                'uuid' => $accreditationRequest->uuid,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($e, 'Dar visto bueno a solicitud');
        }
    }


}
