<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Requests\Role\DeleteRoleRequest;
use App\Models\Role;
use App\Services\Role\RoleServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends BaseController
{
    /**
     * @var RoleServiceInterface
     */
    protected $roleService;
    
    /**
     * RoleController constructor.
     * 
     * @param RoleServiceInterface $roleService
     */
    public function __construct(RoleServiceInterface $roleService)
    {
        $this->roleService = $roleService;
    }
    /**
     * Muestra un listado de roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request): \Inertia\Response
    {
        try {
            // Obtener roles paginados usando el servicio
            $roles = $this->roleService->getPaginatedRoles($request);
            
            // El conteo de permisos y la transformación de nombres ya se realizó en el servicio
            // Convertir la colección de nombres de permisos a un string separado por comas para mejor visualización
            $roles->through(function ($role) {
                // Si permissions es una colección, convertirla a un string
                if (is_object($role->permissions) && method_exists($role->permissions, 'implode')) {
                    $permissionString = $role->permissions->implode(', ');
                    if (empty($permissionString)) {
                        $permissionString = 'Sin permisos';
                    }
                    $role->permissions = $permissionString;
                }
                return $role;
            });

            // Preparar estadísticas
            $stats = [
                [
                    'value' => $roles->total(), // Use total from pagination
                    'label' => 'Total Roles',
                    'icon' => 'shield-check', // Example icon from lucide-react
                    'color' => 'blue'
                ],
                // Add more stats here if needed, e.g.:
                // [
                //    'value' => Role::where('guard_name', 'web')->count(),
                //    'label' => 'Guard Web',
                //    'icon' => 'monitor-check',
                //    'color' => 'green'
                // ],
            ];

            // Registrar acción para auditoría
            $this->logAction('listar', 'roles', null, [
                'filters' => $request->all(),
                'total' => $roles->total()
            ]);

            // Responder con la vista Inertia con el layout correcto y stats
            return $this->respondWithSuccess('roles/index', [ 
                'roles' => $roles,
                'stats' => $stats, // Pass stats to the view
                'filters' => $request->only([
                    'search', 'sort', 'order', 'per_page',
                    'permission_module' // Solo mantener el filtro de módulo de permisos
                ])
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar roles');
        }
    }

    /**
     * Muestra el formulario para crear un nuevo rol.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        try {
            // Obtener todos los permisos disponibles usando el servicio
            $permissions = $this->roleService->getAllPermissions();
            
            return $this->respondWithSuccess('roles/create', [
                'permissions' => $permissions
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear rol (formulario)');
        }
    }

    /**
     * Almacena un nuevo rol en la base de datos.
     *
     * @param  \App\Http\Requests\Role\StoreRoleRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRoleRequest $request)
    {
        try {
            // Crear el rol usando el servicio
            $role = $this->roleService->createRole($request->validated());
            
            // Registrar acción para auditoría
            $this->logAction('crear', 'rol', $role->id, [
                'name' => $role->name,
                'permissions' => $request->permissions
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol creado correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear rol');
        }
    }

    /**
     * Muestra el formulario para editar un rol.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Inertia\Response
     */
    public function edit(Role $role)
    {
        try {
            // Obtener rol con sus permisos usando el servicio
            $data = $this->roleService->getRoleWithPermissions($role);
            
            return $this->respondWithSuccess('roles/edit', $data);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Editar rol (formulario)');
        }
    }

    /**
     * Actualiza la información de un rol.
     *
     * @param  \App\Http\Requests\Role\UpdateRoleRequest  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        try {
            // Actualizar el rol usando el servicio
            $role = $this->roleService->updateRole($role, $request->validated());
            
            // Registrar acción para auditoría
            $this->logAction('actualizar', 'rol', $role->id, [
                'name' => $role->name,
                'permissions' => $request->permissions
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol actualizado correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Actualizar rol');
        }
    }

    /**
     * Elimina un rol.
     *
     * @param  \App\Http\Requests\Role\DeleteRoleRequest  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DeleteRoleRequest $request, Role $role)
    {
        try {
            // Eliminar el rol usando el servicio
            $this->roleService->deleteRole($role);
            
            // Registrar acción para auditoría
            $this->logAction('eliminar', 'rol', $role->id, [
                'name' => $role->name
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol eliminado correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Eliminar rol');
        }
    }
    
    /**
     * Muestra los detalles de un rol específico.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Inertia\Response
     */
    public function show(Role $role)
    {
        try {
            // Obtener rol con sus permisos usando el servicio
            $data = $this->roleService->getRoleWithPermissions($role);
            
            return $this->respondWithSuccess('roles/show', $data);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Ver detalles del rol');
        }
    }
}
