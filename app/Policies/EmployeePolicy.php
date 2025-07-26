<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\Employee\EmployeeServiceInterface;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    protected $employeeService;

    /**
     * Create a new policy instance.
     */
    public function __construct(EmployeeServiceInterface $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Determine whether the user can view any employees.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('security_manager')
            || $user->hasRole('area_manager')
            || $user->hasRole('provider');
    }

    /**
     * Determine whether the user can view the employee.
     *
     * @param User $user
     * @param Employee $employee
     * @return bool
     */
    public function view(User $user, Employee $employee): bool
    {
        $accessibleProviderIds = $this->employeeService->getAccessibleProviderIds();
        return in_array($employee->provider_id, $accessibleProviderIds);
    }

    /**
     * Determine whether the user can create employees.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('security_manager')
            || $user->hasRole('area_manager')
            || $user->hasRole('provider');
    }

    /**
     * Determine whether the user can update the employee.
     *
     * @param User $user
     * @param Employee $employee
     * @return bool
     */
    public function update(User $user, Employee $employee): bool
    {
        $accessibleProviderIds = $this->employeeService->getAccessibleProviderIds();
        return in_array($employee->provider_id, $accessibleProviderIds);
    }

    /**
     * Determine whether the user can toggle active status of the employee.
     *
     * @param User $user
     * @param Employee $employee
     * @return bool
     */
    public function toggleActive(User $user, Employee $employee): bool
    {
        $accessibleProviderIds = $this->employeeService->getAccessibleProviderIds();
        return in_array($employee->provider_id, $accessibleProviderIds);
    }

    /**
     * Determine whether the user can delete the employee.
     *
     * @param User $user
     * @param Employee $employee
     * @return bool
     */
    public function delete(User $user, Employee $employee): bool
    {
        // Only admin and security_manager can permanently delete employees
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
            return true;
        }

        // Area managers and providers can only soft delete (deactivate) their employees
        $accessibleProviderIds = $this->employeeService->getAccessibleProviderIds();
        return in_array($employee->provider_id, $accessibleProviderIds);
    }
}
