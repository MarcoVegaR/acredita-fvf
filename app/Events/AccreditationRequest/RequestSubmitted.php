<?php

namespace App\Events\AccreditationRequest;

use App\Models\AccreditationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $accreditationRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(AccreditationRequest $accreditationRequest)
    {
        $this->accreditationRequest = $accreditationRequest;
    }
}
