<?php

namespace App\Console\Commands;

use App\Models\AccreditationRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportAccreditationRequestsCommand extends Command
{
    protected $signature = 'accreditation:export
        {--output= : Output file path (.xlsx or .csv). Defaults to storage/app/exports/accreditations_YYYY-mm-dd_HHMMSS.xlsx}
        {--event-id= : Optional event ID to filter}
        {--zones=ids : Zones output mode: ids|names}
        {--delimiter=; : CSV delimiter (only used for --output=*.csv). Default ; for Excel compatibility}';

    protected $description = 'Export accreditation requests ordered by area and provider into XLSX (default, embeds FOTO images) or CSV with header: ID_EVENT;TPDOC;CEDULA;NOMBRES;APELLIDOS;PROVEEDOR;FUNCION;ZONAS;FOTO';

    public function handle(): int
    {
        // Increase CLI memory limit to accommodate images when exporting many rows
        try { @ini_set('memory_limit', '512M'); } catch (\Throwable $e) {}

        $delimiter = (string) ($this->option('delimiter') ?: ';');
        if ($delimiter === '') { $delimiter = ';'; }
        $zonesMode = strtolower((string) ($this->option('zones') ?: 'ids'));
        if (!in_array($zonesMode, ['ids', 'names'], true)) {
            $this->warn("Invalid --zones option. Allowed: ids|names. Using 'ids'.");
            $zonesMode = 'ids';
        }

        // Resolve output path
        $output = $this->option('output');
        if (!is_string($output) || trim($output) === '') {
            $ts = now()->format('Y-m-d_His');
            $rel = "exports/accreditations_{$ts}.xlsx";
            // Ensure directory exists under storage/app/exports
            Storage::disk('local')->makeDirectory('exports');
            $fullPath = storage_path('app/'.$rel);
            $output = $fullPath;
        }
        $outDir = dirname((string) $output);
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0777, true);
        }

        // Prepare a cache directory for PhpSpreadsheet disk caching
        $cacheDir = storage_path('app/phpspreadsheet-cache');
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        // Decide format based on file extension
        $ext = strtolower(pathinfo((string) $output, PATHINFO_EXTENSION));
        $format = $ext === 'csv' ? 'csv' : 'xlsx';

        $this->info('Preparing query...');
        $builder = AccreditationRequest::query()
            ->select('accreditation_requests.*')
            ->leftJoin('employees', 'employees.id', '=', 'accreditation_requests.employee_id')
            ->leftJoin('providers', 'providers.id', '=', 'employees.provider_id')
            ->leftJoin('areas', 'areas.id', '=', 'providers.area_id')
            ->with([
                'employee.provider.area',
                'event',
                'zones' => function ($q) {
                    $q->select('zones.id', 'zones.name');
                },
            ])
            ->orderBy('areas.name')
            ->orderBy('providers.name')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->orderBy('accreditation_requests.id');

        // Optional filter by event
        $eventId = $this->option('event-id');
        if ($eventId !== null && $eventId !== '') {
            $builder->where('accreditation_requests.event_id', (int) $eventId);
        }

        if ($format === 'csv') {
            $this->info('Opening output file: ' . $output);
            $fh = @fopen($output, 'wb');
            if (!$fh) {
                $this->error('Cannot open output file for writing: ' . $output);
                return 1;
            }

            // Write UTF-8 BOM for Excel
            fwrite($fh, "\xEF\xBB\xBF");

            // Header
            $headers = ['ID_EVENT', 'TPDOC', 'CEDULA', 'NOMBRES', 'APELLIDOS', 'PROVEEDOR', 'FUNCION', 'ZONAS', 'FOTO'];
            fputcsv($fh, $headers, $delimiter);

            $count = 0;
            $this->info('Exporting (CSV)...');
            $builder->chunk(500, function ($chunk) use ($fh, $delimiter, $zonesMode, &$count) {
                foreach ($chunk as $req) {
                    /** @var \App\Models\AccreditationRequest $req */
                    $emp = $req->employee;
                    if (!$emp) { continue; }
                    $prov = $emp->provider;
                    $photoUrl = '';
                    if ($emp->photo_path) {
                        // Generate a public URL if possible (e.g., /storage/.. or absolute if ASSET_URL is set)
                        try {
                            $photoUrl = Storage::disk('public')->url($emp->photo_path);
                        } catch (\Throwable $e) {
                            // Fallback to relative path in case disk is not configured
                            $photoUrl = $emp->photo_path;
                        }
                    }

                    // Zones
                    $zonesStr = '';
                    if ($zonesMode === 'names') {
                        $zonesStr = $req->zones->pluck('name')->filter()->sort()->implode(',');
                    } else {
                        $zonesStr = $req->zones->pluck('id')->filter()->sort()->implode(',');
                    }

                    // Map fields
                    $row = [
                        (string) $req->event_id,                                 // ID_EVENT
                        strtoupper((string) ($emp->document_type ?: '')),        // TPDOC
                        (string) ($emp->document_number ?: ''),                  // CEDULA
                        (string) ($emp->first_name ?: ''),                       // NOMBRES
                        (string) ($emp->last_name ?: ''),                        // APELLIDOS
                        (string) ($prov->name ?? ''),                            // PROVEEDOR
                        (string) ($emp->function ?: ''),                         // FUNCION
                        (string) $zonesStr,                                      // ZONAS
                        (string) $photoUrl,                                      // FOTO
                    ];

                    fputcsv($fh, $row, $delimiter);
                    $count++;
                }
                // Free memory between chunks
                unset($chunk);
            });

            fclose($fh);
            $this->info("Export completed. Rows: {$count}");
            $this->line('File: ' . $output);
            return 0;
        }

        // XLSX export with embedded photos in FOTO column
        $this->info('Building XLSX workbook...');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Acreditaciones');

        $headers = ['ID_EVENT', 'TPDOC', 'CEDULA', 'NOMBRES', 'APELLIDOS', 'PROVEEDOR', 'FUNCION', 'ZONAS', 'FOTO'];
        $sheet->fromArray([$headers], null, 'A1');

        // Optional: make header bold and set some widths
        try {
            $sheet->getStyle('A1:I1')->getFont()->setBold(true);
            foreach (['A'=>10,'B'=>8,'C'=>18,'D'=>22,'E'=>22,'F'=>28,'G'=>22,'H'=>16,'I'=>14] as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }
            $sheet->getRowDimension(1)->setRowHeight(20);
        } catch (\Throwable $e) { /* styling is optional */ }

        $rowIndex = 2;
        $count = 0;
        $fotoColIndex = 9; // FOTO is the 9th column
        $fotoColLetter = Coordinate::stringFromColumnIndex($fotoColIndex);

        $this->info('Exporting (XLSX)...');
        $builder->chunk(300, function ($chunk) use ($sheet, &$rowIndex, $zonesMode, &$count, $fotoColLetter) {
            foreach ($chunk as $req) {
                /** @var \App\Models\AccreditationRequest $req */
                $emp = $req->employee;
                if (!$emp) { continue; }
                $prov = $emp->provider;

                // Zones
                if ($zonesMode === 'names') {
                    $zonesStr = $req->zones->pluck('name')->filter()->sort()->implode(',');
                } else {
                    $zonesStr = $req->zones->pluck('id')->filter()->sort()->implode(',');
                }

                // First 8 columns as values (A..H)
                $row = [
                    (string) $req->event_id,                                 // A: ID_EVENT
                    strtoupper((string) ($emp->document_type ?: '')),        // B: TPDOC
                    (string) ($emp->document_number ?: ''),                  // C: CEDULA
                    (string) ($emp->first_name ?: ''),                       // D: NOMBRES
                    (string) ($emp->last_name ?: ''),                        // E: APELLIDOS
                    (string) ($prov->name ?? ''),                            // F: PROVEEDOR
                    (string) ($emp->function ?: ''),                         // G: FUNCION
                    (string) $zonesStr,                                      // H: ZONAS
                ];
                $sheet->fromArray([$row], null, 'A' . (string) $rowIndex);

                // FOTO in column I as an embedded, downscaled image anchored to the cell
                if (!empty($emp->photo_path)) {
                    try {
                        $abs = Storage::disk('public')->path($emp->photo_path);
                        if (is_file($abs)) {
                            // Downscale and embed from temp file (disk-backed) to minimize memory footprint
                            $this->attachResizedPhoto($sheet, $abs, $fotoColLetter . (string) $rowIndex, 60);
                            // Adjust the row height to fit the display height
                            try { $sheet->getRowDimension($rowIndex)->setRowHeight(50); } catch (\Throwable $e) {}
                        }
                    } catch (\Throwable $e) {
                        // Ignore photo errors and continue
                    }
                }

                $rowIndex++;
                $count++;
            }
            unset($chunk);
        });

        // Save workbook
        $this->info('Writing XLSX file: ' . $output);
        try {
            // Free any internal caches before writing
            try { $spreadsheet->garbageCollect(); } catch (\Throwable $e) {}

            $writer = new Xlsx($spreadsheet);
            // Enable disk caching to reduce memory spikes when embedding many images
            try { $writer->setUseDiskCaching(true, $cacheDir); } catch (\Throwable $e) {}

            $writer->save($output);
        } catch (\Throwable $e) {
            $this->error('Failed to write XLSX: ' . $e->getMessage());
            return 1;
        }

        $this->info("Export completed. Rows: {$count}");
        $this->line('File: ' . $output);
        return 0;
    }

    /**
     * Downscale a photo and attach it to the sheet using MemoryDrawing to reduce memory consumption.
     *
     * @param Worksheet $sheet The worksheet to attach to
     * @param string $filePath Absolute path to the source image file
     * @param string $cellRef Target cell reference (e.g., 'I2')
     * @param int $displayHeight Target display height in points/pixels for the drawing
     */
    private function attachResizedPhoto(Worksheet $sheet, string $filePath, string $cellRef, int $displayHeight = 60): void
    {
        try {
            $contents = @file_get_contents($filePath);
            if ($contents === false) {
                return;
            }

            // Create GD image from string (supports JPEG/PNG/GIF)
            $src = @imagecreatefromstring($contents);
            if ($src === false) {
                return;
            }

            // Detect original MIME; if PNG/GIF prefer preserving transparency by embedding PNG
            $mime = null;
            $imgInfo = @getimagesize($filePath);
            if (is_array($imgInfo) && isset($imgInfo['mime'])) {
                $mime = $imgInfo['mime'];
            }
            $preferPng = ($mime === 'image/png' || $mime === 'image/gif');

            $srcW = (int) imagesx($src);
            $srcH = (int) imagesy($src);
            if ($srcW <= 0 || $srcH <= 0) {
                @imagedestroy($src);
                return;
            }

            // Fit within 240x320 preserving aspect ratio; do not upscale
            $maxW = 240;
            $maxH = 320;
            $scale = min($maxW / $srcW, $maxH / $srcH, 1.0);
            $dstW = max(1, (int) floor($srcW * $scale));
            $dstH = max(1, (int) floor($srcH * $scale));

            $dst = imagecreatetruecolor($dstW, $dstH);
            if (!$dst) {
                @imagedestroy($src);
                return;
            }

            if ($preferPng) {
                // Prepare fully transparent canvas
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
            } else {
                // White background to avoid black when flattening to JPEG
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
                imagealphablending($dst, true);
            }

            // High-quality resample
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
            @imagedestroy($src);

            // Write to temp file to avoid keeping GD resources in memory via MemoryDrawing
            $cacheDir = storage_path('app/phpspreadsheet-cache');
            if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
            $ext = $preferPng ? 'png' : 'jpg';
            $tmpPath = tempnam($cacheDir, 'img_');
            if ($tmpPath === false) {
                @imagedestroy($dst);
                return;
            }
            $finalPath = $tmpPath . '.' . $ext;
            @unlink($tmpPath);
            if ($preferPng) {
                imagepng($dst, $finalPath, 6);
            } else {
                imagejpeg($dst, $finalPath, 85);
            }
            @imagedestroy($dst);

            // Ensure temp file removed at the end
            register_shutdown_function(function() use ($finalPath) { @unlink($finalPath); });

            $drawing = new Drawing();
            $drawing->setPath($finalPath);
            $drawing->setCoordinates($cellRef);
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setHeight($displayHeight);
            $drawing->setWorksheet($sheet);
        } catch (\Throwable $e) {
            // Ignore image errors to keep export resilient
        }
    }
}
