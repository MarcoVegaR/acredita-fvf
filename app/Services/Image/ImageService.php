<?php

namespace App\Services\Image;

use App\Events\Image\ImageDeleted;
use App\Events\Image\ImageUploaded;
use App\Models\Image;
use App\Models\ImageType;
use App\Repositories\Image\ImageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService implements ImageServiceInterface
{
    /**
     * @var ImageRepositoryInterface
     */
    protected $repository;

    /**
     * ImageService constructor.
     *
     * @param ImageRepositoryInterface $repository
     */
    public function __construct(ImageRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

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
    public function upload(UploadedFile $file, string $module, int $entityId, string $typeCode, int $userId): Image
    {
        // Validate image type
        $imageType = ImageType::where('code', $typeCode)
            ->where('module', $module)
            ->firstOrFail();

        // Get entity type from module
        $entityType = $this->getEntityTypeFromModule($module);

        // Start transaction
        return DB::transaction(function () use ($file, $module, $entityId, $imageType, $entityType, $userId) {
            // Create image record
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Generate UUID and path
            $uuid = (string) \Illuminate\Support\Str::uuid();
            $relativePath = "images/{$module}/" . date('Y/m');
            $filename = "{$uuid}.{$extension}";
            $fullPath = "{$relativePath}/{$filename}";

            // Get image dimensions
            $dimensions = getimagesize($file->getPathname());
            $width = $dimensions[0] ?? null;
            $height = $dimensions[1] ?? null;

            // Store original image
            Storage::disk('public')->putFileAs($relativePath, $file, $filename);

            // Create thumbnail
            $thumbnailPath = "{$relativePath}/{$uuid}_thumb.{$extension}";
            
            // Crear un ImageManager con el driver GD (utilizando la nueva API de Intervention Image v3)
            $manager = new ImageManager(new Driver());
            $thumbnail = $manager->read($file->getPathname());
            $thumbnail->scale(width: 200);
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode()->toString());

            // Create image record
            $image = new Image([
                'name' => $originalName,
                'path' => $fullPath,
                'mime_type' => $mimeType,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'created_by' => $userId,
            ]);
            $image->save();

            // Attach to entity
            $this->repository->attachToEntity(
                $image->id,
                $entityType,
                $entityId,
                $imageType->id
            );

            // Dispatch event
            ImageUploaded::dispatch($image, $module, $entityId);

            return $image;
        });
    }

    /**
     * Delete an image by its ID.
     *
     * @param int $imageId The image ID
     * @return bool
     */
    public function delete(int $imageId): bool
    {
        $image = $this->repository->find($imageId);

        if (!$image) {
            return false;
        }

        return DB::transaction(function () use ($image) {
            // Delete files
            Storage::disk('public')->delete($image->path);
            Storage::disk('public')->delete($image->thumbnail_path);

            // Get image details for event
            $imageData = [
                'id' => $image->id,
                'uuid' => $image->uuid,
                'name' => $image->name,
            ];

            // Delete image record (this will cascade to imageables due to foreign key constraint)
            $result = $image->delete();

            // Dispatch event if deletion was successful
            if ($result) {
                ImageDeleted::dispatch($imageData);
            }

            return $result;
        });
    }

    /**
     * List images for a specific entity.
     *
     * @param string $module The module name
     * @param int $entityId The entity ID
     * @return Collection
     */
    public function list(string $module, int $entityId): Collection
    {
        return $this->repository->listByEntity($module, $entityId);
    }

    /**
     * Get an image by UUID.
     *
     * @param string $uuid The image UUID
     * @return Image|null
     */
    public function getByUuid(string $uuid): ?Image
    {
        return $this->repository->findByUuid($uuid);
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
