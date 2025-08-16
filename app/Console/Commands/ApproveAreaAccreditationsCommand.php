<?php

namespace App\Console\Commands;

use App\Enums\AccreditationStatus;
use App\Models\AccreditationRequest;
use App\Models\Area;
use App\Models\Credential;
use App\Models\Provider;
use App\Models\User;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use App\Services\PrintBatch\PrintBatchServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApproveAreaAccreditationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accreditations:approve-area
        {area_id : The ID of the area to process}
        {--batch-size=100 : Number of requests to approve per batch}
        {--wait-time=10 : Seconds to wait between batches}
        {--no-wait-credentials : Skip waiting for credential generation}
        {--max-wait=300 : Maximum seconds to wait for credentials}
        {--dry-run : Simulate the approval process without making changes}
        {--skip-errors : Continue processing other providers on error}
        {--system-user=system@acredita.local : Email of the system user for automated actions}';

    /**
     * The console command description.
     */
    protected $description = 'Approve all pending accreditation requests for a specific area with batch processing and print batch generation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $areaId = $this->argument('area_id');
        $batchSize = (int) $this->option('batch-size');
        $waitTime = (int) $this->option('wait-time');
        $waitCredentials = !$this->option('no-wait-credentials'); // Default true (wait)
        $maxWait = (int) $this->option('max-wait');
        $dryRun = (bool) $this->option('dry-run');
        $skipErrors = (bool) $this->option('skip-errors');
        $systemUserEmail = $this->option('system-user') ?: 'system@acredita.local';
        
        // Initialize services
        $accreditationRequestService = app(AccreditationRequestServiceInterface::class);
        $printBatchService = app(PrintBatchServiceInterface::class);
        
        if (!$accreditationRequestService || !$printBatchService) {
            $this->error('Required services are not available. Please check service providers.');
            return 1;
        }

        // Validate area exists
        $area = Area::with('providers')->find($areaId);
        if (!$area) {
            $this->error("Area with ID {$areaId} not found.");
            return 1;
        }
        
        // Get system user for automated operations
        $systemUser = User::where('email', $systemUserEmail)->first();
        if (!$systemUser) {
            $systemUser = User::first(); // Fallback to first user
            if (!$systemUser) {
                $this->error('No users found in the system. Cannot proceed.');
                return 1;
            }
            $this->warn("System user '{$systemUserEmail}' not found. Using fallback: {$systemUser->email}");
        }

        $this->info("Starting accreditation approval process for area: {$area->name}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE: No actual changes will be made to the database.");
        }

        // Get active providers in the area
        $providers = Provider::byArea($areaId)
            ->where('active', true)
            ->orderBy('name')
            ->get();
        
        $totalProviders = $providers->count();
        
        if ($totalProviders === 0) {
            $this->warn("No active providers found for area: {$area->name}");
            return 0;
        }

        $this->info("Found {$totalProviders} active providers to process.");
        $this->newLine();
        
        // Initialize tracking arrays
        $errors = [];
        $providerStats = [];

        $successCount = 0;
        $errorCount = 0;
        $totalApproved = 0;
        $totalPrintBatches = 0;
        $totalSkipped = 0;
        $totalCredentials = 0;

        // Process each provider
        foreach ($providers as $index => $provider) {
            $providerNumber = $index + 1;
            $this->info("[{$providerNumber}/{$totalProviders}] Processing provider: {$provider->name}");
            
            try {
                $providerResult = $this->processProvider(
                    $provider,
                    $area,
                    $accreditationRequestService,
                    $printBatchService,
                    $systemUser,
                    $errors,
                    $dryRun,
                    $skipErrors,
                    $batchSize,
                    $waitCredentials,
                    $waitTime
                );
                
                $providerStats[$provider->name] = $providerResult;
                $totalApproved += $providerResult['approved'];
                $totalSkipped += $providerResult['skipped'];
                $totalPrintBatches += $providerResult['print_batches'];
                $totalCredentials += $providerResult['credentials_generated'];
                
                $this->info("  ✅ Provider {$provider->name} completed: {$providerResult['approved']} approved, {$providerResult['credentials_generated']} credentials, {$providerResult['print_batches']} print batches");
                $this->info("  " . str_repeat("─", 70)); // Separator line
                
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("Error processing provider {$provider->name}: {$e->getMessage()}");
                $errors[] = [
                    'provider' => $provider->name,
                    'error' => $e->getMessage()
                ];
                Log::error("Error in ApproveAreaAccreditationsCommand for provider {$provider->id}", [
                    'provider' => $provider->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;

                if (!$skipErrors) {
                    $this->error("Aborting due to error. Use --skip-errors to continue despite errors.");
                    break;
                }
            }

            // Wait between providers to avoid overloading the queue
            if ($index < $totalProviders - 1 && !$dryRun && $waitTime > 0) {
                $this->info("Waiting {$waitTime} seconds before processing next provider...");
                sleep($waitTime);
            }
        }

        // Display summary
        $this->displaySummary(
            $dryRun,
            $area,
            $totalProviders,
            $successCount,
            $errorCount,
            $totalApproved,
            $totalPrintBatches,
            $totalSkipped,
            $providerStats,
            $errors
        );

        return $errorCount > 0 && !$skipErrors ? 1 : 0;
    }

    /**
     * Process a single provider's accreditation requests
     */
    private function processProvider(
        Provider $provider,
        Area $area,
        AccreditationRequestServiceInterface $accreditationRequestService,
        PrintBatchServiceInterface $printBatchService,
        User $systemUser,
        array &$errors,
        bool $dryRun = false,
        bool $skipErrors = false,
        int $batchSize = 100,
        bool $waitCredentials = true,
        int $waitTime = 10
    ): array {
        $stats = [
            'total' => 0,
            'approved' => 0,
            'skipped' => 0,
            'print_batches' => 0,
            'credentials_generated' => 0
        ];

        // First check if provider already has approved requests
        $approvedRequests = AccreditationRequest::whereHas('employee', function ($query) use ($provider) {
            $query->where('provider_id', $provider->id)
                ->where('active', true);
        })
            ->where('status', AccreditationStatus::Approved)
            ->get();

        $hasApprovedRequests = $approvedRequests->count() > 0;
        
        // Check if all approved requests have credentials ready
        $credentialsReady = 0;
        $hasPrintBatch = false;
        
        if ($hasApprovedRequests) {
            $credentialsReady = Credential::whereHas('accreditationRequest', function ($query) use ($provider) {
                $query->whereHas('employee', function ($q) use ($provider) {
                    $q->where('provider_id', $provider->id);
                })
                ->where('status', AccreditationStatus::Approved);
            })
                ->where('status', 'ready')
                ->count();
            
            // Check if print batch exists (provider_id is a JSON array field)
            $hasPrintBatch = \App\Models\PrintBatch::whereJsonContains('provider_id', $provider->id)
                ->where('event_id', $approvedRequests->pluck('event_id')->first())
                ->exists();
            
            $this->info("  Provider status: {$approvedRequests->count()} approved, {$credentialsReady} credentials ready, print batch: " . ($hasPrintBatch ? 'YES' : 'NO'));
        }

        // Query for pending accreditation requests (including drafts that need to be submitted)
        $requestsQuery = AccreditationRequest::with(['employee', 'event', 'zones'])
            ->whereHas('employee', function ($query) use ($provider) {
                $query->where('provider_id', $provider->id)
                    ->where('active', true);
            })
            ->whereIn('status', [AccreditationStatus::Draft, AccreditationStatus::Submitted, AccreditationStatus::UnderReview]);

        // Get count for validation
        $totalRequests = $requestsQuery->count();
        $stats['total'] = $totalRequests;

        if ($totalRequests === 0 && $hasApprovedRequests) {
            $this->info("  No pending requests. Provider already processed.");
            
            // If credentials are ready but no print batch, generate it
            if ($credentialsReady > 0 && !$hasPrintBatch) {
                $this->info("  Generating missing print batch...");
                $events = \App\Models\Event::whereIn('id', $approvedRequests->pluck('event_id')->unique())->get();
                $printBatchCount = $this->generatePrintBatchesForProvider(
                    $provider,
                    $events,
                    $printBatchService,
                    $systemUser,
                    $errors
                );
                $stats['print_batches'] = $printBatchCount;
                $this->info("  Generated {$printBatchCount} print batch(es)");
            }
            
            $stats['approved'] = $approvedRequests->count();
            $stats['credentials_generated'] = $credentialsReady;
            return $stats;
        } elseif ($totalRequests === 0) {
            $this->warn("  No pending accreditation requests found.");
            return $stats;
        }

        // Count by status
        $draftCount = (clone $requestsQuery)->where('status', AccreditationStatus::Draft)->count();
        $submittedCount = (clone $requestsQuery)->where('status', AccreditationStatus::Submitted)->count();
        $underReviewCount = (clone $requestsQuery)->where('status', AccreditationStatus::UnderReview)->count();
        
        $this->info("  Found {$totalRequests} requests: {$draftCount} draft, {$submittedCount} submitted, {$underReviewCount} under review");

        // Get distinct events for this provider's requests
        $eventIds = (clone $requestsQuery)->select('event_id')
            ->distinct()
            ->pluck('event_id');
        $events = \App\Models\Event::whereIn('id', $eventIds)->get();

        $this->info("  Events involved: " . $events->pluck('name')->implode(', '));

        // Submit draft requests first
        // Debug: let's see what the requestsQuery contains
        $allRequestIds = (clone $requestsQuery)->pluck('id')->toArray();
        $this->info("    Debug: Total request IDs from query: " . count($allRequestIds));
        $this->info("    Debug: Request IDs: " . implode(', ', array_slice($allRequestIds, 0, 10)));
        
        // Check draft status directly
        $draftCheckQuery = AccreditationRequest::whereIn('id', $allRequestIds)
            ->where('status', AccreditationStatus::Draft);
        $draftCount2 = $draftCheckQuery->count();
        $this->info("    Debug: Draft requests found by ID check: {$draftCount2}");
        
        // Get the draft requests
        $draftRequests = $draftCheckQuery
            ->with(['employee', 'employee.provider', 'event'])
            ->get();
        
        $this->info("    Found {$draftRequests->count()} draft requests to submit");
        
        foreach ($draftRequests as $request) {
            $this->info("      Submitting request #{$request->id}...");
            
            try {
                // Ensure status is loaded as an enum, not a relation
                $request->refresh();
                $accreditationRequestService->submitRequest($request);
                $this->info("        ✓ Request #{$request->id} submitted successfully");
            } catch (\Exception $e) {
                $this->error("    Failed to submit request #{$request->id}: {$e->getMessage()}");
                Log::error('[COMMAND] Failed to submit draft request', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                if (!$skipErrors) {
                    throw $e;
                }
            }
        }

        // Re-query to get submitted and under_review requests after submission
        $approvableRequests = (clone $requestsQuery)
            ->whereIn('status', [AccreditationStatus::Submitted, AccreditationStatus::UnderReview])
            ->get();

        // In dry-run, count draft requests as approvable
        if ($dryRun && $draftCount > 0) {
            $totalRequests = $draftCount + $approvableRequests->count();
            $this->info("  [DRY-RUN] Would approve {$totalRequests} requests (including {$draftCount} drafts after submission)");
        } else {
            $totalRequests = $approvableRequests->count();
        }

        if ($totalRequests === 0) {
            $this->warn("  No submitted or under_review requests to approve after submission.");
            return [
                'provider' => $provider->name,
                'total' => 0,
                'approved' => 0,
                'skipped' => 0,
                'credentials_generated' => 0,
                'print_batches' => 0
            ];
        }

        // Process in batches for approval
        if ($dryRun) {
            // In dry-run, simulate the approval process
            $approvedCount = $totalRequests;
            $totalBatches = ceil($totalRequests / $batchSize);
            $this->info("  [DRY-RUN] Would process {$totalBatches} batch(es) for approval");
            $this->info("    ✓ Would approve {$approvedCount} requests");
        } else {
            $requestChunks = $approvableRequests->chunk($batchSize);
            $approvedCount = 0;
            $batchErrors = [];

            foreach ($requestChunks as $index => $chunk) {
                $batchNumber = $index + 1;
                $totalBatches = ceil($approvableRequests->count() / $batchSize);
                $this->info("  Processing batch {$batchNumber}/{$totalBatches} ({$chunk->count()} requests)...");

                DB::beginTransaction();
                try {
                    foreach ($chunk as $request) {
                        $accreditationRequestService->approveRequest($request, $systemUser, 'Batch approval');
                        $approvedCount++;
                    }
                    DB::commit();
                    $this->info("    ✓ Batch {$batchNumber} approved successfully");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("    Failed to approve batch {$batchNumber}: {$e->getMessage()}");
                    $batchErrors[] = $e->getMessage();
                    if (!$skipErrors) {
                        throw $e;
                    }
                }
                
                // Wait between batches
                if ($index < count($requestChunks) - 1 && $waitTime > 0) {
                    $this->info("  Waiting {$waitTime} seconds before next batch...");
                    sleep($waitTime);
                }
            }
        }

        // Wait for credential generation if enabled
        $credentialCount = 0;
        if ($waitCredentials && $approvedCount > 0) {
            if ($dryRun) {
                $this->info("  [DRY-RUN] Would wait for credential generation to complete...");
                $credentialCount = $approvedCount;
                $this->info("    ✓ Would wait for {$credentialCount} credentials to be generated");
            } else {
                $credentialCount = $this->waitForCredentialGeneration(
                    $provider,
                    $events,
                    $approvedCount,
                    600,
                    false
                );
            }
        } else if (!$waitCredentials) {
            $this->info("  Skipping credential generation wait (--no-wait-credentials).");
        }

        // Generate print batches for this provider
        $printBatchCount = 0;
        if ($approvedCount > 0) {
            if ($dryRun) {
                $this->info("  [DRY-RUN] Would generate print batches for provider...");
                $printBatchCount = 1;
                $this->info("    ✓ Would generate {$printBatchCount} print batch(es)");
            } else {
                // Wait a bit for credentials to be fully ready
                sleep(5);
                $this->info("  Generating print batches for provider...");
                $printBatchCount = $this->generatePrintBatchesForProvider(
                    $provider,
                    $events,
                    $printBatchService,
                    $systemUser,
                    $errors
                );
            }
        }

        $this->info("  ✅ Provider {$provider->name} processed successfully");
        $this->info("    - Approved: {$approvedCount} requests");
        $this->info("    - Credentials: {$credentialCount} generated");
        $this->info("    - Print batches: {$printBatchCount} created\n");

        return [
            'provider' => $provider->name,
            'total' => $totalRequests,
            'approved' => $approvedCount,
            'skipped' => count($batchErrors ?? []),
            'credentials_generated' => $credentialCount,
            'print_batches' => $printBatchCount
        ];
    }

    /**
     * Wait for credential generation jobs to complete
     */
    private function waitForCredentialGeneration(
        Provider $provider,
        $events,
        int $expectedCount,
        int $maxWait,
        bool $dryRun = false
    ): int
    {
        $this->info("  ⏳ Waiting for credential generation to complete for provider: {$provider->name}...");
        $this->info("    Expected credentials: {$expectedCount}");

        $startTime = time();
        $waitInterval = 5; // Check every 5 seconds
        $elapsedTime = 0;
        $lastGeneratedCount = 0;
        $credentialsGenerated = 0;

        while ($elapsedTime < $maxWait) {
            // Check how many credentials exist for this provider's approved requests
            $generatedCount = Credential::whereHas('accreditationRequest', function ($query) use ($provider) {
                $query->whereHas('employee', function ($q) use ($provider) {
                    $q->where('provider_id', $provider->id);
                })
                ->where('status', AccreditationStatus::Approved);
            })
                ->where('status', 'ready')
                ->count();

            $credentialsGenerated = $generatedCount;

            // Check if we have reached the expected count
            if ($generatedCount >= $expectedCount) {
                $this->info("    ✅ All {$generatedCount} credentials generated successfully!");
                break;
            }

            // Only show progress if count changed
            if ($generatedCount !== $lastGeneratedCount) {
                $pendingCount = $expectedCount - $generatedCount;
                $this->info("    Progress: {$generatedCount}/{$expectedCount} credentials ready, {$pendingCount} pending");
                $lastGeneratedCount = $generatedCount;
            }

            // Sleep and update elapsed time
            sleep($waitInterval);
            $elapsedTime = time() - $startTime;
        }

        if ($elapsedTime >= $maxWait) {
            $this->warn("    ⚠️ Maximum wait time ({$maxWait}s) reached. {$credentialsGenerated}/{$expectedCount} credentials generated.");
        }

        return $credentialsGenerated;
    }

    /**
     * Generate print batches for a provider
     */
    private function generatePrintBatchesForProvider(
        Provider $provider,
        $events,
        PrintBatchServiceInterface $printBatchService,
        User $systemUser,
        array &$errors
    ): int {
        $this->info("  🖨️ Generating print batches for provider: {$provider->name}...");
        $batchCount = 0;

        if (empty($events)) {
            return $batchCount;
        }

        foreach ($events as $event) {
            try {
                // Check if there are unprinted credentials for this event/provider
                $unprintedCount = Credential::whereHas('accreditationRequest', function ($query) use ($provider, $event) {
                    $query->whereHas('employee', function ($q) use ($provider) {
                        $q->where('provider_id', $provider->id);
                    })
                    ->where('event_id', $event->id);
                })
                    ->where('status', 'ready')
                    ->whereNull('print_batch_id')
                    ->count();

                if ($unprintedCount === 0) {
                    $this->info("    ⚠️ No unprinted credentials for event: {$event->name}");
                    continue;
                }

                // Queue print batch for this provider and event
                $printBatch = $printBatchService->queueBatch([
                    'event_id' => $event->id,
                    'provider_id' => [$provider->id],
                    'area_id' => [$provider->area_id],
                    'only_unprinted' => true
                ], $systemUser);

                if ($printBatch && isset($printBatch->id)) {
                    $batchCount++;
                    $credentialCount = $printBatch->credentials()->count();
                    $this->info("    ✅ Print batch #{$printBatch->id} created for event: {$event->name} ({$credentialCount} credentials)");
                } else {
                    $this->warn("    ⚠ Failed to create print batch for event: {$event->name}");
                }
            } catch (\Exception $e) {
                $this->error("    Failed to create print batch for event {$event->name}: {$e->getMessage()}");
                $errors[] = [
                    'provider' => $provider->name,
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $batchCount;
    }
    
    /**
     * Display execution summary
     */
    private function displaySummary(
        bool $dryRun,
        Area $area,
        int $totalProviders,
        int $successCount,
        int $errorCount,
        int $totalApproved,
        int $totalPrintBatches,
        int $totalSkipped,
        array $providerStats,
        array $errors
    ): void {
        $this->newLine();
        $this->info(str_repeat('=', 80));
        $this->info(($dryRun ? 'DRY RUN ' : '') . 'EXECUTION SUMMARY');
        $this->info(str_repeat('=', 80));
        
        // Overview section
        $this->newLine();
        $this->comment('Overview');
        $overviewRows = [
            ['Mode', $dryRun ? 'DRY-RUN (no changes saved)' : 'EXECUTION'],
            ['Area', "{$area->name} (ID: {$area->id})"],
            ['Providers Processed', "{$successCount} / {$totalProviders}"],
            ['Providers with Errors', (string) $errorCount],
        ];
        $this->table(['Metric', 'Value'], $overviewRows);
        
        // Accreditation Requests section
        $this->newLine();
        $this->comment('Accreditation Requests');
        $requestRows = [
            ['Total Approved', (string) $totalApproved],
            ['Total Skipped', (string) $totalSkipped],
        ];
        $this->table(['Metric', 'Count'], $requestRows);
        
        // Print Batches section
        $this->newLine();
        $this->comment('Print Batches');
        $printRows = [
            ['Total Generated', (string) $totalPrintBatches],
        ];
        $this->table(['Metric', 'Count'], $printRows);
        
        // Provider Details (if not too many)
        if (!empty($providerStats) && count($providerStats) <= 20) {
            $this->newLine();
            $this->comment('Provider Details');
            $providerRows = [];
            foreach ($providerStats as $providerName => $stats) {
                $providerRows[] = [
                    $providerName,
                    (string) $stats['total'],
                    (string) $stats['approved'],
                    (string) $stats['skipped'],
                    (string) $stats['print_batches']
                ];
            }
            $this->table(['Provider', 'Total', 'Approved', 'Skipped', 'Print Batches'], $providerRows);
        }
        
        // Errors detail
        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors Encountered');
            foreach ($errors as $error) {
                $this->line("<fg=red>• {$error['provider']}: {$error['error']}</>");
            }
        }
        
        // Final status
        $this->newLine();
        if ($dryRun) {
            $this->warn('DRY RUN COMPLETE: Review the summary above. No changes were made.');
            $this->info('Run without --dry-run to execute the actual approval and batch generation.');
        } else {
            if ($errorCount === 0) {
                $this->info('✓ EXECUTION COMPLETE: All providers processed successfully.');
            } else {
                $this->warn('⚠ EXECUTION COMPLETE WITH ERRORS: Some providers encountered issues.');
            }
        }
    }
}
