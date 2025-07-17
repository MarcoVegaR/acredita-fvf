<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface EmployeeServiceInterface
{
    /**
     * Get paginated employees based on user's access level and request parameters.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedEmployees(Request $request): LengthAwarePaginator;

    /**
     * Create a new employee.
     *
     * @param array $data
     * @return Employee
     */
    public function createEmployee(array $data): Employee;

    /**
     * Update an existing employee.
     *
     * @param Employee $employee
     * @param array $data
     * @return Employee
     */
    public function updateEmployee(Employee $employee, array $data): Employee;

    /**
     * Toggle employee active status.
     *
     * @param Employee $employee
     * @return Employee
     */
    public function toggleActive(Employee $employee): Employee;
    
    /**
     * Find employee by UUID.
     *
     * @param string $uuid
     * @return Employee|null
     */
    public function findByUuid(string $uuid): ?Employee;
    
    /**
     * Process and store employee photo.
     *
     * @param mixed $photo
     * @param Employee $employee
     * @return string|null Photo path if successful, null otherwise
     */
    public function processEmployeePhoto($photo, Employee $employee): ?string;
    
    /**
     * Get the list of accessible provider IDs for the current user.
     *
     * @return array
     */
    public function getAccessibleProviderIds(): array;
}
