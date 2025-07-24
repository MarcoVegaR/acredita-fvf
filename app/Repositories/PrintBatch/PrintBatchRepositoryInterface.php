<?php

namespace App\Repositories\PrintBatch;

use App\Models\PrintBatch;
use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PrintBatchRepositoryInterface extends RepositoryInterface
{
    /**
     * Create a new print batch
     */
    public function createBatch(array $data): PrintBatch;

    /**
     * Get batches by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get processing batches for polling
     */
    public function getProcessingBatches(): Collection;

    /**
     * Get paginated batches with filters
     */
    public function getPaginatedWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Update batch status
     */
    public function updateStatus(PrintBatch $batch, string $status, array $additionalData = []): bool;

    /**
     * Get credentials ready for printing based on filters
     */
    public function getCredentialsForPrinting(array $filters): Collection;

    /**
     * Mark credentials as printed
     */
    public function markCredentialsAsPrinted(Collection $credentialIds, PrintBatch $batch): void;

    /**
     * Get batches for cleanup (older than specified days)
     */
    public function getBatchesForCleanup(int $daysOld = 90): Collection;

    /**
     * Archive old batches
     */
    public function archiveBatches(Collection $batchIds): int;
}
