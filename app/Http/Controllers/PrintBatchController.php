<?php

namespace App\Http\Controllers;


use App\Http\Requests\PrintBatch\CreateBatchRequest;
use App\Models\PrintBatch;
use App\Services\PrintBatch\PrintBatchServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrintBatchController extends BaseController
{
    public function __construct(
        private PrintBatchServiceInterface $printBatchService
    ) {
        $this->middleware('can:print_batch.manage');
    }

    /**
     * Display a listing of print batches
     */
    public function index(Request $request)
    {
        try {
            // Habilitar registro de consultas SQL para depuración
            DB::enableQueryLog();
            
            // Registrar que estamos cargando el índice de lotes
            Log::info('[PRINT BATCH CONTROLLER] Cargando índice de lotes', [
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);
            
            // Establecer filtros predeterminados
            $filters = [
                'search' => $request->input('search'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 15),
            ];

            // Obtener los lotes paginados desde el servicio
            $batches = $this->printBatchService->getPaginatedBatches($filters, $filters['per_page']);
            
            // Log simplificado
            Log::debug('[PRINT BATCH CONTROLLER] Lotes obtenidos', [
                'total' => $batches->total(),
                'current_page' => $batches->currentPage(),
                'items_count' => count($batches->items())
            ]);
            
            // Obtener datos adicionales para la vista
            $filtersData = $this->printBatchService->getFiltersData();
            $stats = $this->printBatchService->getBatchStats();

            // Los accessors del modelo ya manejan correctamente los valores null
            // convirtiendo null en etiquetas descriptivas como "Todas las áreas"

            // Renderizar la vista con los datos procesados
            return Inertia::render('print-batches/index', [
                'batches' => $batches,
                'filters' => $filters,
                'filtersData' => $filtersData,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            // Registrar el error
            Log::error('[PRINT BATCH CONTROLLER] Error en índice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            // Registrar la última consulta SQL ejecutada (si está disponible)
            try {
                $lastQuery = DB::getQueryLog();
                $lastQueryString = !empty($lastQuery) ? end($lastQuery)['query'] : 'No hay consultas registradas';
                Log::debug('[PRINT BATCH DEBUG] Última consulta SQL', ['query' => $lastQueryString]);
            } catch (\Exception $queryError) {
                Log::debug('[PRINT BATCH DEBUG] Error al obtener consulta SQL', ['error' => $queryError->getMessage()]);
            }
            
            throw $e;
        }
    }

    /**
     * Show the form for creating a new print batch
     */
    public function create()
    {
        try {
            $filtersData = $this->printBatchService->getFiltersData();

            return Inertia::render('print-batches/create', [
                'filtersData' => $filtersData
            ]);

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error en create', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->handleException($e, 'Error al cargar el formulario de creación.');
        }
    }

    /**
     * Store a newly created print batch
     */
    public function store(CreateBatchRequest $request)
    {
        try {
            Log::info('[PRINT BATCH CONTROLLER] Creando nuevo lote', [
                'user_id' => auth()->id(),
                'filters' => $request->validated()
            ]);

            $batch = $this->printBatchService->queueBatch(
                $request->validated(),
                auth()->user()
            );

            Log::info('[PRINT BATCH CONTROLLER] Lote creado exitosamente', [
                'batch_uuid' => $batch->uuid,
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->route('print-batches.index')
                ->with('success', "Lote de impresión creado exitosamente. UUID: {$batch->uuid}");

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error al crear lote', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->validated()
            ]);

            return $this->handleException($e, 'Error al crear el lote de impresión.');
        }
    }

    /**
     * Display the specified print batch
     */
    public function show(PrintBatch $printBatch)
    {
        try {
            // Cargar solo las relaciones válidas (area y provider ahora son accessors desde arrays JSON)
            $printBatch->load(['event', 'generatedBy', 'credentials.accreditationRequest.employee']);

            return Inertia::render('print-batches/show', [
                'batch' => $printBatch
            ]);

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error en show', [
                'batch_uuid' => $printBatch->uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->handleException($e, 'Error al cargar los detalles del lote.');
        }
    }

    /**
     * Download the print batch PDF
     */
    public function download(PrintBatch $printBatch): BinaryFileResponse
    {
        try {
            Log::info('[PRINT BATCH CONTROLLER] Descargando lote', [
                'batch_uuid' => $printBatch->uuid,
                'user_id' => auth()->id()
            ]);

            $filePath = $this->printBatchService->downloadBatch($printBatch);
            $fileName = "lote_impresion_{$printBatch->uuid}.pdf";

            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error en descarga', [
                'batch_uuid' => $printBatch->uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al descargar el lote: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed print batch
     */
    public function retry(PrintBatch $printBatch)
    {
        try {
            Log::info('[PRINT BATCH CONTROLLER] Reintentando lote', [
                'batch_uuid' => $printBatch->uuid,
                'user_id' => auth()->id()
            ]);

            $batch = $this->printBatchService->retryBatch($printBatch);

            return redirect()
                ->back()
                ->with('success', "Lote reencolado para reintento. UUID: {$batch->uuid}");

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error al reintentar lote', [
                'batch_uuid' => $printBatch->uuid,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->handleException($e, 'Error al reintentar el lote.');
        }
    }

    /**
     * Get processing batches for polling
     */
    public function processing()
    {
        try {
            $processingBatches = $this->printBatchService->getProcessingBatches();

            return response()->json([
                'batches' => $processingBatches
            ]);

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error al obtener lotes en procesamiento', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => 'Error al obtener lotes en procesamiento'
            ], 500);
        }
    }

    /**
     * Preview credentials count before creating batch
     */
    public function preview(Request $request)
    {
        Log::info('[PRINT BATCH CONTROLLER] Preview method called', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'is_inertia' => $request->header('X-Inertia')
        ]);
        
        try {
            $filters = $request->validate([
                'event_id' => 'required|integer|exists:events,id',
                'area_id' => 'nullable|array',
                'area_id.*' => 'integer|exists:areas,id',
                'provider_id' => 'nullable|array',
                'provider_id.*' => 'integer|exists:providers,id',
                'only_unprinted' => 'boolean'
            ]);
            
            Log::info('[PRINT BATCH CONTROLLER] Validation passed', ['filters' => $filters]);

            $validatedFilters = $this->printBatchService->validateFilters($filters);
            Log::info('[PRINT BATCH CONTROLLER] Filters validated', ['validatedFilters' => $validatedFilters]);
            
            // Simular conteo sin crear el lote
            $credentialsCount = app(\App\Repositories\PrintBatch\PrintBatchRepositoryInterface::class)
                ->getCredentialsForPrinting($validatedFilters)
                ->count();
            
            Log::info('[PRINT BATCH CONTROLLER] Credentials count calculated', ['count' => $credentialsCount]);

            $preview = [
                'credentials_count' => $credentialsCount,
                'estimated_pages' => $credentialsCount, // 1 credencial = 1 página
                'can_create' => $credentialsCount > 0
            ];
            
            Log::info('[PRINT BATCH CONTROLLER] Preview data prepared', ['preview' => $preview]);

            // Detectar si es petición AJAX (fetch) o Inertia
            if ($request->expectsJson() || $request->ajax()) {
                Log::info('[PRINT BATCH CONTROLLER] Returning JSON response for AJAX request');
                return response()->json([
                    'preview' => $preview,
                    'success' => true
                ]);
            }

            // Fallback para peticiones Inertia (si fuera necesario)
            Log::info('[PRINT BATCH CONTROLLER] Returning Inertia response');
            return Inertia::render('print-batches/create', [
                'preview' => $preview
            ]);

        } catch (\Exception $e) {
            Log::error('[PRINT BATCH CONTROLLER] Error en preview', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Detectar si es petición AJAX para devolver JSON de error
            if ($request->expectsJson() || $request->ajax()) {
                Log::info('[PRINT BATCH CONTROLLER] Returning JSON error response for AJAX request');
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => true
                ], 422);
            }

            // Fallback para Inertia en caso de error
            Log::info('[PRINT BATCH CONTROLLER] Returning Inertia error response');
            return Inertia::render('print-batches/create', [
                'errors' => ['preview' => $e->getMessage()]
            ]);
        }
    }
}
