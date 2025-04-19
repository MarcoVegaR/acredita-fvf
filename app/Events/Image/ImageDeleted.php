<?php

namespace App\Events\Image;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The image data.
     *
     * @var array
     */
    public $imageData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $imageData)
    {
        $this->imageData = $imageData;
    }
}
