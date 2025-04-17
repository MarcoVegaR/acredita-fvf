<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    /**
     * Respond with a successful Inertia view
     *
     * @param string $view
     * @param array $data
     * @param string|null $message
     * @return Response
     */
    protected function respondWithSuccess(string $view, array $data = [], ?string $message = null): Response
    {
        // If message is provided, flash it to the session
        if ($message) {
            session()->flash('success', $message);
        }
        
        // Return Inertia view
        return Inertia::render($view, $data);
    }
    
    /**
     * Redirect with success message
     *
     * @param string $route
     * @param array $parameters
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectWithSuccess(string $route, array $parameters = [], string $message = ''): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route($route, $parameters)
            ->with('success', $message);
    }
    
    /**
     * Respond with error
     *
     * @param string $message
     * @param int $status
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function respondWithError(string $message, int $status = 400)
    {
        if (request()->wantsJson()) {
            return response([
                'message' => $message
            ], $status);
        }
        
        return back()->withErrors(['message' => $message]);
    }
    
    /**
     * Standard exception handler
     *
     * @param \Throwable $exception
     * @param string $context
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleException(\Throwable $exception, string $context = ''): \Illuminate\Http\RedirectResponse
    {
        // Log the error with context
        Log::error("{$context}: " . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // User-friendly message
        $message = config('app.debug') 
            ? $exception->getMessage() 
            : 'Ha ocurrido un error al procesar tu solicitud.';
            
        return back()->withErrors(['message' => $message]);
    }
    
    /**
     * Log user action for audit purposes
     *
     * @param string $action
     * @param string $entity
     * @param int|null $entityId
     * @param array $metadata
     * @return void
     */
    protected function logAction(string $action, string $entity, ?int $entityId = null, array $metadata = []): void
    {
        // This is a placeholder for an audit logging system
        // In a real application, this would likely save to a dedicated audit log table
        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $userName = $user ? $user->name : 'Sistema';
        
        Log::info("Auditoría: $userName realizó '$action' en $entity" . ($entityId ? " #$entityId" : ''), [
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'data' => $metadata,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
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
     * Verifica si el usuario actual tiene un permiso específico
     *
     * @param string $permission Permiso a verificar
     * @return bool
     */
    protected function userCan(string $permission): bool
    {
        return Gate::allows($permission);
    }
    
    /**
     * Autoriza una acción según un permiso y responde con error si no está autorizado
     *
     * @param string $permission Permiso requerido
     * @param string|null $message Mensaje de error personalizado (opcional)
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeAction(string $permission, ?string $message = null): void
    {
        $this->authorize($permission, $message);
    }
}
