<?php

namespace App\Repositories\Image;

use App\Models\Image;
use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ImageRepositoryInterface extends RepositoryInterface
{
    /**
     * List images by entity.
     *
     * @param string $module The module name
     * @param int $entityId The entity ID
     * @return Collection
     */
    public function listByEntity(string $module, int $entityId): Collection;

    /**
     * Find image by UUID.
     *
     * @param string $uuid
     * @param array $relations
     * @return Image|null
     */
    public function findByUuid(string $uuid, array $relations = []): ?Image;
    
    /**
     * Associate an image with an entity.
     *
     * @param int $imageId The image ID
     * @param string $entityType The entity type (model class)
     * @param int $entityId The entity ID
     * @param int $imageTypeId The image type ID
     * @return void
     */
    public function attachToEntity(int $imageId, string $entityType, int $entityId, int $imageTypeId): void;
    
    /**
     * Detach an image from an entity.
     *
     * @param int $imageId The image ID
     * @param string $entityType The entity type (model class)
     * @param int $entityId The entity ID
     * @return void
     */
    public function detachFromEntity(int $imageId, string $entityType, int $entityId): void;
}
