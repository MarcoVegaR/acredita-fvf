<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Muestra un listado de usuarios.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            // Iniciar la consulta
            $query = User::query();
            
            // Aplicar filtros de búsqueda si existen
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Aplicar ordenamiento
            $sortField = $request->input('sort', 'id');
            $sortOrder = $request->input('order', 'desc');
            
            // Verificar que el campo de ordenamiento es válido
            if (in_array($sortField, ['id', 'name', 'email', 'created_at', 'email_verified_at'])) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
            
            // Paginación
            $perPage = (int) $request->input('per_page', 10);
            $users = $query->paginate($perPage);
            
            // Registrar acción para auditoría
            $this->logAction('listar', 'usuarios', null, [
                'filters' => $request->all(),
                'total' => $users->total()
            ]);
            
            // Responder con la vista Inertia con el layout correcto
            return $this->respondWithSuccess('users/index', [
                'users' => $users,
                'filters' => $request->only(['search', 'sort', 'order', 'per_page'])
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar usuarios');
        }
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // Aquí se podría agregar lógica para cargar datos adicionales,
        // como roles cuando se implemente Spatie Permission
        return $this->respondWithSuccess('users/create');
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // Validar los datos del formulario
            $data = $this->validateRequest($request, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            
            // Crear el usuario dentro de una transacción
            DB::beginTransaction();
            
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            
            // Asignar roles si fuera necesario (cuando se implemente Spatie)
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('crear', 'usuario', $user->id, [
                'name' => $user->name,
                'email' => $user->email
            ]);
            
            return $this->redirectWithSuccess('users.index', [], 'Usuario creado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Crear usuario');
        }
    }

    /**
     * Muestra los detalles de un usuario específico.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function show(User $user)
    {
        // Registrar acción para auditoría
        $this->logAction('ver', 'usuario', $user->id);
        
        return $this->respondWithSuccess('users/show', [
            'user' => $user
        ]);
    }

    /**
     * Muestra el formulario para editar un usuario.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function edit(User $user)
    {
        // Aquí se podría cargar información adicional como roles
        // cuando se implemente Spatie Permission
        
        return $this->respondWithSuccess('users/edit', [
            'user' => $user
        ]);
    }

    /**
     * Actualiza la información de un usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        try {
            // Validar los datos del formulario
            $data = $this->validateRequest($request, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
            
            // Actualizar el usuario dentro de una transacción
            DB::beginTransaction();
            
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];
            
            // Solo actualizar la contraseña si se proporciona una nueva
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            
            $user->update($userData);
            
            // Actualizar roles si fuera necesario (cuando se implemente Spatie)
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('actualizar', 'usuario', $user->id, [
                'name' => $user->name,
                'email' => $user->email
            ]);
            
            return $this->redirectWithSuccess('users.index', [], 'Usuario actualizado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Actualizar usuario');
        }
    }

    /**
     * Elimina un usuario.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        try {
            // Proteger contra la eliminación del usuario actual
            if (auth()->id() === $user->id) {
                return $this->respondWithError('No puedes eliminar tu propio usuario.');
            }
            
            // Eliminar usuario dentro de una transacción
            DB::beginTransaction();
            
            // Guardar datos para la auditoría antes de eliminar
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
            
            $user->delete();
            
            DB::commit();
            
            // Registrar acción para auditoría
            $this->logAction('eliminar', 'usuario', $userData['id'], $userData);
            
            return $this->redirectWithSuccess('users.index', [], 'Usuario eliminado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Eliminar usuario');
        }
    }
}
