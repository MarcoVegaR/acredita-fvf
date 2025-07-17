<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\Provider;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use App\Repositories\Provider\ProviderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            if (isset($data['photo'])) {
                $photoPath = $this->processEmployeePhoto($data['photo'], $employee);
                if ($photoPath) {
                    $employee->photo_path = $photoPath;
                    $employee->save();
                }
            }
            
            DB::commit();
            return $employee;
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
     * Process and store employee photo.
     *
     * @param mixed $photo
     * @param Employee $employee
     * @return string|null Photo path if successful, null otherwise
     */
    public function processEmployeePhoto($photo, Employee $employee): ?string
    {
        if (!$photo) {
            return null;
        }
        
        $path = "employees/{$employee->provider_id}/{$employee->uuid}";
        
        // Si es un string base64, convertir a archivo
        if (is_string($photo) && strpos($photo, 'data:image') === 0) {
            // Es una imagen base64
            $image_parts = explode(";", $photo);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = explode(",", $image_parts[1])[1];
            $imageData = base64_decode($image_base64);
            
            $fileName = uniqid() . '.' . $image_type;
            $fullPath = $path . '/' . $fileName;
            
            // Guardar la imagen en el disco
            if (Storage::disk('public')->put($fullPath, $imageData)) {
                return $fullPath;
            }
            
            return null;
        }
        
        // Si es un archivo cargado (UploadedFile)
        return $photo->store($path, 'public');
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
        if ($user->hasRole('admin')) {
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
}
