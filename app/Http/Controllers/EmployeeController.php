<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\Employee\EmployeeServiceInterface;
use App\Services\Provider\ProviderServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EmployeeController extends BaseController
{
    protected $employeeService;
    protected $providerService;

    /**
     * Create a new controller instance.
     *
     * @param EmployeeServiceInterface $employeeService
     * @param ProviderServiceInterface $providerService
     */
    public function __construct(
        EmployeeServiceInterface $employeeService,
        ProviderServiceInterface $providerService
    ) {
        $this->employeeService = $employeeService;
        $this->providerService = $providerService;
    }

    /**
     * Display a listing of employees.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            Gate::authorize('viewAny', Employee::class);

            $employees = $this->employeeService->getPaginatedEmployees($request);
            $providers = $this->providerService->getAccessibleProviders();
            $stats = $this->employeeService->getEmployeeStats();
            
            // Determine user role and provider status
            $user = auth()->user();
            $isProvider = $user && $user->hasRole('provider');
            $currentUserRole = $user ? $user->roles->first()->name : null;
            
            $this->logAction('listar', 'empleados', null, [
                'filters' => $request->all()
            ]);
            
            return $this->respondWithSuccess('employees/index', [
                'employees' => $employees,
                'providers' => $providers,
                'stats' => $stats,
                'currentUserRole' => $currentUserRole,
                'isProvider' => $isProvider,
                'filters' => $request->only(['search', 'provider_id', 'active', 'sort', 'direction', 'per_page'])
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar empleados');
        }
    }

    /**
     * Show the form for creating a new employee.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        try {
            $user = auth()->user();
            $hasPermission = false;
            
            // Log de inicio del método y usuario
            \Log::info('EmployeeController@create - Inicio', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'roles' => $user->getRoleNames()->toArray()
            ]);
            
            // Verificar permisos por rol o por permisos específicos
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            \Log::info('EmployeeController@create - Permisos del usuario', [
                'permisos' => $userPermissions,
                'tiene_employee.manage' => $user->hasPermissionTo('employee.manage'),
                'tiene_employee.manage_own_provider' => $user->hasPermissionTo('employee.manage_own_provider'),
            ]);
            
            if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
                $hasPermission = true;
                \Log::info('EmployeeController@create - Permiso concedido: admin/security_manager');
            } elseif ($user->hasRole('area_manager')) {
                \Log::info('EmployeeController@create - Usuario es area_manager');
                if ($user->can('employee.manage_own_provider')) {
                    $hasPermission = true;
                    \Log::info('EmployeeController@create - Permiso concedido: area_manager con employee.manage_own_provider');
                } else {
                    \Log::error('EmployeeController@create - Permiso denegado: area_manager SIN employee.manage_own_provider');
                }
            } elseif ($user->hasRole('provider') && $user->can('employee.manage_own_provider')) {
                $hasPermission = true;
                \Log::info('EmployeeController@create - Permiso concedido: provider con employee.manage_own_provider');
            } elseif ($user->can('employee.manage')) {
                $hasPermission = true;
                \Log::info('EmployeeController@create - Permiso concedido: tiene employee.manage');
            } else {
                \Log::error('EmployeeController@create - No cumple ninguna condición de permisos');
            }
            
            if (!$hasPermission) {
                \Log::error('EmployeeController@create - Acceso denegado');
                abort(403, 'No tienes permisos para registrar empleados');
            }
            
            $providers = $this->providerService->getAccessibleProviders();
            
            return $this->respondWithSuccess('employees/create', [
                'providers' => $providers
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear empleado');
        }
    }

    /**
     * Store a newly created employee in storage.
     *
     * @param StoreEmployeeRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $user = auth()->user();
            $hasPermission = false;
            
            // Log de inicio del método store y datos del usuario
            \Log::info('EmployeeController@store - Inicio', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'provider_id_request' => $request->input('provider_id')
            ]);
            
            // Verificar permisos por rol o por permisos específicos
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            \Log::info('EmployeeController@store - Permisos del usuario', [
                'permisos' => $userPermissions,
                'tiene_employee.manage' => $user->hasPermissionTo('employee.manage'),
                'tiene_employee.manage_own_provider' => $user->hasPermissionTo('employee.manage_own_provider'),
            ]);
            
            if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
                $hasPermission = true;
                \Log::info('EmployeeController@store - Permiso concedido: admin/security_manager');
            } elseif ($user->hasRole('area_manager')) {
                \Log::info('EmployeeController@store - Usuario es area_manager');
                if ($user->can('employee.manage_own_provider')) {
                    $hasPermission = true;
                    \Log::info('EmployeeController@store - Tiene permiso employee.manage_own_provider');
                    
                    // Verificar que el proveedor pertenezca al área del area_manager
                    $providerId = $request->input('provider_id');
                    $accessibleProviderIds = $this->employeeService->getAccessibleProviderIds();
                    
                    \Log::info('EmployeeController@store - Verificando acceso a proveedor', [
                        'provider_id' => $providerId,
                        'accessible_provider_ids' => $accessibleProviderIds,
                        'has_access' => in_array($providerId, $accessibleProviderIds)
                    ]);
                    
                    if (!in_array($providerId, $accessibleProviderIds)) {
                        \Log::error('EmployeeController@store - Proveedor fuera del área del gerente');
                        abort(403, 'No puedes crear empleados para proveedores fuera de tu área');
                    }
                } else {
                    \Log::error('EmployeeController@store - area_manager sin permiso employee.manage_own_provider');
                }
            } elseif ($user->hasRole('provider') && $user->can('employee.manage_own_provider')) {
                $hasPermission = true;
                \Log::info('EmployeeController@store - Permiso concedido: provider con employee.manage_own_provider');
                
                // Verificar que el proveedor sea el mismo del usuario
                \Log::info('EmployeeController@store - Verificando proveedor del usuario', [
                    'user_provider_id' => $user->provider_id,
                    'request_provider_id' => $request->input('provider_id'),
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);
                
                // Si el usuario tiene provider_id asignado, verificar que sea el mismo
                if ($user->provider_id !== null && $user->provider_id != $request->input('provider_id')) {
                    \Log::error('EmployeeController@store - Intento de crear empleado para otro proveedor');
                    abort(403, 'Solo puedes crear empleados para tu propio proveedor');
                }
                
                // Si el usuario no tiene provider_id asignado, buscar su proveedor por usuario
                if ($user->provider_id === null) {
                    // Buscar el proveedor relacionado con este usuario
                    $provider = \App\Models\Provider::where('user_id', $user->id)->first();
                    \Log::info('EmployeeController@store - Buscando proveedor por user_id', [
                        'user_id' => $user->id,
                        'provider_encontrado' => $provider ? true : false,
                        'provider_id' => $provider ? $provider->id : null
                    ]);
                    
                    if ($provider && $provider->id != $request->input('provider_id')) {
                        \Log::error('EmployeeController@store - Intento de crear empleado para otro proveedor (por user_id)');
                        abort(403, 'Solo puedes crear empleados para tu propio proveedor');
                    }
                }
            } elseif ($user->can('employee.manage')) {
                $hasPermission = true;
                \Log::info('EmployeeController@store - Permiso concedido: tiene employee.manage');
            } else {
                \Log::error('EmployeeController@store - No cumple ninguna condición de permisos');
            }
            
            if (!$hasPermission) {
                \Log::error('EmployeeController@store - Acceso denegado');
                abort(403, 'No tienes permisos para registrar empleados');
            }
            
            $data = $request->validated();
            $employee = $this->employeeService->createEmployee($data);
            
            $this->logAction('crear', 'empleado', $employee->id);
            
            return $this->redirectWithSuccess(
                'employees.index', 
                [], 
                'Empleado creado correctamente'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear empleado');
        }
    }

    /**
     * Display the specified employee.
     *
     * @param Employee $employee
     * @return \Inertia\Response
     */
    public function show(Employee $employee)
    {
        try {
            Gate::authorize('view', $employee);
            
            $this->logAction('ver', 'empleado', $employee->id);
            
            return $this->respondWithSuccess('employees/show', [
                'employee' => $employee->load('provider')
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Ver empleado');
        }
    }

    /**
     * Show the form for editing the specified employee.
     *
     * @param Employee $employee
     * @return \Inertia\Response
     */
    public function edit(Employee $employee)
    {
        try {
            Gate::authorize('update', $employee);
            
            $providers = $this->providerService->getAccessibleProviders();
            
            return $this->respondWithSuccess('employees/edit', [
                'employee' => $employee->load('provider'),
                'providers' => $providers
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Editar empleado');
        }
    }

    /**
     * Update the specified employee in storage.
     *
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        try {
            Gate::authorize('update', $employee);
            
            $data = $request->validated();
            $employee = $this->employeeService->updateEmployee($employee, $data);
            
            $this->logAction('actualizar', 'empleado', $employee->id);
            
            return $this->redirectWithSuccess(
                'employees.index', 
                [], 
                'Empleado actualizado correctamente'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Actualizar empleado');
        }
    }

    /**
     * Toggle the active status of the employee.
     *
     * @param Employee $employee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(Employee $employee)
    {
        try {
            Gate::authorize('toggleActive', $employee);
            
            $employee = $this->employeeService->toggleActive($employee);
            
            $status = $employee->active ? 'activado' : 'desactivado';
            $this->logAction($status, 'empleado', $employee->id);
            
            return $this->redirectWithSuccess(
                'employees.index',
                [],
                "Empleado {$status} correctamente"
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Cambiar estado de empleado');
        }
    }
}
