<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Responde con una vista Inertia y datos para una operación exitosa
     *
     * @param string $component Componente Inertia a renderizar
     * @param array $props Propiedades para pasar al componente
     * @param string|null $message Mensaje de éxito para mostrar (opcional)
     * @return \Inertia\Response
     */
    protected function respondWithSuccess(string $component, array $props = [], ?string $message = null)
    {
        if ($message) {
            session()->flash('success', $message);
        }
        
        return Inertia::render($component, $props);
    }

    /**
     * Responde con una redirección y datos para una operación exitosa
     *
     * @param string $route Ruta a la que redireccionar
     * @param array $params Parámetros para la ruta (opcional)
     * @param string|null $message Mensaje de éxito para mostrar (opcional)
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectWithSuccess(string $route, array $params = [], ?string $message = null)
    {
        if ($message) {
            session()->flash('success', $message);
        }
        
        return redirect()->route($route, $params);
    }

    /**
     * Responde con un mensaje de error
     *
     * @param string $message Mensaje de error
     * @param array $errors Errores adicionales (opcional)
     * @param int $status Código de estado HTTP
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function respondWithError(string $message, array $errors = [], int $status = 422)
    {
        session()->flash('error', $message);
        
        return back()->withErrors($errors)->setStatusCode($status);
    }

    /**
     * Maneja y registra una excepción
     *
     * @param \Throwable $exception La excepción capturada
     * @param string $context Contexto adicional para el log
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleException(\Throwable $exception, string $context = '')
    {
        // Registra la excepción en los logs
        Log::error("[$context] {$exception->getMessage()}", [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $message = config('app.debug') 
            ? $exception->getMessage() 
            : 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo más tarde.';
            
        return $this->respondWithError($message);
    }

    /**
     * Valida los datos de la request
     *
     * @param Request $request La request a validar
     * @param array $rules Reglas de validación
     * @param array $messages Mensajes de error personalizados (opcional)
     * @return array Datos validados
     */
    protected function validateRequest(Request $request, array $rules, array $messages = [])
    {
        return $request->validate($rules, $messages);
    }

    /**
     * Registra una acción para auditoría
     *
     * @param string $action Acción realizada
     * @param string $entity Entidad sobre la que se realizó la acción
     * @param int|null $entityId ID de la entidad (opcional)
     * @param array $data Datos adicionales (opcional)
     * @return void
     */
    protected function logAction(string $action, string $entity, ?int $entityId = null, array $data = [])
    {
        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $userName = $user ? $user->name : 'Sistema';
        
        Log::info("Auditoría: $userName realizó '$action' en $entity" . ($entityId ? " #$entityId" : ''), [
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'data' => $data,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // Aquí se podría guardar en una tabla de auditoría si es necesario
    }

    /**
     * Verifica si el usuario actual tiene un permiso específico
     * (Placeholder para cuando se integre Spatie Permission)
     *
     * @param string $permission Permiso a verificar
     * @return bool
     */
    protected function userCan(string $permission)
    {
        // Esta es una implementación básica que se puede mejorar al integrar Spatie
        return Gate::allows($permission);
    }

    /**
     * Autoriza una acción según un permiso y responde con error si no está autorizado
     *
     * @param string $permission Permiso requerido
     * @param string $message Mensaje de error personalizado (opcional)
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeAction(string $permission, ?string $message = null)
    {
        $this->authorize($permission, $message);
    }
}
