<?php

namespace App\Console\Commands;

use App\Services\PrintBatch\PrintBatchServiceInterface;
use Illuminate\Console\Command;

class CleanupPrintBatches extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'print-batches:cleanup {--days=90 : Number of days old to consider for cleanup}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old print batch PDF files and archive batches';

    public function __construct(
        private PrintBatchServiceInterface $printBatchService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysOld = (int) $this->option('days');

        $this->info("Iniciando limpieza de lotes de impresión más antiguos que {$daysOld} días...");

        try {
            $result = $this->printBatchService->cleanupOldBatches($daysOld);

            $this->info("Limpieza completada exitosamente:");
            $this->line("- Archivos PDF eliminados: {$result['cleaned_files']}");
            $this->line("- Lotes archivados: {$result['archived_batches']}");
            $this->line("- Total procesados: {$result['total_processed']}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error durante la limpieza: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
