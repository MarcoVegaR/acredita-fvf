<?php

namespace App\Services\Image;

use App\Models\Image;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface ImageServiceInterface
{
    /**
     * Upload an image and associate it with an entity.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $module The module name
     * @param int $entityId The entity ID
     * @param string $typeCode The image type code
     * @param int $userId The user ID who is uploading the image
     * @return Image
     */
    public function upload(UploadedFile $file, string $module, int $entityId, string $typeCode, int $userId): Image;

    /**
     * Delete an image by its ID.
     *
     * @param int $imageId The image ID
     * @return bool
     */
    public function delete(int $imageId): bool;

    /**
     * List images for a specific entity.
     *
     * @param string $module The module name
     * @param int $entityId The entity ID
     * @return Collection
     */
    public function list(string $module, int $entityId): Collection;

    /**
     * Get an image by UUID.
     *
     * @param string $uuid The image UUID
     * @return Image|null
     */
    public function getByUuid(string $uuid): ?Image;
}
