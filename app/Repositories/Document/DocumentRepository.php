<?php

namespace App\Repositories\Document;

use App\Models\Document;
use App\Models\DocumentType;
use App\Repositories\BaseRepository;

class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    /**
     * DocumentRepository constructor.
     *
     * @param Document $model
     */
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }
    
    /**
     * List documents by entity (module and entity ID)
     * 
     * @param string $module
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listByEntity(string $module, int $entityId)
    {
        $modelClass = config("documents.modules.{$module}.model");
        
        if (!$modelClass) {
            throw new \InvalidArgumentException("Module {$module} not found in config");
        }
        
        return $this->model
            ->whereHas('documentables', function ($query) use ($modelClass, $entityId) {
                $query->where('documentable_type', $modelClass)
                      ->where('documentable_id', $entityId);
            })
            ->with(['type', 'user'])
            ->latest()
            ->get();
    }
    
    // UUID methods are now handled by the BaseRepository
    
    /**
     * Get available document types for a module
     * 
     * @param string|null $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentTypes(string $module = null)
    {
        $query = DocumentType::query();
        
        if ($module) {
            // Filtrar estrictamente por módulo si está especificado
            // Solo incluir tipos específicamente asignados a este módulo
            $query->where('module', $module);
            
            // Si no hay tipos específicos para este módulo, permitir tipos genéricos (null)
            if ($query->count() === 0) {
                $query = DocumentType::whereNull('module');
            }
        }
        
        return $query->orderBy('label')->get();
    }
}
