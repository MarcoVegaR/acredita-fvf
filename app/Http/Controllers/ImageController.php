<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Http\Requests\Image\DeleteImageRequest;
use App\Http\Requests\Image\StoreImageRequest;
use App\Models\ImageType;
use App\Services\Image\ImageServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends BaseController
{
    /**
     * The image service instance.
     *
     * @var ImageServiceInterface
     */
    protected $imageService;

    /**
     * Create a new controller instance.
     *
     * @param ImageServiceInterface $imageService
     */
    public function __construct(ImageServiceInterface $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of images for a specific entity.
     *
     * @param Request $request
     * @param string $module
     * @param int $entityId
     * @return Response
     */
    /**
     * Display a listing of images for a specific entity.
     *
     * @param Request $request
     * @param string $module
     * @param int $entityId
     * @return Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $module, int $entityId)
    {
        // Check permission
        $this->validatePermission("images.view.{$module}");

        // Get images for the entity
        $images = $this->imageService->list($module, $entityId);

        // Get image types for the module
        $imageTypes = ImageType::forModule($module)->get();
        
        // Log activity
        $this->logActivity("Visualización de imágenes de {$module}", $module, $entityId);

        // Extract permissions for the frontend
        $permissions = [
            'canUpload' => PermissionHelper::hasAllPermissions(['images.upload', "images.upload.{$module}"]),
            'canDelete' => PermissionHelper::hasAllPermissions(['images.delete', "images.delete.{$module}"]),
        ];

        // Si es una solicitud AJAX/XHR normal (no Inertia), retornar JSON directamente
        if ($request->ajax() && !$request->header('X-Inertia')) {
            logger()->info('DEBUG - API de imágenes: Respondiendo solicitud AJAX', [
                'images_count' => count($images),
                'module' => $module,
                'entity_id' => $entityId
            ]);
            
            return response()->json([
                'images' => $images,
                'imageTypes' => $imageTypes,
                'permissions' => $permissions
            ]);
        }

        // Return the view basada en el módulo para solicitudes Inertia
        // Para cada módulo, renderizamos su propia vista de imágenes
        $viewMapping = [
            'users' => 'users/images',
            // Agregar aquí otros módulos cuando se agreguen
        ];
        
        // Verificar si existe una vista para el módulo, de lo contrario usar una genérica
        $view = $viewMapping[$module] ?? "$module/images";
        
        logger()->info('DEBUG - Inertia: Renderizando vista de imágenes', [
            'view' => $view,
            'images_count' => count($images),
            'module' => $module,
            'entity_id' => $entityId
        ]);
        
        return Inertia::render($view, [
            'user' => $module === 'users' ? \App\Models\User::find($entityId) : null,
            'images' => $images,
            'imageTypes' => $imageTypes,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created image in storage.
     *
     * @param StoreImageRequest $request
     * @return RedirectResponse
     */
    public function store(StoreImageRequest $request): RedirectResponse
    {
        // Get the validated data
        $validated = $request->validated();
        
        // Upload the image
        $image = $this->imageService->upload(
            $validated['file'],
            $validated['module'],
            $validated['entity_id'],
            $validated['type_code'],
            auth()->id()
        );
        
        // Log activity
        $this->logActivity(
            "Carga de imagen {$image->name}",
            $validated['module'],
            $validated['entity_id']
        );

        // Redirect back with success message
        return redirect()
            ->back()
            ->with('success', 'Imagen cargada exitosamente.');
    }

    /**
     * Remove the specified image from storage.
     *
     * @param DeleteImageRequest $request
     * @param string $module
     * @param int $entityId
     * @param string $uuid
     * @return RedirectResponse
     */
    public function destroy(DeleteImageRequest $request, string $module, int $entityId, string $uuid): RedirectResponse
    {
        // Get the image by UUID
        $image = $this->imageService->getByUuid($uuid);
        
        if (!$image) {
            return redirect()->back()->with('error', 'La imagen no se encontró.');
        }
        
        // Delete the image
        $result = $this->imageService->delete($image->id);
        
        // Log activity
        $this->logActivity(
            "Eliminación de imagen {$image->name}",
            $module,
            $entityId
        );

        // Redirect back with appropriate message
        return redirect()
            ->back()
            ->with($result ? 'success' : 'error', $result 
                ? 'Imagen eliminada exitosamente.' 
                : 'No se pudo eliminar la imagen.'
            );
    }

    /**
     * Validate if the user has the required permission.
     *
     * @param string $permission
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function validatePermission(string $permission): void
    {
        // Verificamos tanto el permiso específico como el permiso global (sin el sufijo del módulo)
        $basePermission = explode('.', $permission);
        if (count($basePermission) > 2) {
            // Si es un permiso de módulo (ej: images.view.users), también permitimos el global (images.view)
            $globalPermission = $basePermission[0] . '.' . $basePermission[1];
            if (!PermissionHelper::hasAnyPermission([$permission, $globalPermission])) {
                $this->authorize('someRandomAbilityThatWillFail');
            }
        } else {
            // Si es un permiso simple, verificamos solo ese
            if (!PermissionHelper::hasAnyPermission($permission)) {
                $this->authorize('someRandomAbilityThatWillFail');
            }
        }
    }

    /**
     * Log activity for auditing.
     *
     * @param string $action
     * @param string $module
     * @param int $entityId
     * @return void
     */
    protected function logActivity(string $action, string $module, int $entityId): void
    {
        // This is a placeholder for actual logging functionality
        // It could be implemented with a ActivityLog service
        logger()->info($action, [
            'user_id' => auth()->id(),
            'module' => $module,
            'entity_id' => $entityId,
            'ip' => request()->ip(),
        ]);
    }
}
