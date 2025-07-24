<?php

namespace App\Policies;

use App\Models\PrintBatch;
use App\Models\User;

class PrintBatchPolicy
{
    /**
     * Determine whether the user can view any print batches.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('print_batch.manage');
    }

    /**
     * Determine whether the user can view the print batch.
     */
    public function view(User $user, PrintBatch $printBatch): bool
    {
        return $user->can('print_batch.manage');
    }

    /**
     * Determine whether the user can create print batches.
     */
    public function create(User $user): bool
    {
        return $user->can('print_batch.manage');
    }

    /**
     * Determine whether the user can download the print batch.
     */
    public function download(User $user, PrintBatch $printBatch): bool
    {
        return $user->can('print_batch.manage') && $printBatch->canBeDownloaded();
    }

    /**
     * Determine whether the user can retry the print batch.
     */
    public function retry(User $user, PrintBatch $printBatch): bool
    {
        return $user->can('print_batch.manage') && $printBatch->canBeRetried();
    }
}
