<?php

namespace App\Repositories\Credential;

use App\Models\Credential;
use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface CredentialRepositoryInterface extends RepositoryInterface
{
    /**
     * Find credential by QR code
     *
     * @param string $qrCode
     * @return Credential|null
     */
    public function findByQRCode(string $qrCode): ?Credential;

    /**
     * Find credential by accreditation request
     *
     * @param int $accreditationRequestId
     * @return Credential|null
     */
    public function findByAccreditationRequest(int $accreditationRequestId): ?Credential;

    /**
     * Get credentials by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get credentials for an event
     *
     * @param int $eventId
     * @return Collection
     */
    public function getByEvent(int $eventId): Collection;

    /**
     * Mark credentials as expired for an event
     *
     * @param int $eventId
     * @return int Number of updated credentials
     */
    public function markEventCredentialsExpired(int $eventId): int;

    /**
     * Get credential statistics
     *
     * @return array
     */
    public function getStats(): array;
}
