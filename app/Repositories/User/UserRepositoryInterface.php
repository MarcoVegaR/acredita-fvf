<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email);
    
    /**
     * Get active users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive();
    
    /**
     * Get inactive users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInactive();
    
    /**
     * Get users count by status
     *
     * @return array
     */
    public function getCountsByStatus();
    
    /**
     * Assign roles to user
     *
     * @param User $user
     * @param array $roles
     * @return User
     */
    public function assignRoles(User $user, array $roles);
    
    /**
     * Sync roles for user
     *
     * @param User $user
     * @param array $roles
     * @return User
     */
    public function syncRoles(User $user, array $roles);
}
