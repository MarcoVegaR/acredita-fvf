<?php

namespace App\Repositories\Image;

use App\Models\Image;
use App\Models\Imageable;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ImageRepository extends BaseRepository implements ImageRepositoryInterface
{
    /**
     * ImageRepository constructor.
     *
     * @param Image $model
     */
    public function __construct(Image $model)
    {
        parent::__construct($model);
    }

    /**
     * List images by entity.
     *
     * @param string $module The module name
     * @param int $entityId The entity ID
     * @return Collection
     */
    public function listByEntity(string $module, int $entityId): Collection
    {
        $entityType = $this->getEntityTypeFromModule($module);
        
        return Image::whereHas('imageables', function ($query) use ($entityType, $entityId) {
            $query->where('imageable_type', $entityType)
                  ->where('imageable_id', $entityId);
        })->with(['imageables.imageType'])->get();
    }

    /**
     * Find image by UUID.
     *
     * @param string $uuid
     * @param array $relations
     * @return Image|null
     */
    public function findByUuid(string $uuid, array $relations = []): ?Image
    {
        return $this->model->with($relations)->where('uuid', $uuid)->first();
    }
    
    /**
     * Associate an image with an entity.
     *
     * @param int $imageId The image ID
     * @param string $entityType The entity type (model class)
     * @param int $entityId The entity ID
     * @param int $imageTypeId The image type ID
     * @return void
     */
    public function attachToEntity(int $imageId, string $entityType, int $entityId, int $imageTypeId): void
    {
        Imageable::updateOrCreate(
            [
                'image_id' => $imageId,
                'imageable_type' => $entityType,
                'imageable_id' => $entityId,
                'image_type_id' => $imageTypeId,
            ]
        );
    }
    
    /**
     * Detach an image from an entity.
     *
     * @param int $imageId The image ID
     * @param string $entityType The entity type (model class)
     * @param int $entityId The entity ID
     * @return void
     */
    public function detachFromEntity(int $imageId, string $entityType, int $entityId): void
    {
        Imageable::where('image_id', $imageId)
                ->where('imageable_type', $entityType)
                ->where('imageable_id', $entityId)
                ->delete();
    }
    
    /**
     * Get entity type from module name.
     *
     * @param string $module
     * @return string
     */
    private function getEntityTypeFromModule(string $module): string
    {
        $moduleMap = [
            'users' => \App\Models\User::class,
            'roles' => \App\Models\Role::class,
            'documents' => \App\Models\Document::class,
            // Add other modules as they are created
        ];
        
        return $moduleMap[$module] ?? 'App\\Models\\' . ucfirst(rtrim($module, 's'));
    }
}
