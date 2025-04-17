<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\Role\RoleServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;
    
    /**
     * @var RoleServiceInterface
     */
    protected $roleService;

    /**
     * UserService constructor.
     *
     * @param UserRepositoryInterface $userRepository
     * @param RoleServiceInterface $roleService
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        RoleServiceInterface $roleService
    )
    {
        $this->userRepository = $userRepository;
        $this->roleService = $roleService;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedUsers(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        
        $filters = [];
        
        // Add search filter if provided
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Add active status filter if provided
        if ($request->has('active')) {
            $filters['active'] = $request->input('active');
        }
        
        // Add role filter if provided
        if ($request->has('role')) {
            $filters['role'] = $request->input('role');
        }
        
        // Set up sorting options
        $sortOptions = [
            'field' => $request->input('sort', 'id'),
            'direction' => $request->input('order', 'desc')
        ];
        
        return $this->userRepository->paginate(
            $perPage,
            ['roles'], // Always include roles relation
            $filters,
            $sortOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUserStats(): array
    {
        return $this->userRepository->getCountsByStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function createUser(array $data): User
    {
        // Normalizar roles (asegurar que sea un array)
        if (isset($data['roles']) && !is_array($data['roles'])) {
            $data['roles'] = [$data['roles']];
        }
        
        // Asignar rol predeterminado si no se proporciona ninguno
        $roleNames = $data['roles'] ?? ['user'];
        unset($data['roles']);
        
        // Validar roles contra el servicio de roles
        $validatedRoles = $this->roleService->validateRoleNames($roleNames);
        
        // Hash de contraseña
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        DB::beginTransaction();
        try {
            $user = $this->userRepository->create($data);
            $user->syncRoles($validatedRoles);
            DB::commit();
            
            // Cargar roles para el usuario recién creado
            $user->load('roles');
            
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id, ['roles']);
        
        if (!$user) {
            throw new \Exception("User not found");
        }
        
        // Transformar roles a un formato amigable para el frontend
        $user->role_names = $user->roles->pluck('name')->toArray();
        
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function updateUser(User $user, array $data): User
    {
        // Normalizar roles (asegurar que sea un array)
        if (isset($data['roles']) && !is_array($data['roles'])) {
            $data['roles'] = [$data['roles']];
        }
        
        $roleNames = $data['roles'] ?? null;
        unset($data['roles']);
        
        // Validar roles contra el servicio de roles si se proporcionaron
        $validatedRoles = null;
        if ($roleNames !== null) {
            $validatedRoles = $this->roleService->validateRoleNames($roleNames);
        }
        
        // Solo hacer hash de la contraseña si se proporciona
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        DB::beginTransaction();
        try {
            // Actualizar usuario
            $user = $this->userRepository->update($user->id, $data);
            
            // Actualizar roles si se proporcionaron y validaron
            if ($validatedRoles !== null) {
                $user->syncRoles($validatedRoles);
            }
            
            DB::commit();
            
            // Cargar roles actualizados
            $user->load('roles');
            $user->role_names = $user->roles->pluck('name')->toArray();
            
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUser(User $user): bool
    {
        // Check if trying to delete yourself
        if (auth()->id() === $user->id) {
            throw new \Exception("Cannot delete your own user account");
        }
        
        DB::beginTransaction();
        try {
            $result = $this->userRepository->delete($user->id);
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllRoles()
    {
        return $this->roleService->getAllRoles();
    }
    
    /**
     * Obtener usuario con sus datos completos para mostrar en la vista
     *
     * @param User $user
     * @return array
     */
    public function getUserWithRolesAndStats(User $user): array
    {
        // Cargar usuario con sus roles
        $user = $this->getUserById($user->id);
        
        // Obtener todos los roles disponibles
        $allRoles = $this->getAllRoles();
        
        // Obtener permisos del usuario (directos y a través de roles)
        // Añadimos también el nameshow para mostrar nombres legibles
        $permissions = $user->getAllPermissions()->map(function ($permission) {
            return [
                'name' => $permission->name,
                'nameshow' => $permission->nameshow ?? $permission->name
            ];
        });
        
        // Obtener permisos por rol
        $rolePermissions = [];
        foreach ($user->roles as $role) {
            $rolePermissions[$role->name] = $role->permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'nameshow' => $permission->nameshow ?? $permission->name
                ];
            })->toArray();
        }
        
        return [
            'user' => $user,
            'allRoles' => $allRoles,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ];
    }
}
