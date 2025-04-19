<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The document that was uploaded.
     *
     * @var Document
     */
    public $document;
    
    /**
     * The module the document was uploaded to.
     *
     * @var string
     */
    public $module;
    
    /**
     * The entity ID the document was associated with.
     *
     * @var int
     */
    public $entityId;

    /**
     * Create a new event instance.
     *
     * @param Document $document
     * @param string $module
     * @param int $entityId
     */
    public function __construct(Document $document, string $module, int $entityId)
    {
        $this->document = $document;
        $this->module = $module;
        $this->entityId = $entityId;
    }
}
