<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getInactive()
    {
        return $this->model->inactive()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountsByStatus()
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'inactive' => $this->model->inactive()->count(),
            'deleted' => $this->model->onlyTrashed()->count(), // Agregar conteo de usuarios eliminados (soft deleted)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function assignRoles(User $user, array $roles)
    {
        $user->assignRole($roles);
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function syncRoles(User $user, array $roles)
    {
        $user->syncRoles($roles);
        return $user;
    }

    /**
     * Extend the paginate method to handle specific user filters
     *
     * @param int $perPage
     * @param array $relations
     * @param array $filters
     * @param array $sortOptions
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = [])
    {
        $query = $this->model->with($relations);
        
        // Handle search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
            unset($filters['search']);
        }
        
        // Handle active status filter
        if (isset($filters['active'])) {
            $isActive = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $isActive);
            unset($filters['active']);
        }
        
        // Handle role filter
        if (isset($filters['role'])) {
            $role = $filters['role'];
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
            unset($filters['role']);
        }
        
        // Apply remaining filters
        foreach ($filters as $field => $value) {
            if (is_callable($value)) {
                $value($query);
            } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                $query->where($field, $value);
            }
        }
        
        // Apply sorting
        $field = $sortOptions['field'] ?? 'id';
        $direction = $sortOptions['direction'] ?? 'desc';
        
        if (in_array($field, ['id', 'name', 'email', 'created_at', 'email_verified_at'])) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('id', 'desc');
        }
        
        $users = $query->paginate($perPage);
        
        // Transform users to include role_names for easier frontend handling
        $users->through(function ($user) {
            $user->role_names = $user->roles->pluck('name')->toArray();
            return $user;
        });
        
        return $users;
    }
}
