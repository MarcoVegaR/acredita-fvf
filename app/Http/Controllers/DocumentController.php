<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Http\Requests\Document\DeleteDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Services\Document\DocumentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends BaseController
{
    protected $documentService;
    
    /**
     * DocumentController constructor.
     *
     * @param DocumentServiceInterface $documentService
     */
    public function __construct(DocumentServiceInterface $documentService)
    {
        $this->documentService = $documentService;
    }
    
    /**
     * Display documents for a specific entity
     *
     * @param Request $request
     * @param string $module
     * @param int $entityId
     * @return \Inertia\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $module, int $entityId)
    {
        try {
            // Log completo de la solicitud para depuración
            \Log::info('DEBUG - INICIO SOLICITUD DOCUMENTOS', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'module' => $module,
                'entity_id' => $entityId,
                'user_id' => auth()->id(),
                'is_ajax' => $request->ajax(),
                'is_inertia' => $request->header('X-Inertia') ? true : false
            ]);

            // Validar que el módulo exista en la configuración
            if (!config("documents.modules.{$module}")) {
                \Log::warning('DEBUG - Módulo no configurado', ['module' => $module]);
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Módulo no configurado'], 404);
                }
                return $this->respondWithError('Módulo no configurado');
            }

            // Verificar permisos
            if (!PermissionHelper::hasAnyPermission(['documents.view', "documents.view.{$module}"])) {
                \Log::warning('DEBUG - Sin permisos para ver documentos', [
                    'user_id' => auth()->id(),
                    'permisos_requeridos' => ['documents.view', "documents.view.{$module}"]
                ]);
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'No tiene permisos para ver documentos'], 403);
                }
                return $this->respondWithError('No tiene permisos para ver documentos');
            }
        
            // Obtener todos los documentos asociados a esta entidad en este módulo
            $documents = $this->documentService->list($module, $entityId);
            
            // Obtener tipos de documentos disponibles SOLO para este módulo
            $documentTypes = $this->documentService->getDocumentTypes($module);
            $moduleConfig = config("documents.modules.{$module}");
            
            // Registrar acción para auditoría
            $this->logAction("ver", "documentos", null, ["module" => $module, "entity_id" => $entityId]);
            
            // Diferenciar entre solicitudes AJAX normales y solicitudes Inertia SPA
            if (($request->ajax() || $request->wantsJson()) && !$request->header('X-Inertia')) {
                \Log::info('DEBUG - Respondiendo JSON para solicitud AJAX normal');
                // Solicitud AJAX normal (no Inertia) - devolver JSON
                return response()->json([
                    'documents' => $documents,
                    'documentTypes' => $documentTypes,
                    'success' => true
                ]);
            }
            
            // Para solicitudes normales, devolver vista Inertia
            // Usar la vista adecuada según el módulo
            $viewPath = '';
            switch ($module) {
                case 'users':
                    $viewPath = 'users/documents';
                    
                    // 1. Obtener el usuario objetivo (el usuario específico cuyos documentos se están viendo)
                    $targetUser = \App\Models\User::findOrFail($entityId);
                    
                    \Log::info('DEBUG - USUARIO OBJETIVO (DOCUMENTOS)', [
                        'entity_id' => $entityId,
                        'target_user_id' => $targetUser->id,
                        'target_user_name' => $targetUser->name,
                        'url_completa' => $request->fullUrl()
                    ]);
                    
                    // 2. Obtener el usuario autenticado (la persona que está navegando el sistema)
                    $currentUser = auth()->user();
                    
                    // 3. Información de permisos del usuario autenticado
                    $userRoles = $currentUser ? $currentUser->getRoleNames()->toArray() : [];
                    $allUserPermissions = $currentUser ? $currentUser->getAllPermissions()->pluck('name')->toArray() : [];
                    $hasDocumentsUploadPermission = $currentUser ? $currentUser->hasPermissionTo('documents.upload') : false;
                    $hasDocumentsUploadUsersPermission = $currentUser ? $currentUser->hasPermissionTo('documents.upload.users') : false;
                    
                    \Log::info('DEBUG - USUARIO AUTENTICADO (PERMISOS)', [
                        'auth_user_id' => $currentUser ? $currentUser->id : null,
                        'auth_user_name' => $currentUser ? $currentUser->name : null,
                        'roles' => $userRoles,
                        'all_permissions' => $allUserPermissions,
                        'has_documents_upload' => $hasDocumentsUploadPermission,
                        'has_documents_upload_users' => $hasDocumentsUploadUsersPermission,
                        'is_admin' => in_array('admin', $userRoles)
                    ]);
                    
                    // 4. Obtener los permisos para enviar al frontend
                    $permissions = $allUserPermissions;
                    
                    // 5. Asegurar que los permisos de documentos estén disponibles para administradores
                    // (esto es una solución temporal para desarrollo/pruebas)
                    if ($currentUser && in_array('admin', $userRoles)) {
                        $documentsPermissions = [
                            'documents.view',
                            'documents.view.users', 
                            'documents.upload',
                            'documents.upload.users',
                            'documents.download',
                            'documents.download.users',
                            'documents.delete',
                            'documents.delete.users'
                        ];
                        
                        // Añadir permisos que falten
                        foreach ($documentsPermissions as $permission) {
                            if (!in_array($permission, $permissions)) {
                                $permissions[] = $permission;
                            }
                        }
                    }
                    
                    \Log::info('DEBUG - PERMISOS ENVIADOS AL FRONTEND', [
                        'permissions' => $permissions,
                        'documentsCount' => count($documents),
                        'typesCount' => count($documentTypes)
                    ]);
                    
                    // 6. IMPORTANTE: Usar $targetUser (no $currentUser) para la vista
                    return $this->respondWithSuccess($viewPath, [
                        'user' => $targetUser, // Pasar el usuario OBJETIVO, no el autenticado
                        'documents' => $documents,
                        'types' => $documentTypes,
                        'module' => $module,
                        'entityId' => $entityId,
                        'moduleConfig' => $moduleConfig,
                        'permissions' => $permissions
                    ]);
                    break;
                default:
                    // Para otros módulos que puedan implementarse en el futuro
                    return $this->respondWithError('Módulo de documentos no configurado');
            }
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                // Logear el error para depuración
                \Log::error("Error en DocumentController::index: {$e->getMessage()}", ['exception' => $e]);
                return response()->json(['error' => 'Error al cargar documentos', 'message' => $e->getMessage()], 500);
            }
            return $this->handleException($e, 'Listar documentos');
        }
    }
    
    /**
     * Upload a new document
     *
     * @param StoreDocumentRequest $request
     * @param string $module
     * @param int $entityId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreDocumentRequest $request, string $module, int $entityId)
    {
        try {
            $document = $this->documentService->upload(
                $request->file('file'),
                $module,
                $entityId,
                auth()->id(),
                $request->input('document_type_id')
            );
            
            $this->logAction("subir", "documento", null, [
                'module' => $module, 
                'entity_id' => $entityId,
                'document_type' => $request->input('document_type_id')
            ]);
            
            return redirect()->back()->with('success', 'Documento subido correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Subir documento');
        }
    }
    
    /**
     * Delete a document
     *
     * @param DeleteDocumentRequest $request
     * @param string $documentUuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DeleteDocumentRequest $request, string $documentUuid)
    {
        Log::info("DocumentController@destroy: Intentando eliminar", ['document_uuid' => $documentUuid]);
        try {
            $document = $this->documentService->getByUuid($documentUuid);
            Log::info("DocumentController@destroy: Documento obtenido desde servicio", ['document_id' => $document->id, 'original_filename' => $document->original_filename]);
            
            $this->documentService->delete($documentUuid);
            Log::info("DocumentController@destroy: Servicio de eliminación llamado", ['document_uuid' => $documentUuid]);
            
            // $this->logAction("Eliminó documento {$document->original_filename}"); // Comentado temporalmente por si causa error
            Log::info("DocumentController@destroy: Eliminación completada exitosamente (antes de redirect)");
            
            return redirect()->back()->with('success', 'Documento eliminado correctamente');
        } catch (\Throwable $e) {
            Log::error("Error en DocumentController@destroy", [
                'document_uuid' => $documentUuid,
                'exception_message' => $e->getMessage(),
                'exception_trace_short' => \Illuminate\Support\Str::limit($e->getTraceAsString(), 500) // Traza corta
            ]);
            // Re-lanza la excepción o maneja como antes
            return $this->handleException($e, 'Eliminar documento'); 
        }
    }
    
    /**
     * Download a document
     *
     * @param string $documentUuid
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(string $documentUuid)
    {
        try {
            $document = $this->documentService->getByUuid($documentUuid);
            
            $documentable = $document->documentables()->first();
            if (!$documentable) {
                return $this->respondWithError('El documento no está asociado a ninguna entidad');
            }
            
            // Obtener el model class del documentable
            $modelClass = $documentable->documentable_type;
            
            // Buscar el módulo correspondiente en la configuración
            $moduleKey = null;
            foreach (config('documents.modules') as $key => $moduleConfig) {
                if ($moduleConfig['model'] === $modelClass) {
                    $moduleKey = $key;
                    break;
                }
            }
            
            if (!$moduleKey) {
                \Log::error('Módulo no encontrado para el documento', [
                    'uuid' => $documentUuid,
                    'model_class' => $modelClass,
                    'documentable_id' => $documentable->documentable_id
                ]);
                return $this->respondWithError('Módulo no encontrado para este documento');
            }
            
            if (!PermissionHelper::hasAnyPermission(['documents.download', "documents.download.{$moduleKey}"])) {
                return $this->respondWithError('No tiene permisos para descargar documentos');
            }
            
            // Pasar los argumentos correctos a logAction
            $this->logAction("descargar", "documento", $document->id, [
                'filename' => $document->original_filename,
                'module' => $moduleKey,
                'entity_id' => $documentable->documentable_id
            ]);
            
            return Storage::disk('public')->download(
                $document->path,
                $document->original_filename
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Descargar documento');
        }
    }
}
