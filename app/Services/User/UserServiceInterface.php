<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    /**
     * Get paginated list of users with filters
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(Request $request): LengthAwarePaginator;
    
    /**
     * Get user statistics
     *
     * @return array
     */
    public function getUserStats(): array;
    
    /**
     * Create new user
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User;
    
    /**
     * Get user by ID
     *
     * @param int $id
     * @return User
     */
    public function getUserById(int $id): User;
    
    /**
     * Update existing user
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User;
    
    /**
     * Delete user
     *
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user): bool;
    
    /**
     * Get all available roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoles();
    
    /**
     * Obtener usuario con sus datos completos para mostrar en la vista
     *
     * @param User $user
     * @return array
     */
    public function getUserWithRolesAndStats(User $user): array;
}
