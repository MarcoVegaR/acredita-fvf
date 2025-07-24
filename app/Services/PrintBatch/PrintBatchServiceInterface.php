<?php

namespace App\Services\PrintBatch;

use App\Models\PrintBatch;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface PrintBatchServiceInterface
{
    /**
     * Queue a new print batch based on filters
     */
    public function queueBatch(array $filters, User $user): PrintBatch;

    /**
     * Get paginated print batches with filters
     */
    public function getPaginatedBatches(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get processing batches for frontend polling
     */
    public function getProcessingBatches(): array;

    /**
     * Download batch PDF
     */
    public function downloadBatch(PrintBatch $batch): string;

    /**
     * Get available filters data for the UI
     */
    public function getFiltersData(): array;

    /**
     * Validate filters before creating batch
     */
    public function validateFilters(array $filters): array;

    /**
     * Get batch statistics
     */
    public function getBatchStats(): array;

    /**
     * Retry failed batch
     */
    public function retryBatch(PrintBatch $batch): PrintBatch;

    /**
     * Clean up old batches
     */
    public function cleanupOldBatches(int $daysOld = 90): array;
}
