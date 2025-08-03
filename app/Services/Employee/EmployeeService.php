<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use App\Repositories\Provider\ProviderRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeService implements EmployeeServiceInterface
{
    protected $employeeRepository;
    protected $providerRepository;

    /**
     * Create a new service instance.
     *
     * @param EmployeeRepositoryInterface $employeeRepository
     * @param ProviderRepositoryInterface $providerRepository
     */
    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        ProviderRepositoryInterface $providerRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->providerRepository = $providerRepository;
    }
    
    /**
     * Get statistics about employees (total, active, inactive)
     *
     * @return array
     */
    public function getEmployeeStats(): array
    {
        $user = auth()->user();
        $query = $this->employeeRepository->query();
        
        // If user is provider, only show their employees
        if ($user && $user->hasRole('provider')) {
            $provider = $user->provider;
            if ($provider) {
                $query->where('provider_id', $provider->id);
            }
        }
        // If user is area_manager, only show employees from providers in their areas
        else if ($user && $user->hasRole('area_manager')) {
            $managedAreas = $user->managedAreas()->pluck('id')->toArray();
            $providerIds = $this->providerRepository->findByAreaIds($managedAreas)->pluck('id')->toArray();
            $query->whereIn('provider_id', $providerIds);
        }
        
        $total = $query->count();
        $active = (clone $query)->where('active', true)->count();
        $inactive = $total - $active;
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }

    /**
     * Get paginated employees based on user's access level and request parameters.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedEmployees(Request $request): LengthAwarePaginator
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');
        $providerId = $request->input('provider_id');
        
        $query = $this->employeeRepository->query()->with('provider');
        
        // Apply provider filter if specified
        if ($providerId) {
            $query->where('provider_id', $providerId);
        } else {
            // Apply access restrictions based on user role
            $providerIds = $this->getAccessibleProviderIds();
            if (!empty($providerIds)) {
                $query->whereIn('provider_id', $providerIds);
            }
        }
        
        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('document_number', 'LIKE', "%{$search}%")
                  ->orWhere('function', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply active filter if specified
        if ($request->has('active')) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $active);
        }
        
        // Apply sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'first_name', 'last_name', 'document_number', 'function', 'active', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        
        // Include provider relationship for displaying provider details
        return $query->with('provider')->paginate($perPage);
    }

    /**
     * Create a new employee.
     *
     * @param array $data
     * @return Employee
     */
    public function createEmployee(array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            // Primero, creamos el empleado con los datos básicos
            $employee = $this->employeeRepository->create([
                'uuid' => Str::uuid(),
                'provider_id' => $data['provider_id'],
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'function' => $data['function'],
                'active' => $data['active'] ?? true,
            ]);
            
            // Process employee photo if provided
            if (isset($data['photo']) && $data['photo']) {
                $photoPath = $this->processEmployeePhoto($data['photo'], $employee);
                if ($photoPath) {
                    // Actualizamos el modelo con la ruta de la foto
                    $employee->photo_path = $photoPath;
                    $employee->save();
                }
            }
            
            DB::commit();
            return $employee->fresh(); // Devolvemos el empleado recién cargado para asegurar que tenga todos los datos
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing employee.
     *
     * @param Employee $employee
     * @param array $data
     * @return Employee
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        DB::beginTransaction();
        
        try {
            $employee->provider_id = $data['provider_id'] ?? $employee->provider_id;
            $employee->document_type = $data['document_type'] ?? $employee->document_type;
            $employee->document_number = $data['document_number'] ?? $employee->document_number;
            $employee->first_name = $data['first_name'] ?? $employee->first_name;
            $employee->last_name = $data['last_name'] ?? $employee->last_name;
            $employee->function = $data['function'] ?? $employee->function;
            
            if (isset($data['active'])) {
                $employee->active = $data['active'];
            }
            
            // Process employee photo if provided
            if (isset($data['photo'])) {
                $photoPath = $this->processEmployeePhoto($data['photo'], $employee);
                if ($photoPath) {
                    // Delete old photo if exists
                    if ($employee->photo_path) {
                        Storage::disk('public')->delete($employee->photo_path);
                    }
                    $employee->photo_path = $photoPath;
                }
            }
            
            $employee->save();
            
            DB::commit();
            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Toggle employee active status.
     *
     * @param Employee $employee
     * @return Employee
     */
    public function toggleActive(Employee $employee): Employee
    {
        $employee->active = !$employee->active;
        $employee->save();
        
        return $employee;
    }
    
    /**
     * Find employee by UUID.
     *
     * @param string $uuid
     * @return Employee|null
     */
    public function findByUuid(string $uuid): ?Employee
    {
        return $this->employeeRepository->findByUuid($uuid);
    }
    
    /**
     * Get employees accessible to the current user based on permissions.
     *
     * @param Request $request
     * @return array
     */
    public function getAccessibleEmployees(Request $request): array
    {
        $query = Employee::query();
        $user = Auth::user();

        // Filtrar según permisos y rol (orden de prioridad)
        if ($user->hasRole('admin') || $user->hasPermissionTo('employee.manage')) {
            // Admin o con permiso employee.manage puede ver todos los empleados
            // No aplicar filtros adicionales
        } elseif ($user->hasRole('area_manager')) {
            // Gerentes de área solo ven empleados de proveedores en su área gestionada
            if ($user->managedArea) {
                $userAreaId = $user->managedArea->id;
                $query->whereHas('provider', function ($q) use ($userAreaId) {
                    $q->where('area_id', $userAreaId);
                });
            } else {
                // Si no tiene área asignada, no debe ver empleados
                return [];
            }
        } elseif ($user->hasRole('provider')) {
            // Usuarios con rol 'provider' solo ven sus propios empleados
            $provider = $user->provider;
            if ($provider) {
                $query->where('provider_id', $provider->id);
            } else {
                // Si el usuario es proveedor pero no tiene proveedor asignado, no debe ver empleados
                return [];
            }
        } elseif ($user->hasPermissionTo('employee.view')) {
            // Usuarios con permiso employee.view solo ven empleados de proveedores públicos
            $query->whereHas('provider', function ($q) {
                $q->where('is_public', true);
            });
        } else {
            // Sin permisos, no retorna empleados
            return [];
        }
        
        // Filtramos empleados activos solamente para la selección
        $query->where('active', true);
        
        // Ordenamos por nombre
        $query->orderBy('first_name');
        
        // Obtener los empleados con sus relaciones necesarias
        $employees = $query->get();
        
        // Transformar los empleados para incluir atributos adicionales
        return $employees->map(function ($employee) {
            $data = $employee->toArray();
            $data['name'] = $employee->getFullNameAttribute(); // Añadir el nombre completo
            return $data;
        })->toArray();
    }
    
    /**
     * Process and store employee photo.
     *
     * @param mixed $photo
     * @param Employee $employee
     * @return string|null Photo path if successful, null otherwise
     */
    public function processEmployeePhoto($photo, Employee $employee): ?string
    {
        \Log::info('[EMPLOYEE PHOTO] Starting photo processing', [
            'employee_id' => $employee->id,
            'photo_type' => gettype($photo),
            'photo_length' => is_string($photo) ? strlen($photo) : 'not_string'
        ]);
        
        if (!$photo) {
            \Log::info('[EMPLOYEE PHOTO] No photo provided');
            return null;
        }
        
        $path = "employees/{$employee->provider_id}/{$employee->uuid}";
        \Log::info('[EMPLOYEE PHOTO] Storage path: ' . $path);
        
        // Si es un string base64, convertir a archivo
        if (is_string($photo) && strpos($photo, 'data:image') === 0) {
            \Log::info('[EMPLOYEE PHOTO] Processing base64 image');
            
            // Es una imagen base64
            $image_parts = explode(";", $photo);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = explode(",", $image_parts[1])[1];
            $imageData = base64_decode($image_base64);
            
            \Log::info('[EMPLOYEE PHOTO] Image details', [
                'image_type' => $image_type,
                'image_data_size' => strlen($imageData)
            ]);
            
            $fileName = uniqid() . '.' . $image_type;
            $fullPath = $path . '/' . $fileName;
            
            \Log::info('[EMPLOYEE PHOTO] Attempting to save to: ' . $fullPath);
            
            // Guardar la imagen en el disco
            if (Storage::disk('public')->put($fullPath, $imageData)) {
                \Log::info('[EMPLOYEE PHOTO] Image saved successfully: ' . $fullPath);
                return $fullPath;
            } else {
                \Log::error('[EMPLOYEE PHOTO] Failed to save image to storage');
            }
            
            return null;
        }
        
        \Log::info('[EMPLOYEE PHOTO] Processing uploaded file');
        // Si es un archivo cargado (UploadedFile)
        $result = $photo->store($path, 'public');
        \Log::info('[EMPLOYEE PHOTO] File stored: ' . ($result ?: 'FAILED'));
        return $result;
    }
    
    /**
     * Get the list of accessible provider IDs for the current user.
     *
     * @return array
     */
    public function getAccessibleProviderIds(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        // Admin can access all providers
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
            return $this->providerRepository->all()->pluck('id')->toArray();
        }
        
        // Area manager can access providers in their areas
        if ($user->hasRole('area_manager')) {
            // Get the areas managed by this user
            $managedAreas = $user->managedAreas()->pluck('id')->toArray();
            
            // Get providers from these areas
            return $this->providerRepository->findByAreaIds($managedAreas)
                ->pluck('id')
                ->toArray();
        }
        
        // Provider users can only access their own provider
        if ($user->hasRole('provider')) {
            $provider = $user->provider;
            return $provider ? [$provider->id] : [];
        }
        
        return [];
    }
    
    /**
     * Find employee by ID.
     *
     * @param int $id
     * @return Employee
     * @throws \Exception if employee not found
     */
    public function findById(int $id): Employee
    {
        $employee = $this->employeeRepository->find($id);
        
        if (!$employee) {
            throw new \Exception("Employee not found");
        }
        
        return $employee;
    }
    
    /**
     * Get employees available for bulk accreditation requests (without active requests for the event).
     *
     * @param int $eventId
     * @return Collection
     */
    public function getEmployeesForBulkRequest(int $eventId): Collection
    {
        $user = Auth::user();
        
        $query = $this->employeeRepository->query()
            ->with(['provider', 'accreditationRequests' => function ($query) use ($eventId) {
                $query->where('event_id', $eventId)
                      ->whereIn('status', ['draft', 'submitted', 'under_review', 'approved']);
            }]);
        
        // Filtrar según permisos y rol (orden de prioridad)
        if ($user->hasRole('admin') || $user->hasPermissionTo('employee.manage')) {
            // Admin o con permiso employee.manage puede ver todos los empleados
            // No aplicar filtros adicionales
        } elseif ($user->hasRole('area_manager')) {
            // Gerentes de área solo ven empleados de proveedores en su área gestionada
            $accessibleProviderIds = $this->getAccessibleProviderIds();
            if (!empty($accessibleProviderIds)) {
                $query->whereIn('provider_id', $accessibleProviderIds);
            } else {
                // Si no tiene proveedores accesibles, devolver colección vacía
                return collect([]);
            }
        } elseif ($user->hasRole('provider')) {
            // Usuarios con rol 'provider' solo ven sus propios empleados
            $provider = $user->provider;
            if ($provider) {
                $query->where('provider_id', $provider->id);
            } else {
                // Si el usuario es proveedor pero no tiene proveedor asignado, devolver colección vacía
                return collect([]);
            }
        } else {
            // Usuarios sin permisos especiales
            return collect([]);
        }
        
        $employees = $query->get();
        
        // Filtrar empleados que NO tienen solicitudes activas para este evento
        return $employees->filter(function ($employee) {
            return $employee->accreditationRequests->isEmpty();
        })->values();
    }
    
    /**
     * Get multiple employees by their IDs.
     *
     * @param array $employeeIds
     * @return Collection
     */
    public function getEmployeesByIds(array $employeeIds): Collection
    {
        if (empty($employeeIds)) {
            return Employee::query()->whereRaw('1 = 0')->get();
        }
        
        $user = Auth::user();
        
        $query = $this->employeeRepository->query()
            ->with('provider')
            ->whereIn('id', $employeeIds);
        
        // Aplicar filtros de seguridad según el rol (mismo patrón que en otros métodos)
        if ($user->hasRole('admin') || $user->hasPermissionTo('employee.manage')) {
            // Admin puede ver todos los empleados seleccionados
        } elseif ($user->hasRole('area_manager')) {
            // Area manager solo puede ver empleados de proveedores de su área
            if ($user->managedArea) {
                $userAreaId = $user->managedArea->id;
                $query->whereHas('provider', function ($q) use ($userAreaId) {
                    $q->where('area_id', $userAreaId);
                });
            } else {
                return Employee::query()->whereRaw('1 = 0')->get();
            }
        } elseif ($user->hasRole('provider')) {
            // Provider: verificar que los empleados sean de su proveedor
            // Cargar el proveedor asociado al usuario mediante la relación
            $userProvider = $user->provider;
            
            if ($userProvider) {
                $query->where('provider_id', $userProvider->id);
            } else {
                // Sin proveedor asociado, retornar colección vacía
                return Employee::query()->whereRaw('1 = 0')->get();
            }
        } else {
            // Otros usuarios no tienen acceso a empleados
            return Employee::query()->whereRaw('1 = 0')->get();
        }
        
        return $query->get();
    }
}
