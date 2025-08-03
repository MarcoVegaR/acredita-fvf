<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccreditationRequest\BulkStoreAccreditationRequestRequest;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use App\Services\Employee\EmployeeServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AccreditationRequestBulkController extends BaseController
{
    protected $accreditationService;
    protected $employeeService;

    public function __construct(
        AccreditationRequestServiceInterface $accreditationService,
        EmployeeServiceInterface $employeeService
    ) {
        $this->accreditationService = $accreditationService;
        $this->employeeService = $employeeService;
    }

    /**
     * Mostrar paso 1: Selección de evento
     */
    public function step1()
    {
        $this->authorize('accreditation_request.create');

        $events = $this->accreditationService->getActiveEvents();

        return Inertia::render('accreditation-requests/bulk/step-1', [
            'events' => $events
        ]);
    }

    /**
     * Mostrar paso 2: Selección de empleados (GET)
     */
    public function showStep2(Request $request)
    {
        $this->authorize('accreditation_request.create');

        // Obtener event_id de la sesión o del request
        $eventId = $request->get('event_id') ?? session('bulk_wizard.event_id');
        
        if (!$eventId) {
            return redirect()->route('accreditation-requests.bulk.step-1')
                ->with('error', 'Debe seleccionar un evento primero.');
        }

        $eventId = (int) $eventId;
        $event = $this->accreditationService->getActiveEvents()->find($eventId);

        if (!$event) {
            return redirect()->route('accreditation-requests.bulk.step-1')
                ->with('error', 'El evento seleccionado no existe.');
        }

        // Guardar en sesión
        session(['bulk_wizard.event_id' => $eventId]);

        // Obtener empleados disponibles
        $employees = $this->employeeService->getEmployeesForBulkRequest($eventId);

        return Inertia::render('accreditation-requests/bulk/step-2', [
            'event' => $event,
            'employees' => $employees
        ]);
    }

    /**
     * Procesar paso 1 y mostrar paso 2: Selección de empleados
     */
    public function step2(Request $request)
    {
        $this->authorize('accreditation_request.create');

        $request->validate([
            'event_id' => 'required|integer|exists:events,id'
        ]);

        $eventId = (int) $request->event_id;
        $event = $this->accreditationService->getActiveEvents()->find($eventId);

        if (!$event) {
            return back()->with('error', 'El evento seleccionado no existe.');
        }

        // Obtener empleados disponibles (sin solicitudes activas para este evento)
        $employees = $this->employeeService->getEmployeesForBulkRequest($eventId);

        return Inertia::render('accreditation-requests/bulk/step-2', [
            'event' => $event,
            'employees' => $employees
        ]);
    }

    /**
     * Mostrar paso 3: Configuración de zonas (GET)
     */
    public function showStep3(Request $request)
    {
        $this->authorize('accreditation_request.create');

        // Obtener datos de la sesión
        $eventId = session('bulk_wizard.event_id');
        $employeeIds = session('bulk_wizard.employee_ids');
        
        if (!$eventId || !$employeeIds) {
            return redirect()->route('accreditation-requests.bulk.step-1')
                ->with('error', 'Debe completar los pasos anteriores primero.');
        }

        $eventId = (int) $eventId;
        $employeeIds = array_map('intval', $employeeIds);

        $event = $this->accreditationService->getActiveEvents()->find($eventId);
        $zones = $this->accreditationService->getZonesForEvent($eventId);
        $selectedEmployees = $this->employeeService->getEmployeesByIds($employeeIds);

        return Inertia::render('accreditation-requests/bulk/step-3', [
            'event' => $event,
            'selectedEmployees' => $selectedEmployees,
            'zones' => $zones,
            'templates' => []
        ]);
    }

    /**
     * Procesar paso 2 y mostrar paso 3: Configuración de zonas
     */
    public function step3(Request $request)
    {
        $this->authorize('accreditation_request.create');
        
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $eventId = $request->input('event_id');
        $employeeIds = $request->input('employee_ids');

        // Guardar selecciones en la sesión
        session([
            'bulk_wizard.event_id' => $eventId,
            'bulk_wizard.employee_ids' => $employeeIds
        ]);

        $event = $this->accreditationService->getActiveEvents()->find($eventId);
        $zones = $this->accreditationService->getZonesForEvent($eventId);
        $selectedEmployees = $this->employeeService->getEmployeesByIds($employeeIds);
        
        return Inertia::render('accreditation-requests/bulk/step-3', [
            'event' => $event,
            'selectedEmployees' => $selectedEmployees,
            'zones' => $zones,
            'templates' => [] // TODO: Implementar templates de zonas si es necesario
        ]);
    }

    /**
     * Mostrar paso 4: Confirmación (GET)
     */
    public function showStep4(Request $request)
    {
        $this->authorize('accreditation_request.create');

        // Obtener datos de la sesión
        $eventId = session('bulk_wizard.event_id');
        $employeeZones = session('bulk_wizard.employee_zones');
        
        if (!$eventId || !$employeeZones) {
            return redirect()->route('accreditation-requests.bulk.step-1')
                ->with('error', 'Debe completar los pasos anteriores primero.');
        }

        $eventId = (int) $eventId;
        $event = $this->accreditationService->getActiveEvents()->find($eventId);
        $zones = $this->accreditationService->getZonesForEvent($eventId);
        $employeeIds = array_keys($employeeZones);
        $employees = $this->employeeService->getEmployeesByIds($employeeIds);

        // Combinar empleados con sus zonas asignadas
        $employeesWithZones = $employees->map(function ($employee) use ($employeeZones, $zones) {
            $assignedZoneIds = $employeeZones[$employee->id] ?? [];
            $assignedZones = $zones->whereIn('id', $assignedZoneIds);
            
            return array_merge($employee->toArray(), [
                'zones' => $assignedZoneIds, // Usar 'zones' en lugar de 'assigned_zones'
                'assigned_zones' => $assignedZones->values()->toArray()
            ]);
        });

        return Inertia::render('accreditation-requests/bulk/step-4', [
            'event' => $event,
            'employeesWithZones' => $employeesWithZones,
            'zones' => $zones
        ]);
    }

    /**
     * Procesar paso 3 y mostrar paso 4: Confirmación
     */
    public function step4(Request $request)
    {
        $this->authorize('accreditation_request.create');

        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'employee_zones' => 'required|array|min:1',
            'employee_zones.*' => 'required|array|min:1',
            'employee_zones.*.*' => 'integer|exists:zones,id'
        ]);

        $eventId = (int) $request->event_id;
        $employeeZones = [];

        // Convertir employee_zones a integers
        foreach ($request->employee_zones as $employeeId => $zones) {
            $employeeZones[(int) $employeeId] = array_map('intval', $zones);
        }

        // Guardar en sesión
        session([
            'bulk_wizard.event_id' => $eventId,
            'bulk_wizard.employee_zones' => $employeeZones
        ]);

        $event = $this->accreditationService->getActiveEvents()->find($eventId);
        $zones = $this->accreditationService->getZonesForEvent($eventId);
        $employeeIds = array_keys($employeeZones);
        $employees = $this->employeeService->getEmployeesByIds($employeeIds);

        // Combinar empleados con sus zonas asignadas
        $employeesWithZones = $employees->map(function ($employee) use ($employeeZones, $zones) {
            $assignedZoneIds = $employeeZones[$employee->id] ?? [];
            $assignedZones = $zones->whereIn('id', $assignedZoneIds);
            
            return array_merge($employee->toArray(), [
                'zones' => $assignedZoneIds, // Usar 'zones' en lugar de 'assigned_zones'
                'assigned_zones' => $assignedZones->values()->toArray()
            ]);
        });

        return Inertia::render('accreditation-requests/bulk/step-4', [
            'event' => $event,
            'employeesWithZones' => $employeesWithZones,
            'zones' => $zones
        ]);
    }

    /**
     * Crear las solicitudes masivas
     */
    public function store(BulkStoreAccreditationRequestRequest $request)
    {
        Log::info('AccreditationRequestBulkController::store - Iniciando creación masiva', [
            'user_id' => auth()->id(),
            'data' => $request->validated()
        ]);

        try {
            $results = $this->accreditationService->createBulkRequests($request->validated());

            $createdCount = count($results['created']);
            $skippedCount = count($results['skipped']);
            $errorCount = count($results['errors']);

            // Preparar mensaje de resultado
            $messages = [];
            
            if ($createdCount > 0) {
                $messages[] = "✅ {$createdCount} solicitudes creadas exitosamente";
            }
            
            if ($skippedCount > 0) {
                $messages[] = "⚠️ {$skippedCount} empleados omitidos (ya tenían solicitudes activas)";
            }
            
            if ($errorCount > 0) {
                $messages[] = "❌ {$errorCount} errores durante la creación";
            }

            $message = implode('. ', $messages);
            $flashType = $errorCount > 0 ? 'warning' : 'success';

            Log::info('AccreditationRequestBulkController::store - Proceso completado', [
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount
            ]);

            return $this->redirectWithSuccess(
                'accreditation-requests.index',
                [],
                $message
            )->with($flashType, $message);

        } catch (\Exception $e) {
            Log::error('AccreditationRequestBulkController::store - Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Error al crear las solicitudes masivas');
        }
    }
}
