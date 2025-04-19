<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document that was deleted.
     *
     * @var Document
     */
    public $document;

    /**
     * Create a new event instance.
     *
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }
}
