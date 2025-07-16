<?php

namespace App\Repositories\Provider;

use App\Models\Provider;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentProviderRepository extends BaseRepository implements ProviderRepositoryInterface
{
    /**
     * EloquentProviderRepository constructor.
     *
     * @param Provider $model
     */
    public function __construct(Provider $model)
    {
        parent::__construct($model);
    }
    
    /**
     * {@inheritdoc}
     */
    public function createOrUpdateInternal($area, $userId)
    {
        // Buscar si ya existe un proveedor interno para esta área
        $internalProvider = $this->model->where([
            'area_id' => $area->id,
            'type' => 'internal'
        ])->first();
        
        // Obtener datos del usuario gerente
        $user = User::findOrFail($userId);
        
        // Datos para crear o actualizar el proveedor interno
        $providerData = [
            'name' => "Interno {$area->name}",
            'description' => "Proveedor interno gestionado por {$user->name}",
            'email' => $user->email,
            'phone' => property_exists($user, 'phone') ? ($user->phone ?? '') : '',
            'address' => '',
            'type' => 'internal',
            'active' => true,
            'area_id' => $area->id,
            'user_id' => $userId
        ];
        
        if ($internalProvider) {
            // Actualizar proveedor existente
            $internalProvider->fill($providerData);
            $internalProvider->save();
            return $internalProvider;
        } else {
            // Crear nuevo proveedor
            return $this->model->create($providerData);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 10, array $relations = [], array $filters = [], array $sortOptions = []): LengthAwarePaginator
    {
        $query = $this->model->with(array_merge(['user', 'area'], $relations));

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('rif', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('email', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if (isset($filters['area_id'])) {
            $query->byArea($filters['area_id']);
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['active'])) {
            $query->active($filters['active']);
        }

        // Apply sorting
        $sortField = $sortOptions['field'] ?? 'created_at';
        $sortDirection = $sortOptions['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid, array $relations = []): ?Provider
    {
        return $this->model
            ->where('uuid', $uuid)
            ->with(array_merge(['user', 'area'], $relations))
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function createExternal(array $data): Provider
    {
        return DB::transaction(function () use ($data) {
            // Create user with editor role (permiso base para acceder)
            $userData = $data['user'];
            $user = User::create($userData);
            $user->assignRole('editor');
            
            // Create provider and link to user
            $providerData = [
                'area_id' => $data['area_id'],
                'user_id' => $user->id,
                'name' => $data['name'],
                'rif' => $data['rif'],
                'phone' => $data['phone'] ?? null,
                'type' => 'external',
                'active' => $data['active'] ?? true,
            ];
            
            $provider = $this->model->create($providerData);
            
            return $provider->load('user', 'area');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function createInternal(int $areaId, ?int $managerUserId = null, array $data = []): Provider
    {
        return DB::transaction(function () use ($areaId, $managerUserId, $data) {
            // Buscar si ya existe un proveedor interno para esta área
            $existingProvider = $this->model->where([
                'area_id' => $areaId,
                'type' => 'internal'
            ])->first();
            
            if ($existingProvider) {
                throw new \Exception('Ya existe un proveedor interno para esta área.');
            }
            
            // Obtener el área
            $area = \App\Models\Area::findOrFail($areaId);
            
            // Preparar datos del proveedor
            $providerData = [
                'area_id' => $areaId,
                'user_id' => $managerUserId, // Puede ser null
                'name' => $data['name'] ?? ('Interno ' . $area->name),
                'rif' => $data['rif'] ?? ('INTERNAL-' . $areaId), // RIF único para proveedores internos
                'phone' => $data['phone'] ?? null,
                'type' => 'internal',
                'active' => $data['active'] ?? ($managerUserId ? true : false) // Solo activo por defecto si tiene gerente
            ];
            
            // Verificar si hay un usuario gerente asignado
            if ($managerUserId) {
                $user = User::findOrFail($managerUserId);
                // Verificar que el usuario tenga permisos de gerente de área
                if (!$user->hasRole('area_manager')) {
                    throw new \Exception('El usuario asignado no tiene el rol de gerente de área.');
                }
            }
            
            return $this->model->create($providerData);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateProvider(string $uuid, array $data): Provider
    {
        return DB::transaction(function () use ($uuid, $data) {
            $provider = $this->findByUuid($uuid);
            
            if (!$provider) {
                throw new \Exception('Proveedor no encontrado');
            }
            
            // Update user if user data is provided
            if (isset($data['user'])) {
                $userData = $data['user'];
                $user = $provider->user;
                
                // Only update fields that are provided
                if (isset($userData['name'])) {
                    $user->name = $userData['name'];
                }
                
                if (isset($userData['email'])) {
                    $user->email = $userData['email'];
                }
                
                if (isset($userData['password']) && !empty($userData['password'])) {
                    $user->password = bcrypt($userData['password']);
                }
                
                if (isset($userData['active'])) {
                    $user->active = $userData['active'];
                }
                
                $user->save();
            }
            
            // Update provider data
            $updateData = array_intersect_key($data, array_flip([
                'name', 'rif', 'phone', 'area_id', 'active'
            ]));
            
            $provider->update($updateData);
            return $provider->fresh(['user', 'area']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function toggleActive(string $uuid, bool $active): void
    {
        DB::transaction(function () use ($uuid, $active) {
            $provider = $this->findByUuid($uuid);
            
            if (!$provider) {
                throw new \Exception('Proveedor no encontrado');
            }
            
            // Update provider status
            $provider->active = $active;
            $provider->save();
            
            // Update user status
            $provider->user->active = $active;
            $provider->user->save();
            
            // Apply soft delete if deactivating
            if (!$active) {
                $provider->delete();
            } else if ($provider->trashed()) {
                // Restore if activating a soft-deleted provider
                $provider->restore();
            }
        });
    }
}
