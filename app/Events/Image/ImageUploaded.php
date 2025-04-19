<?php

namespace App\Events\Image;

use App\Models\Image;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Image
     */
    public $image;

    /**
     * @var string
     */
    public $module;

    /**
     * @var int
     */
    public $entityId;

    /**
     * Create a new event instance.
     */
    public function __construct(Image $image, string $module, int $entityId)
    {
        $this->image = $image;
        $this->module = $module;
        $this->entityId = $entityId;
    }
}
