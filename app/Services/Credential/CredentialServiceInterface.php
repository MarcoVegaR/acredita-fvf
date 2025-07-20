<?php

namespace App\Services\Credential;

use App\Models\AccreditationRequest;
use App\Models\Credential;

interface CredentialServiceInterface
{
    /**
     * Create a credential for an approved accreditation request
     *
     * @param AccreditationRequest $request
     * @return Credential
     */
    public function createCredentialForRequest(AccreditationRequest $request): Credential;

    /**
     * Capture immutable snapshots of data
     *
     * @param Credential $credential
     * @return void
     */
    public function captureSnapshots(Credential $credential): void;

    /**
     * Generate QR code for credential
     *
     * @param Credential $credential
     * @return string QR code string
     */
    public function generateQRCode(Credential $credential): string;

    /**
     * Generate credential image
     *
     * @param Credential $credential
     * @return string Image file path
     */
    public function generateCredentialImage(Credential $credential): string;

    /**
     * Generate credential PDF
     *
     * @param Credential $credential
     * @return string PDF file path
     */
    public function generateCredentialPDF(Credential $credential): string;

    /**
     * Process complete credential generation
     *
     * @param Credential $credential
     * @return void
     */
    public function processCredentialGeneration(Credential $credential): void;

    /**
     * Verify a credential by QR code
     *
     * @param string $qrCode
     * @return array|null
     */
    public function verifyCredentialByQR(string $qrCode): ?array;

    /**
     * Mark credentials as expired for an event
     *
     * @param int $eventId
     * @return int Number of credentials marked as expired
     */
    public function expireEventCredentials(int $eventId): int;

    /**
     * Get credential statistics
     *
     * @return array
     */
    public function getCredentialStats(): array;
    
    /**
     * Regenerate a failed or expired credential
     *
     * @param Credential $credential
     * @return Credential
     */
    public function regenerateCredential(Credential $credential): Credential;
}
