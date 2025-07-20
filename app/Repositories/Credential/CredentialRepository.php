<?php

namespace App\Repositories\Credential;

use App\Models\Credential;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CredentialRepository extends BaseRepository implements CredentialRepositoryInterface
{
    public function __construct(Credential $model)
    {
        parent::__construct($model);
    }

    /**
     * Find credential by QR code
     *
     * @param string $qrCode
     * @return Credential|null
     */
    public function findByQRCode(string $qrCode): ?Credential
    {
        return $this->model->where('qr_code', $qrCode)
            ->with(['accreditationRequest.employee', 'accreditationRequest.event', 'accreditationRequest.zones'])
            ->first();
    }

    /**
     * Find credential by accreditation request
     *
     * @param int $accreditationRequestId
     * @return Credential|null
     */
    public function findByAccreditationRequest(int $accreditationRequestId): ?Credential
    {
        return $this->model->where('accreditation_request_id', $accreditationRequestId)
            ->with(['accreditationRequest.employee', 'accreditationRequest.event', 'accreditationRequest.zones'])
            ->first();
    }

    /**
     * Get credentials by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
            ->with(['accreditationRequest.employee', 'accreditationRequest.event'])
            ->get();
    }

    /**
     * Get credentials for an event
     *
     * @param int $eventId
     * @return Collection
     */
    public function getByEvent(int $eventId): Collection
    {
        return $this->model->whereHas('accreditationRequest', function ($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })->with(['accreditationRequest.employee', 'accreditationRequest.event'])->get();
    }

    /**
     * Mark credentials as expired for an event
     *
     * @param int $eventId
     * @return int Number of updated credentials
     */
    public function markEventCredentialsExpired(int $eventId): int
    {
        return $this->model->whereHas('accreditationRequest', function ($query) use ($eventId) {
            $query->where('event_id', $eventId);
        })->where('is_active', true)->update([
            'is_active' => false,
            'expires_at' => now()
        ]);
    }

    /**
     * Get credential statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = $this->model->selectRaw('
            status,
            COUNT(*) as count
        ')->groupBy('status')->get();

        $result = [
            'pending' => 0,
            'generating' => 0,
            'ready' => 0,
            'failed' => 0,
            'total' => 0
        ];

        foreach ($stats as $stat) {
            $result[$stat->status] = $stat->count;
            $result['total'] += $stat->count;
        }

        return $result;
    }
}
