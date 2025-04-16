<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Muestra un listado de roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            // Iniciar la consulta
            $query = Role::query();
            
            // Aplicar filtros de búsqueda si existen
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('name', 'like', "%{$search}%");
            }
            
            // Aplicar ordenamiento
            $sortField = $request->input('sort', 'id');
            $sortOrder = $request->input('order', 'desc');
            
            // Verificar que el campo de ordenamiento es válido
            if (in_array($sortField, ['id', 'name', 'created_at'])) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
            
            // Paginación
            $perPage = (int) $request->input('per_page', 10);
            $roles = $query->paginate($perPage);
            
            // Cargar los permisos para cada rol
            $roles->getCollection()->transform(function ($role) {
                $role->permissions = $role->permissions->pluck('name');
                return $role;
            });
            
            // Registrar acción para auditoría
            $this->logAction('listar', 'roles', null, [
                'filters' => $request->all(),
                'total' => $roles->total()
            ]);
            
            // Responder con la vista Inertia con el layout correcto
            return $this->respondWithSuccess('roles/index', [
                'roles' => $roles,
                'filters' => $request->only(['search', 'sort', 'order', 'per_page'])
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
        // Obtener todos los permisos disponibles
        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'nameshow' => $permission->nameshow
            ];
        });
        
        return $this->respondWithSuccess('roles/create', [
            'permissions' => $permissions
        ]);
    }

    /**
     * Almacena un nuevo rol en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // Validar los datos del formulario
            $data = $this->validateRequest($request, [
                'name' => ['required', 'string', 'max:255', 'unique:roles'],
                'permissions' => ['required', 'array'],
                'permissions.*' => ['exists:permissions,name']
            ]);
            
            // Crear el rol dentro de una transacción
            DB::beginTransaction();
            
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web'
            ]);
            
            // Asignar permisos
            $role->syncPermissions($data['permissions']);
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('crear', 'rol', $role->id, [
                'name' => $role->name,
                'permissions' => $data['permissions']
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol creado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
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
        // Obtener todos los permisos disponibles
        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'nameshow' => $permission->nameshow
            ];
        });
        
        // Obtener permisos asignados al rol
        $rolePermissions = $role->permissions->pluck('name');
        
        return $this->respondWithSuccess('roles/edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions
        ]);
    }

    /**
     * Actualiza la información de un rol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Role $role)
    {
        try {
            // Validar los datos del formulario
            $data = $this->validateRequest($request, [
                'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
                'permissions' => ['required', 'array'],
                'permissions.*' => ['exists:permissions,name']
            ]);
            
            // Actualizar el rol dentro de una transacción
            DB::beginTransaction();
            
            $role->update([
                'name' => $data['name']
            ]);
            
            // Sincronizar permisos
            $role->syncPermissions($data['permissions']);
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('actualizar', 'rol', $role->id, [
                'name' => $role->name,
                'permissions' => $data['permissions']
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol actualizado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Actualizar rol');
        }
    }

    /**
     * Elimina un rol.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $role)
    {
        try {
            // Verificar que no sea un rol del sistema
            if (in_array($role->name, ['admin', 'user'])) {
                return $this->redirectWithError('roles.index', [], 'No se puede eliminar un rol del sistema');
            }
            
            // Eliminar el rol dentro de una transacción
            DB::beginTransaction();
            
            // Eliminar relaciones y luego el rol
            $role->syncPermissions([]);
            $role->delete();
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('eliminar', 'rol', $role->id, [
                'name' => $role->name
            ]);
            
            return $this->redirectWithSuccess('roles.index', [], 'Rol eliminado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Eliminar rol');
        }
    }
}
