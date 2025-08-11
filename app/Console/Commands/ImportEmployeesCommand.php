<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Provider;
use App\Models\Event;
use App\Models\User;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class ImportEmployeesCommand extends Command
{
    protected $signature = 'employees:import
        {file : Path to .xlsx file}
        {--sheet= : Worksheet name/index, comma-separated, or * / all}
        {--dry-run : Validate only, do not persist}
        {--default-doc=V : Default document type if not provided (V/E/P)}
        {--create-requests : Also create draft accreditation requests for each employee}
        {--event-id= : Event ID to link accreditation requests}
        {--zones-col=ZONAS : Header name for a single zones column (comma/semicolon-separated IDs)}
        {--zones-cols= : Comma-separated header names for multiple zones columns}
        {--created-by= : Creator user (ID or email) for the requests}
        {--truncate : Truncate employees and accreditation-related tables before import}
        {--errors-log= : Write only errors to this file. If it ends with .csv: CSV columns [File,Sheet,Row,Field,Error]. If it ends with .log: plain text lines}';

    protected $description = 'Import employees from an XLSX with embedded photos anchored in the FOTO column';

    public function handle(): int
    {
        $path = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        $sheetOpt = $this->option('sheet');
        $defaultDoc = strtoupper((string) ($this->option('default-doc') ?: 'V'));
        if (!in_array($defaultDoc, ['V', 'E', 'P'], true)) {
            $this->error('Invalid --default-doc. Allowed: V, E, P');
            return 1;
        }

        // Be generous on CLI to handle images in spreadsheets
        @ini_set('memory_limit', env('IMPORT_MEMORY_LIMIT', '512M'));
        if (function_exists('set_time_limit')) { @set_time_limit(0); }

        // Optional accreditation request creation settings
        $createRequests = (bool) $this->option('create-requests');
        $eventIdOpt = $this->option('event-id');
        $zonesHeaderOpt = $this->option('zones-col');
        $creatorOpt = $this->option('created-by');
        $zonesHeadersOpt = $this->option('zones-cols');
        $truncate = (bool) $this->option('truncate');

        $reqService = null; $event = null; $allowedZoneIds = []; $creatorUser = null;
        $errorsLogPath = $this->option('errors-log');
        if ($createRequests) {
            if (!$eventIdOpt) {
                $this->error('When using --create-requests you must provide --event-id=<id>.');
                return 1;
            }
            $event = Event::with('zones')->find((int) $eventIdOpt);
            if (!$event) {
                $this->error("Event not found: {$eventIdOpt}");
                return 1;
            }
            if ($creatorOpt) {
                // Resolve global fallback creator user (by id or email)
                if (is_numeric($creatorOpt)) {
                    $creatorUser = User::find((int) $creatorOpt);
                } else {
                    $creatorUser = User::where('email', (string) $creatorOpt)->first();
                }
                if (!$creatorUser) {
                    $this->error("Creator user not found: {$creatorOpt}");
                    return 1;
                }
                // Set a default authenticated user (can be overridden per row)
                Auth::setUser($creatorUser);
            } else {
                $this->warn('No --created-by provided. Will resolve creator per fila usando el usuario del proveedor (si existe).');
            }
            $allowedZoneIds = $event->zones->pluck('id')->map(fn ($v) => (int) $v)->all();
            $reqService = app(AccreditationRequestServiceInterface::class);
            $this->info("Requests creation enabled. Event: {$event->id} ({$event->name})" . ($creatorUser ? ", Default creator: {$creatorUser->email}" : ''));
            $this->line('Allowed zone IDs for event: ' . implode(',', $allowedZoneIds));
        }

        // Optional: truncate tables before import
        if ($truncate) {
            $this->warn('Truncating employees and accreditation tables before import...');
            try {
                $this->truncateImportTables();
                $this->info('Truncation completed.');
            } catch (\Throwable $te) {
                $this->error('Failed to truncate tables: ' . $te->getMessage());
                return 1;
            }
        }

        if (!is_string($path) || !file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $fileBase = basename((string) $path);
        $this->info('Loading spreadsheet...');
        $reader = IOFactory::createReader('Xlsx');
        $reader->setIncludeCharts(false);
        // Read full data to retain drawings
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($path);

        // Resolve worksheets to process
        $sheets = [];
        if ($sheetOpt) {
            $opt = trim((string) $sheetOpt);
            if ($opt === '*' || strcasecmp($opt, 'all') === 0) {
                for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
                    $sheets[] = $spreadsheet->getSheet($i);
                }
            } else {
                $parts = array_map('trim', explode(',', $opt));
                foreach ($parts as $part) {
                    if ($part === '') { continue; }
                    if (is_numeric($part)) {
                        $index = max(0, ((int) $part) - 1);
                        if ($index < $spreadsheet->getSheetCount()) {
                            $sheets[] = $spreadsheet->getSheet($index);
                        } else {
                            $this->error("Worksheet index out of range: {$part}");
                            return 1;
                        }
                    } else {
                        $sh = $spreadsheet->getSheetByName($part);
                        if (!$sh) {
                            $this->error("Worksheet not found: {$part}");
                            return 1;
                        }
                        $sheets[] = $sh;
                    }
                }
            }
        } else {
            $sheets[] = $spreadsheet->getActiveSheet();
        }

        // Track sheet processing details
        $processedSheets = 0;
        $processedSheetTitles = [];
        $skippedSheetTitles = [];

        // Aggregated counters across all processed sheets
        $created = 0; $updated = 0; $skipped = 0; $photoSaved = 0;
        $reqCreated = 0; $reqSkipped = 0; $reqWithZones = 0; $reqWithoutZones = 0;
        // Detailed skip reasons
        $skippedEmpty = 0; $skippedInvalidDoc = 0; $skippedMissingFields = 0; $skippedProviderMissing = 0; $skippedProviderInactive = 0; $skippedExisting = 0; $skippedDuplicate = 0;
        // Track duplicates within the same file (by doc type + number) across all sheets
        $seenDocs = [];
        $errors = [];

        foreach ($sheets as $sheet) {
            $headerRow = 1;
            $sheetTitle = $sheet->getTitle();
            // Use overall highest column for compatibility across PhpSpreadsheet versions
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            $headers = [];
            for ($col = 1; $col <= $highestCol; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col) . (string) $headerRow;
                $raw = (string) $sheet->getCell($coord)->getCalculatedValue();
                $norm = $this->normalizeHeader($raw);
                if ($norm) { $headers[$norm] = $col; }
            }

            $colMap = $this->resolveColumnMap($headers);
            $required = ['PROVEEDOR', 'CEDULA', 'NOMBRES', 'APELLIDOS'];
            $missing = array_values(array_filter($required, fn ($k) => !isset($colMap[$k])));
            if (!empty($missing)) {
                $this->warn("Sheet '" . $sheet->getTitle() . "': Missing required headers: " . implode(', ', $missing));
                $this->line('Found headers: ' . implode(', ', array_keys($headers)));
                $skippedSheetTitles[] = $sheet->getTitle();
                continue; // Skip this sheet
            }
            $processedSheets++;
            $processedSheetTitles[] = $sheet->getTitle();
            $fotoCol = $colMap['FOTO'] ?? null; // if null we will fallback to F (6)

            // Determine zones column indexes if requests creation is enabled
            $zonesCols = [];
            if ($createRequests) {
                if ($zonesHeadersOpt) {
                    // Explicit multiple headers via --zones-cols
                    $parts = array_filter(array_map('trim', explode(',', (string) $zonesHeadersOpt)), fn($v) => $v !== '');
                    foreach ($parts as $zh) {
                        $normZh = $this->normalizeHeader($zh);
                        if ($normZh && isset($headers[$normZh])) {
                            $zonesCols[] = $headers[$normZh];
                        } else {
                            $this->warn("Sheet '" . $sheet->getTitle() . "': Zones header not found: {$zh}");
                        }
                    }
                } elseif ($zonesHeaderOpt) {
                    // Single header via --zones-col
                    $normZones = $this->normalizeHeader((string) $zonesHeaderOpt);
                    if ($normZones && isset($headers[$normZones])) {
                        $zonesCols[] = $headers[$normZones];
                    } else {
                        $this->warn("Sheet '" . $sheet->getTitle() . "': Zones header not found: {$zonesHeaderOpt}. Requests will be created without zones.");
                    }
                } else {
                    // Fallback to alias map 'ZONAS'
                    if (isset($colMap['ZONAS'])) { $zonesCols[] = $colMap['ZONAS']; }
                    // Autodetect other zone columns by header names containing ZONA/ZONAS
                    foreach ($headers as $hKey => $idx) {
                        if (strpos($hKey, 'ZONA') === 0 || strpos($hKey, 'ZONAS') === 0) {
                            if (!in_array($idx, $zonesCols, true)) { $zonesCols[] = $idx; }
                        }
                    }
                }
                // Ensure unique
                $zonesCols = array_values(array_unique($zonesCols));
            }

            // Build image map: row => [contents, ext]
            $this->line("Indexing embedded images for sheet '" . $sheet->getTitle() . "'...");
            $imagesByRow = $this->buildImagesMap($sheet, $fotoCol);

            $highestRow = max($sheet->getHighestDataRow(), $headerRow + 1);

            $this->info("Processing rows for sheet '" . $sheet->getTitle() . "'...");
            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $providerName = $this->readCell($sheet, $colMap['PROVEEDOR'] ?? null, $row);
            $firstName = $this->readCell($sheet, $colMap['NOMBRES'] ?? null, $row);
            $lastName  = $this->readCell($sheet, $colMap['APELLIDOS'] ?? null, $row);
            $docCell   = $this->readCell($sheet, $colMap['CEDULA'] ?? null, $row);
            $funcCell  = $this->readCell($sheet, $colMap['FUNCION'] ?? null, $row) ?? '';
            $tipoCell  = $this->readCell($sheet, $colMap['TIPO_DOCUMENTO'] ?? null, $row);
            // Aggregate zone IDs from all configured/detected zone columns
            $rowZoneIdsRaw = [];
            if ($createRequests && !empty($zonesCols)) {
                foreach ($zonesCols as $zc) {
                    $val = $this->readCell($sheet, $zc, $row);
                    if ($val !== null) {
                        $rowZoneIdsRaw = array_merge($rowZoneIdsRaw, $this->parseZones($val));
                    }
                }
                $rowZoneIdsRaw = array_values(array_unique($rowZoneIdsRaw));
            }

            // Skip empty rows (no provider and no cedula and no names)
            if ($providerName === null && $docCell === null && $firstName === null && $lastName === null) {
                $skippedEmpty++; $skipped++;
                continue;
            }

            // Parse document type/number
            [$docType, $docNumber, $docErr] = $this->parseDocumento($docCell, $tipoCell, $defaultDoc);
            if ($docErr) {
                $fieldForDoc = (strpos((string) $docErr, 'Tipo') !== false) ? 'TIPO_DOCUMENTO' : 'CEDULA';
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => $fieldForDoc,
                    'error' => $docErr,
                ];
                $skippedInvalidDoc++; $skipped++;
                continue;
            }

            // Provider lookup
            $provider = $this->findProviderByName($providerName);
            if (!$provider) {
                $msg = $providerName ? ("Proveedor no encontrado: {$providerName}") : 'Proveedor vacío';
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => 'PROVEEDOR',
                    'error' => $msg,
                ];
                $skippedProviderMissing++; $skipped++;
                continue;
            }

            // Provider must be active (treat null/0/false as inactive)
            if (!$provider->active) {
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => 'PROVEEDOR',
                    'error' => "Proveedor inactivo: {$provider->name}",
                ];
                $skippedProviderInactive++; $skipped++;
                continue;
            }

            // Basic validations
            if (!$firstName || !$lastName) {
                $missingFieldsRow = [];
                if (!$firstName) { $missingFieldsRow[] = 'NOMBRES'; }
                if (!$lastName)  { $missingFieldsRow[] = 'APELLIDOS'; }
                $msg = 'Campos vacíos: ' . implode(', ', $missingFieldsRow);
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => implode(', ', $missingFieldsRow),
                    'error' => $msg,
                ];
                $skippedMissingFields++; $skipped++;
                continue;
            }

            // Duplicated in file check
            $docKey = $docType . '-' . $docNumber;
            if (isset($seenDocs[$docKey])) {
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => 'CEDULA',
                    'error' => 'Fila duplicada en archivo para documento: ' . $docKey,
                ];
                $skippedDuplicate++; $skipped++;
                continue;
            }
            $seenDocs[$docKey] = true;

            // Existing employee check (skip updates)
            $alreadyExists = Employee::where('document_type', $docType)
                ->where('document_number', $docNumber)
                ->exists();
            if ($alreadyExists) {
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => 'CEDULA',
                    'error' => 'Empleado ya existe en el sistema',
                ];
                $skippedExisting++; $skipped++;
                continue;
            }

            if ($dryRun) {
                // Would create a new employee (no updates allowed)
                $created++;
                if (isset($imagesByRow[$row])) { $photoSaved++; }
                if ($createRequests) { $reqCreated++; }
                continue;
            }

            try {
                DB::transaction(function () use (
                    $provider,
                    $firstName,
                    $lastName,
                    $funcCell,
                    $docType,
                    $docNumber,
                    &$imagesByRow,
                    $row,
                    &$created,
                    &$photoSaved,
                    $createRequests,
                    $reqService,
                    $event,
                    $rowZoneIdsRaw,
                    $allowedZoneIds,
                    &$reqCreated,
                    &$reqSkipped,
                    &$errors,
                    &$reqWithZones,
                    &$reqWithoutZones,
                    $creatorUser,
                    $fileBase,
                    $sheetTitle
                ) {
                    $employee = Employee::create([
                        'provider_id' => $provider->id,
                        'document_type' => $docType,
                        'document_number' => $docNumber,
                        'first_name' => trim((string) $firstName),
                        'last_name' => trim((string) $lastName),
                        'function' => Str::limit(trim((string) $funcCell), 100, ''),
                        'active' => true,
                    ]);

                    $created++;

                    if (isset($imagesByRow[$row])) {
                        $imgMeta = $imagesByRow[$row];
                        unset($imagesByRow[$row]); // free metadata as soon as possible
                        $pair = $this->extractImageBinary($imgMeta);
                        if ($pair) {
                            [$binary, $ext] = $pair;
                            $relPath = $this->storeEmployeePhoto($employee, $binary, $ext);
                            if ($relPath) {
                                $employee->photo_path = $relPath;
                                $employee->save();
                                $photoSaved++;
                            } else {
                                $errors[] = [
                                    'file' => $fileBase,
                                    'sheet' => $sheetTitle,
                                    'row' => $row,
                                    'field' => 'FOTO',
                                    'error' => 'Foto: fallo al guardar en disco',
                                ];
                            }
                        }
                    }

                    // Create accreditation request if enabled
                    if ($createRequests && $reqService && $event) {
                        // Determine acting user: provider->user_id, fallback to global --created-by
                        $actingUser = null;
                        if ($provider && $provider->user_id) {
                            $actingUser = User::find((int) $provider->user_id);
                        }
                        if (!$actingUser && isset($creatorUser)) {
                            $actingUser = $creatorUser;
                        }

                        if (!$actingUser) {
                            $reqSkipped++;
                            $errors[] = [
                                'file' => $fileBase,
                                'sheet' => $sheetTitle,
                                'row' => $row,
                                'field' => 'SOLICITUD',
                                'error' => 'Solicitud: no se encontró usuario creador (proveedor sin user y sin --created-by)'
                            ];
                        } else {
                            Auth::setUser($actingUser);
                            $zoneIds = !empty($allowedZoneIds)
                                ? array_values(array_intersect($rowZoneIdsRaw, $allowedZoneIds))
                                : $rowZoneIdsRaw;
                            if (!empty($zoneIds)) { $reqWithZones++; } else { $reqWithoutZones++; }
                            try {
                                $reqService->createRequest([
                                    'employee_id' => $employee->id,
                                    'event_id' => (int) $event->id,
                                    'zones' => $zoneIds,
                                    'comments' => 'Creada automáticamente desde importación de empleados',
                                ]);
                                $reqCreated++;
                            } catch (\Throwable $re) {
                                $reqSkipped++;
                                $errors[] = [
                                    'file' => $fileBase,
                                    'sheet' => $sheetTitle,
                                    'row' => $row,
                                    'field' => 'SOLICITUD',
                                    'error' => 'Solicitud: ' . $re->getMessage(),
                                ];
                            }
                        }
                    }
                });
            } catch (\Throwable $e) {
                $errors[] = [
                    'file' => $fileBase,
                    'sheet' => $sheetTitle,
                    'row' => $row,
                    'field' => '-',
                    'error' => $e->getMessage(),
                ];
                $skipped++;
            }
        }
        // Free per-sheet memory to avoid OOM on large books
        unset($imagesByRow, $headers, $colMap, $zonesCols);
        if (method_exists($sheet, 'garbageCollect')) { $sheet->garbageCollect(); }
        if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }
        }

        $this->line('');
        $this->info('Import summary');

        // Derived metrics
        $totalProcessed = $created + $updated + $skipped;
        $missingPhotos = max(0, $created - $photoSaved);
        $repeatedOrExisting = $skippedExisting + $skippedDuplicate;

        // Overview section
        $this->line('');
        $this->comment('Overview');
        $overviewRows = [
            ['File', basename((string) $path)],
            ['Mode', $dryRun ? 'DRY-RUN (no changes saved)' : 'EXECUTION'],
            ['Sheets processed', $processedSheets . (empty($processedSheetTitles) ? '' : ' (' . implode(', ', $processedSheetTitles) . ')')],
            ['Sheets skipped', count($skippedSheetTitles) . (empty($skippedSheetTitles) ? '' : ' (' . implode(', ', $skippedSheetTitles) . ')')],
            ['Records processed', (string) $totalProcessed],
        ];
        $this->table(['Metric', 'Value'], $overviewRows);

        // Employees section
        $this->line('');
        $this->comment('Employees');
        $empRows = [
            ['Created', (string) $created],
            ['Updated', (string) $updated],
            ['Skipped', (string) $skipped],
        ];
        $this->table(['Metric', 'Count'], $empRows);

        // Photos section
        $this->line('');
        $this->comment('Photos');
        $photoRows = [
            ['Saved', (string) $photoSaved],
            ['Missing', (string) $missingPhotos],
        ];
        $this->table(['Metric', 'Count'], $photoRows);

        // Requests section (optional)
        if ($createRequests) {
            $this->line('');
            $this->comment('Accreditation Requests');
            $reqRows = [
                ['Created', (string) $reqCreated],
                ['Skipped', (string) $reqSkipped],
                ['With zones', (string) $reqWithZones],
                ['Without zones', (string) $reqWithoutZones],
            ];
            $this->table(['Metric', 'Count'], $reqRows);
        }

        // Skipped breakdown
        $this->line('');
        $this->comment('Skipped breakdown');
        $skippedRows = [
            ['Existing in system', (string) $skippedExisting],
            ['Duplicate in file', (string) $skippedDuplicate],
            ['Provider missing', (string) $skippedProviderMissing],
            ['Provider inactive', (string) $skippedProviderInactive],
            ['Missing required fields', (string) $skippedMissingFields],
            ['Invalid document', (string) $skippedInvalidDoc],
            ['Empty row', (string) $skippedEmpty],
        ];
        $this->table(['Reason', 'Count'], $skippedRows);

        // Final tally
        $this->line('');
        $this->comment('Final tally');
        $finalRows = [
            ['Imported (created)', (string) $created],
            ['Failed (skipped)', (string) $skipped],
            ['Repeated/Existing', (string) $repeatedOrExisting],
        ];
        $this->table(['Outcome', 'Count'], $finalRows);

        // Errors detail
        if (!empty($errors)) {
            $this->line('');
            $this->error('Errors');
            $rows = array_map(function ($e) use ($fileBase) {
                return [
                    $e['file'] ?? $fileBase,
                    $e['sheet'] ?? '',
                    (string) ($e['row'] ?? ''),
                    $e['field'] ?? '',
                    '<fg=red>' . ($e['error'] ?? '') . '</>',
                ];
            }, $errors);
            $this->table(['File', 'Sheet', 'Row', 'Field', 'Error'], $rows);
        }

        // Optional: write only errors to CSV if --errors-log provided
        if ($errorsLogPath) {
            try {
                $dir = dirname($errorsLogPath);
                if ($dir && $dir !== '.' && !is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                $ext = strtolower(pathinfo($errorsLogPath, PATHINFO_EXTENSION));
                if ($ext === 'csv') {
                    $fh = fopen($errorsLogPath, 'w');
                    if ($fh === false) {
                        $this->error("No se pudo abrir el archivo para escribir: {$errorsLogPath}");
                    } else {
                        // Header
                        fputcsv($fh, ['File', 'Sheet', 'Row', 'Field', 'Error']);
                        foreach ($errors as $e) {
                            fputcsv($fh, [
                                $e['file'] ?? $fileBase,
                                $e['sheet'] ?? '',
                                (string) ($e['row'] ?? ''),
                                $e['field'] ?? '',
                                $e['error'] ?? '',
                            ]);
                        }
                        fclose($fh);
                        $this->info("Errores exportados a: {$errorsLogPath}");
                    }
                } else {
                    // Plain text .log (or any other) format: one line per error
                    $fh = fopen($errorsLogPath, 'w');
                    if ($fh === false) {
                        $this->error("No se pudo abrir el archivo para escribir: {$errorsLogPath}");
                    } else {
                        fwrite($fh, "# Employee Import Errors\n");
                        foreach ($errors as $e) {
                            $line = sprintf(
                                "file=%s sheet=%s row=%s field=%s error=%s\n",
                                (string) ($e['file'] ?? $fileBase),
                                (string) ($e['sheet'] ?? ''),
                                (string) ($e['row'] ?? ''),
                                (string) ($e['field'] ?? ''),
                                str_replace(["\r", "\n"], ' ', (string) ($e['error'] ?? ''))
                            );
                            fwrite($fh, $line);
                        }
                        fclose($fh);
                        $this->info("Errores exportados a: {$errorsLogPath}");
                    }
                }
            } catch (\Throwable $wre) {
                $this->error('Fallo al escribir el log de errores: ' . $wre->getMessage());
            }
        }

        // Final cleanup of spreadsheet to release memory
        if (isset($spreadsheet)) {
            if (method_exists($spreadsheet, 'disconnectWorksheets')) { $spreadsheet->disconnectWorksheets(); }
            unset($spreadsheet);
        }
        if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }
        return 0;
    }

    private function readCell($sheet, ?int $colIndex, int $row): ?string
    {
        if (!$colIndex) { return null; }
        $coord = Coordinate::stringFromColumnIndex($colIndex) . (string) $row;
        $cell = $sheet->getCell($coord);
        if ($cell === null) { return null; }
        $v = $cell->getCalculatedValue();
        if ($v === null) { return null; }
        $v = is_string($v) ? $v : (string) $v;
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function resolveColumnMap(array $headers): array
    {
        $map = [];
        $aliases = [
            'PROVEEDOR' => ['PROVEEDOR', 'PROVEEDOR AL QUE PRESTA SERVICIOS', 'PROVEEDORALQUEPRESTASERVICIOS'],
            'TIPO_DOCUMENTO' => ['TIPO_DOCUMENTO', 'TIPO DE DOCUMENTO', 'TIPO DOCUMENTO', 'TIPO'],
            'CEDULA' => ['CEDULA', 'CÉDULA'],
            'NOMBRES' => ['NOMBRES', 'NOMBRE'],
            'APELLIDOS' => ['APELLIDOS', 'APELLIDO'],
            'FUNCION' => ['FUNCION', 'FUNCIÓN', 'CARGO EN SU ORGANIZACION', 'CARGO EN SU ORGANIZACIÓN', 'CARGO'],
            'FOTO' => ['FOTO', 'FOTOGRAFIA', 'FOTOGRAFÍA'],
            'ZONAS' => ['ZONAS', 'ZONAS AUTORIZADAS', 'ZONAS SOLICITADAS']
        ];
        foreach ($aliases as $key => $syns) {
            foreach ($syns as $s) {
                $norm = $this->normalizeHeader($s);
                if (isset($headers[$norm])) { $map[$key] = $headers[$norm]; break; }
            }
        }
        return $map;
    }

    private function normalizeHeader(?string $text): ?string
    {
        if ($text === null) { return null; }
        $t = trim($text);
        if ($t === '') { return null; }
        $t = mb_strtoupper($t, 'UTF-8');
        // Remove accents
        $t = strtr($t, [
            'Á' => 'A','É' => 'E','Í' => 'I','Ó' => 'O','Ú' => 'U','Ü' => 'U','Ñ' => 'N',
            'Â' => 'A','Ê' => 'E','Î' => 'I','Ô' => 'O','Û' => 'U',
        ]);
        // Collapse spaces
        $t = preg_replace('/\s+/', ' ', $t);
        return $t;
    }

    private function parseDocumento(?string $cedulaCell, ?string $tipoCell, string $defaultDoc): array
    {
        $raw = $cedulaCell ? trim($cedulaCell) : '';
        $tipo = $tipoCell ? mb_strtoupper(trim($tipoCell)) : null;

        // Detect type from raw if provided like "V-123456", "E 123", "P AB123"
        if (preg_match('/^\s*([VEP])\s*[-\s\.]?\s*(.+)\s*$/i', (string) $raw, $m)) {
            $detectedTipo = strtoupper($m[1]);
            $payload = trim($m[2]);
            if (!$tipo) { $tipo = $detectedTipo; }
            $raw = $payload;
        }

        $tipoFinal = $tipo ?: $defaultDoc;
        if (!in_array($tipoFinal, ['V','E','P'], true)) {
            return [null, null, 'Tipo de documento inválido'];
        }

        if ($tipoFinal === 'P') {
            // Passport: allow alphanumeric, remove spaces/punct, uppercase
            $num = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));
            if ($num === '') { return [null, null, 'Número de pasaporte vacío']; }
            if (strlen($num) < 5 || strlen($num) > 20) { return [null, null, 'Número de pasaporte inválido (longitud 5-20)']; }
            return [$tipoFinal, $num, null];
        }

        // V/E: numeric only, 3-20 digits
        $num = preg_replace('/\D+/', '', (string) $raw);
        if ($num === '') { return [null, null, 'Número de documento vacío']; }
        if (!preg_match('/^\d{3,20}$/', (string) $num)) { return [null, null, 'Número de documento inválido (solo dígitos, 3-20)']; }
        return [$tipoFinal, $num, null];
    }

    private function normalizeProviderName(?string $text): ?string
    {
        if ($text === null) { return null; }
        $t = trim($text);
        if ($t === '') { return null; }
        // Uppercase first to simplify mappings
        $t = mb_strtoupper($t, 'UTF-8');
        // Remove accents (both cases covered by uppercasing, but include extras for safety)
        $t = strtr($t, [
            'Á' => 'A','É' => 'E','Í' => 'I','Ó' => 'O','Ú' => 'U','Ü' => 'U','Ñ' => 'N',
            'Â' => 'A','Ê' => 'E','Î' => 'I','Ô' => 'O','Û' => 'U',
            'À' => 'A','È' => 'E','Ì' => 'I','Ò' => 'O','Ù' => 'U',
            'Ã' => 'A','Õ' => 'O','Ç' => 'C',
            'á' => 'A','é' => 'E','í' => 'I','ó' => 'O','ú' => 'U','ü' => 'U','ñ' => 'N',
            'â' => 'A','ê' => 'E','î' => 'I','ô' => 'O','û' => 'U',
            'à' => 'A','è' => 'E','ì' => 'I','ò' => 'O','ù' => 'U',
            'ã' => 'A','õ' => 'O','ç' => 'C',
        ]);
        // Replace any non-alphanumeric with a single space
        $t = preg_replace('/[^A-Z0-9]+/u', ' ', (string) $t);
        // Collapse spaces
        $t = preg_replace('/\s+/', ' ', (string) $t);
        $t = trim((string) $t);
        return $t === '' ? null : $t;
    }

    private function findProviderByName(?string $name): ?Provider
    {
        if (!$name) { return null; }
        $normQ = $this->normalizeProviderName($name);
        if (!$normQ) { return null; }

        // Cache providers in-memory for fast normalized comparisons during this run
        static $providers = null;
        static $normIndex = null; // normName => Provider (first occurrence)

        if ($providers === null) {
            $providers = Provider::select('id', 'name', 'active', 'user_id')->get();
            $normIndex = [];
            foreach ($providers as $prov) {
                $nk = $this->normalizeProviderName((string) $prov->name);
                if ($nk && !isset($normIndex[$nk])) {
                    $normIndex[$nk] = $prov;
                }
            }
        }

        // 1) Exact normalized match
        if (isset($normIndex[$normQ])) {
            return $normIndex[$normQ];
        }

        // 2) Match ignoring spaces completely
        $qNoSpace = str_replace(' ', '', $normQ);
        foreach ($normIndex as $k => $prov) {
            if (str_replace(' ', '', $k) === $qNoSpace) {
                return $prov;
            }
        }

        // 3) Unique containment (handles extra words like "CANAL RCN COLOMBIA")
        $candidates = [];
        foreach ($normIndex as $k => $prov) {
            if (\Illuminate\Support\Str::contains($k, $normQ) || \Illuminate\Support\Str::contains($normQ, $k)) {
                $candidates[] = $prov;
            }
        }
        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // 4) DB fallback (case-insensitive contains). ILIKE for PostgreSQL, LIKE as fallback.
        try {
            $p = Provider::where('name', 'ILIKE', "%{$name}%")
                ->orWhere('name', 'ILIKE', (string) $name)
                ->first();
            if ($p) { return $p; }
        } catch (\Throwable $e) {
            // ILIKE not supported (e.g., MySQL). Fallback to LIKE.
            $p = Provider::where('name', 'LIKE', "%{$name}%")
                ->orWhere('name', 'LIKE', (string) $name)
                ->first();
            if ($p) { return $p; }
        }

        return null;
    }

    private function buildImagesMap($sheet, ?int $fotoCol): array
    {
        $map = [];
        $expectedCol = $fotoCol ?? 6; // default to F (1-based index 6)
        foreach ($sheet->getDrawingCollection() as $drawing) {
            $coord = $drawing->getCoordinates(); // e.g., F7
            [$colStr, $row] = Coordinate::coordinateFromString($coord);
            $colIndex = Coordinate::columnIndexFromString($colStr);
            if ($colIndex !== $expectedCol) { continue; }

            $meta = null;
            if ($drawing instanceof MemoryDrawing) {
                // Store only metadata to extract later
                $render = $drawing->getRenderingFunction();
                $meta = [
                    'type' => 'memory',
                    'drawing' => $drawing,
                    'render' => $render,
                ];
            } elseif ($drawing instanceof Drawing) {
                $path = $drawing->getPath();
                $ext = $drawing->getExtension() ?: '';
                if (!$ext && is_string($path) && preg_match('/\.([a-zA-Z0-9]+)$/', $path, $m)) {
                    $ext = $m[1];
                }
                $ext = strtolower($ext ?: 'png');
                $meta = [
                    'type' => 'file',
                    'path' => $path,
                    'ext'  => $ext,
                ];
            }

            if ($meta && !isset($map[$row])) {
                // Only keep first image for a given row
                $map[$row] = $meta;
            }
        }
        return $map;
    }

    private function extractImageBinary(array $meta): ?array
    {
        if (!isset($meta['type'])) { return null; }
        if ($meta['type'] === 'memory') {
            /** @var MemoryDrawing $d */
            $d = $meta['drawing'] ?? null;
            if (!$d) { return null; }
            $gd = $d->getImageResource();
            $render = $meta['render'] ?? MemoryDrawing::RENDERING_PNG;
            $ext = 'png';
            ob_start();
            switch ($render) {
                case MemoryDrawing::RENDERING_JPEG:
                    $ext = 'jpg';
                    imagejpeg($gd);
                    break;
                case MemoryDrawing::RENDERING_GIF:
                    $ext = 'gif';
                    imagegif($gd);
                    break;
                case MemoryDrawing::RENDERING_PNG:
                default:
                    $ext = 'png';
                    imagepng($gd);
                    break;
            }
            $binary = ob_get_contents();
            ob_end_clean();
            return $binary ? [$binary, $ext] : null;
        }
        if ($meta['type'] === 'file') {
            $path = $meta['path'] ?? null;
            if (!$path) { return null; }
            $ext = $meta['ext'] ?? 'png';
            $binary = @file_get_contents($path);
            return $binary ? [$binary, $ext] : null;
        }
        return null;
    }

    private function storeEmployeePhoto(Employee $employee, string $binary, string $ext): ?string
    {
        $ext = in_array(strtolower($ext), ['jpg','jpeg','png','webp'], true) ? strtolower($ext) : 'png';
        $hash = sha1($binary);
        $rel = sprintf('employees/%d/%s/%s.%s', $employee->provider_id, $employee->uuid, $hash, $ext);
        try {
            $disk = Storage::disk('public');
            $dir = dirname($rel);
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }
            $ok = $disk->put($rel, $binary);
            if (!$ok) {
                Log::error('ImportEmployeesCommand: failed to write employee photo', [
                    'employee_id' => $employee->id,
                    'provider_id' => $employee->provider_id,
                    'path' => $rel,
                ]);
                return null;
            }
            return $rel;
        } catch (\Throwable $e) {
            Log::error('ImportEmployeesCommand: exception writing employee photo', [
                'employee_id' => $employee->id,
                'provider_id' => $employee->provider_id,
                'path' => $rel,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function truncateImportTables(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            // Postgres can truncate with cascade and reset identities in one statement
            DB::statement('TRUNCATE TABLE credentials, accreditation_request_zone, accreditation_requests, employees RESTART IDENTITY CASCADE');
            return;
        }

        // Fallback for other drivers
        Schema::disableForeignKeyConstraints();
        try {
            $tables = ['credentials', 'accreditation_request_zone', 'accreditation_requests', 'employees'];
            foreach ($tables as $t) {
                if (Schema::hasTable($t)) {
                    DB::table($t)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function parseZones(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') { return []; }
        $clean = str_replace(["\t", ' '], '', $raw);
        // Accept semicolon, comma, and dot as separators (e.g., "1;2,3.4")
        $parts = preg_split('/[;,.]+/', $clean) ?: [];
        $ids = [];
        foreach ($parts as $p) {
            if ($p === '') { continue; }
            if (preg_match('/^\d+$/', $p)) {
                $ids[] = (int) $p;
            }
        }
        return array_values(array_unique($ids));
    }
}
