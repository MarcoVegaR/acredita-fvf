<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccreditationRequest\StoreAccreditationRequestRequest;
use App\Http\Requests\AccreditationRequest\UpdateAccreditationRequestRequest;
use App\Models\AccreditationRequest;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use App\Services\Employee\EmployeeServiceInterface;
use App\Services\Event\EventServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccreditationRequestDraftController extends BaseController
{
    protected $accreditationRequestService;
    protected $eventService;
    protected $employeeService;

    /**
     * Create a new controller instance.
     *
     * @param AccreditationRequestServiceInterface $accreditationRequestService
     * @param EventServiceInterface $eventService
     * @param EmployeeServiceInterface $employeeService
     */
    public function __construct(
        AccreditationRequestServiceInterface $accreditationRequestService,
        EventServiceInterface $eventService,
        EmployeeServiceInterface $employeeService
    ) {
        $this->accreditationRequestService = $accreditationRequestService;
        $this->eventService = $eventService;
        $this->employeeService = $employeeService;
    }

    /**
     * Wizard paso 1: Selección de evento.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function wizardStep1(Request $request)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            $events = $this->eventService->getAllActive();
            $selectedEventId = $request->session()->get('wizard.event_id');
            
            return $this->respondWithSuccess('accreditation-requests/create/step-1', [
                'events' => $events,
                'selectedEventId' => $selectedEventId
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Wizard de solicitud - Selección de evento');
        }
    }

    /**
     * Wizard paso 2: Selección de empleado.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function wizardStep2(Request $request)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            // Verificar que se haya seleccionado un evento
            if (!$request->session()->has('wizard.event_id')) {
                return redirect()->route('accreditation-requests.create')
                    ->withErrors(['event' => 'Debe seleccionar un evento primero']);
            }
            
            // Obtener empleados según permisos
            $employees = $this->employeeService->getAccessibleEmployees($request);
            $selectedEmployeeId = $request->session()->get('wizard.employee_id');
            
            return $this->respondWithSuccess('accreditation-requests/create/step-2', [
                'employees' => $employees,
                'selectedEmployeeId' => $selectedEmployeeId
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Wizard de solicitud - Selección de empleado');
        }
    }

    /**
     * Wizard paso 3: Selección de zonas.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function wizardStep3(Request $request)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            // Verificar que se haya seleccionado un evento
            if (!$request->session()->has('wizard.event_id')) {
                return redirect()->route('accreditation-requests.create')
                    ->withErrors(['event' => 'Debe seleccionar un evento primero']);
            }
            
            $eventId = $request->session()->get('wizard.event_id');
            $zones = $this->accreditationRequestService->getZonesForEvent($eventId);
            $selectedZones = $request->session()->get('wizard.zones', []);
            
            return $this->respondWithSuccess('accreditation-requests/create/step-3', [
                'zones' => $zones,
                'selectedZones' => $selectedZones
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Wizard de solicitud - Selección de zonas');
        }
    }

    /**
     * Wizard paso 4: Revisión y confirmación.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function wizardStep4(Request $request)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            // Verificar que se tengan todos los datos necesarios
            if (!$request->session()->has(['wizard.event_id', 'wizard.employee_id', 'wizard.zones'])) {
                return redirect()->route('accreditation-requests.create')
                    ->withErrors(['error' => 'Debe completar todos los pasos previos']);
            }
            
            $eventId = $request->session()->get('wizard.event_id');
            $employeeId = $request->session()->get('wizard.employee_id');
            $selectedZones = $request->session()->get('wizard.zones', []);
            
            $event = $this->eventService->getEventById($eventId);
            $employee = $this->employeeService->findById($employeeId);
            $zones = $this->accreditationRequestService->getZonesForEvent($eventId)
                ->whereIn('id', $selectedZones);
            
            return $this->respondWithSuccess('accreditation-requests/create/step-4', [
                'event' => $event,
                'employee' => $employee,
                'zones' => $zones,
                'comments' => $request->session()->get('wizard.comments')
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Wizard de solicitud - Revisión');
        }
    }

    /**
     * Store a newly created accreditation request in storage.
     *
     * @param StoreAccreditationRequestRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAccreditationRequestRequest $request)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            $data = $request->validated();
            $accreditationRequest = $this->accreditationRequestService->createRequest($data);
            
            $this->logAction('crear', 'solicitud de acreditación', $accreditationRequest->id);
            
            // Limpiar datos del wizard
            $request->session()->forget('wizard');
            
            return $this->redirectWithSuccess(
                'accreditation-requests.show', 
                ['accreditation_request' => $accreditationRequest->uuid], 
                'Solicitud creada correctamente'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear solicitud de acreditación');
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
        try {
            Gate::authorize('update', $accreditationRequest);
            
            $zones = $this->accreditationRequestService->getZonesForEvent($accreditationRequest->event_id);
            $selectedZones = $accreditationRequest->zones->pluck('id')->toArray();
            
            return $this->respondWithSuccess('accreditation-requests/edit', [
                'request' => $accreditationRequest->load(['employee.provider', 'event']),
                'zones' => $zones,
                'selectedZones' => $selectedZones
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Editar solicitud de acreditación');
        }
    }

    /**
     * Update the specified accreditation request in storage.
     *
     * @param UpdateAccreditationRequestRequest $request
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAccreditationRequestRequest $request, AccreditationRequest $accreditationRequest)
    {
        try {
            Gate::authorize('update', $accreditationRequest);
            
            $data = $request->validated();
            $accreditationRequest = $this->accreditationRequestService->updateRequest($accreditationRequest, $data);
            
            $this->logAction('actualizar', 'solicitud de acreditación', $accreditationRequest->id);
            
            return $this->redirectWithSuccess(
                'accreditation-requests.show', 
                ['accreditation_request' => $accreditationRequest->uuid], 
                'Solicitud actualizada correctamente'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Actualizar solicitud de acreditación');
        }
    }

    /**
     * Submit the specified accreditation request.
     *
     * @param AccreditationRequest $accreditationRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(AccreditationRequest $accreditationRequest)
    {
        try {
            Gate::authorize('submit', $accreditationRequest);
            
            $accreditationRequest = $this->accreditationRequestService->submitRequest($accreditationRequest);
            
            $this->logAction('enviar', 'solicitud de acreditación', $accreditationRequest->id);
            
            return $this->redirectWithSuccess(
                'accreditation-requests.show', 
                ['accreditation_request' => $accreditationRequest->uuid], 
                'Solicitud enviada correctamente'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Enviar solicitud de acreditación');
        }
    }

    /**
     * Store step data in session and redirect to next step.
     *
     * @param Request $request
     * @param int $currentStep
     * @param int $nextStep
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStep(Request $request, int $currentStep, int $nextStep)
    {
        try {
            Gate::authorize('create', AccreditationRequest::class);
            
            // Validar datos según el paso actual
            switch ($currentStep) {
                case 1:
                    $request->validate(['event_id' => 'required|exists:events,id']);
                    $request->session()->put('wizard.event_id', $request->event_id);
                    break;
                case 2:
                    $request->validate(['employee_id' => 'required|exists:employees,id']);
                    $request->session()->put('wizard.employee_id', $request->employee_id);
                    break;
                case 3:
                    $request->validate(['zones' => 'required|array', 'zones.*' => 'exists:zones,id']);
                    $request->session()->put('wizard.zones', $request->zones);
                    break;
                case 4:
                    $request->validate(['confirm' => 'required|boolean', 'notes' => 'nullable|string']);
                    $request->session()->put('wizard.notes', $request->notes);
                    
                    // Si es el último paso (4) y la confirmación es positiva, crear la solicitud
                    if ($request->confirm) {
                        // Obtener datos de la sesión
                        $eventId = $request->session()->get('wizard.event_id');
                        $employeeId = $request->session()->get('wizard.employee_id');
                        $selectedZones = $request->session()->get('wizard.zones', []);
                        $notes = $request->session()->get('wizard.notes');
                        
                        // Crear la solicitud
                        $this->accreditationRequestService->createRequest([
                            'event_id' => $eventId,
                            'employee_id' => $employeeId,
                            'zones' => $selectedZones,
                            'notes' => $notes
                        ]);
                        
                        // Limpiar la sesión del wizard
                        $request->session()->forget(['wizard.event_id', 'wizard.employee_id', 'wizard.zones', 'wizard.notes']);
                        
                        // Redireccionar a la lista de solicitudes con mensaje de éxito
                        return redirect()->route('accreditation-requests.index')
                            ->with('success', 'Solicitud de acreditación creada correctamente.');
                    }
                    break;
            }
            
            // Redireccionar al siguiente paso si no es el último o si no se confirma
            return redirect()->route("accreditation-requests.wizard.step{$nextStep}");
        } catch (\Throwable $e) {
            return $this->handleException($e, "Wizard de solicitud - Paso {$currentStep}");
        }
    }
}
