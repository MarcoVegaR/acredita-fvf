<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\DeleteUserRequest;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use App\Services\Document\DocumentServiceInterface;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends BaseController
{
    /**
     * The user service instance.
     *
     * @var UserServiceInterface
     */
    protected $userService;
    
    /**
     * The document service instance.
     *
     * @var DocumentServiceInterface
     */
    protected $documentService;

    /**
     * Create a new controller instance.
     *
     * @param UserServiceInterface $userService
     * @param DocumentServiceInterface $documentService
     */
    public function __construct(
        UserServiceInterface $userService,
        DocumentServiceInterface $documentService
    )
    {
        $this->userService = $userService;
        $this->documentService = $documentService;
    }

    /**
     * Muestra un listado de usuarios.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            // Obtener usuarios paginados a través del servicio
            $users = $this->userService->getPaginatedUsers($request);
            
            // Obtener estadísticas de usuarios
            $stats = $this->userService->getUserStats();
            
            // Registrar acción para auditoría
            $this->logAction('listar', 'usuarios', null, [
                'filters' => $request->all(),
                'total' => $users->total()
            ]);
            
            // Responder con la vista Inertia
            return $this->respondWithSuccess('users/index', [
                'users' => $users,
                'stats' => $stats,
                'filters' => $request->only(['search', 'sort', 'order', 'per_page', 'active', 'role'])
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
        // Obtener roles disponibles a través del servicio
        $roles = $this->userService->getAllRoles();
        
        return $this->respondWithSuccess('users/create', [
            'roles' => $roles
        ]);
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     *
     * @param  \App\Http\Requests\User\StoreUserRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        try {
            // El Form Request ya se encargó de la validación
            $data = $request->validated();
            
            // Crear usuario a través del servicio (la lógica de roles está en el servicio)
            $user = $this->userService->createUser($data);
            
            // Registrar acción para auditoría
            $this->logAction('crear', 'usuario', $user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray()
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
        try {
            // Obtener usuario con todos sus datos relevantes a través del servicio
            $viewData = $this->userService->getUserWithRolesAndStats($user);
            
            // Añadir información de documentos si el usuario tiene permisos
            if (PermissionHelper::hasAnyPermission(['documents.view.users', 'documents.view'])) {
                // Obtener tipos de documentos para el módulo de usuarios
                $viewData['documentTypes'] = $this->documentService->getDocumentTypes('users');
                
                // Obtener documentos del usuario
                $viewData['userDocuments'] = $this->documentService->list('users', $user->id);
                
                // Agregar permisos del usuario para verificación en el frontend
                $viewData['userPermissions'] = Auth::user()->getAllPermissions()->pluck('name')->toArray();
            }
            
            // Registrar acción para auditoría
            $this->logAction('ver', 'usuario', $user->id);
            
            return $this->respondWithSuccess('users/show', $viewData);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Ver usuario');
        }
    }

    /**
     * Muestra el formulario para editar un usuario.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function edit(User $user)
    {
        try {
            // Obtener usuario con todos sus datos relevantes a través del servicio
            $data = $this->userService->getUserWithRolesAndStats($user);
            
            // Registrar acción para auditoría
            $this->logAction('editar', 'usuario', $user->id);
            
            // Incluir userRoles separadamente para facilitar la selección en el formulario
            $data['userRoles'] = $data['user']->role_names;
            
            return $this->respondWithSuccess('users/edit', $data);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Editar usuario');
        }
    }

    /**
     * Actualiza la información de un usuario.
     *
     * @param  \App\Http\Requests\User\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            // El Form Request ya se encargó de la validación
            $data = $request->validated();
            
            // Actualizar usuario a través del servicio
            $user = $this->userService->updateUser($user, $data);
            
            // Registrar acción para auditoría incluyendo los roles actualizados
            $auditData = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->role_names
            ];
            
            $this->logAction('actualizar', 'usuario', $user->id, $auditData);
            
            return $this->redirectWithSuccess('users.index', [], 'Usuario actualizado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Actualizar usuario');
        }
    }

    /**
     * Elimina un usuario.
     *
     * @param  \App\Http\Requests\User\DeleteUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DeleteUserRequest $request, User $user)
    {
        DB::beginTransaction();
        try {
            // Guardar datos para la auditoría antes de eliminar
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
            
            // Eliminar usuario a través del servicio
            $this->userService->deleteUser($user);
            
            // Confirmar transacción
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
