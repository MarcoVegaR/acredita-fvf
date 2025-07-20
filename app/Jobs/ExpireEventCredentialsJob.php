<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\Credential\CredentialServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireEventCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos timeout

    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->onQueue('credentials'); // Cola dedicada
    }

    /**
     * Execute the job.
     */
    public function handle(CredentialServiceInterface $credentialService): void
    {
        Log::info('[EXPIRE CREDENTIALS JOB] Expirando credenciales del evento', [
            'event_id' => $this->event->id,
            'event_name' => $this->event->name
        ]);

        try {
            $expiredCount = $credentialService->expireEventCredentials($this->event->id);

            Log::info('[EXPIRE CREDENTIALS JOB] Credenciales expiradas exitosamente', [
                'event_id' => $this->event->id,
                'expired_count' => $expiredCount
            ]);

        } catch (\Exception $e) {
            Log::error('[EXPIRE CREDENTIALS JOB] Error expirando credenciales', [
                'event_id' => $this->event->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
