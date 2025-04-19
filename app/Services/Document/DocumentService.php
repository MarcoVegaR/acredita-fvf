<?php

namespace App\Services\Document;

use App\Models\Document;
use App\Models\DocumentType;
use App\Repositories\Document\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DocumentService implements DocumentServiceInterface
{
    protected $documentRepository;
    
    /**
     * DocumentService constructor.
     *
     * @param DocumentRepositoryInterface $documentRepository
     */
    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }
    
    /**
     * Upload a document
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $module
     * @param int $entityId
     * @param int $userId
     * @param int|null $typeId
     * @return \App\Models\Document
     */
    public function upload(UploadedFile $file, string $module, int $entityId, int $userId, int $typeId = null)
    {
        // Validar que el módulo exista
        if (!array_key_exists($module, config('documents.modules'))) {
            throw new \InvalidArgumentException("El módulo {$module} no está configurado");
        }
        
        // Obtener los tipos de documentos permitidos para este módulo
        $allowedTypes = $this->getDocumentTypes($module)->pluck('id')->toArray();
        
        // Validar que el tipo proporcionado sea válido para este módulo
        if ($typeId && !in_array($typeId, $allowedTypes)) {
            throw new \InvalidArgumentException("El tipo de documento seleccionado no está disponible para el módulo {$module}");
        }
        
        // Obtener el tipo de documento predeterminado si no se proporciona o es inválido
        if (!$typeId || !in_array($typeId, $allowedTypes)) {
            $defaultType = config("documents.modules.{$module}.default_type");
            $type = DocumentType::where('code', $defaultType)->where(function($query) use ($module) {
                $query->where('module', $module)->orWhereNull('module');
            })->firstOrFail();
            $typeId = $type->id;
        }
        
        // Generar UUID para el documento
        $uuid = (string) Str::uuid();
        
        // Guardar el archivo
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}.{$extension}";
        $path = $file->storeAs("documents/{$module}", $filename, 'public');
        
        // Crear registro de documento
        $document = $this->documentRepository->create([
            'document_type_id' => $typeId,
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'is_validated' => false,
        ]);
        
        // Crear relación polimórfica
        $modelClass = config("documents.modules.{$module}.model");
        $document->documentables()->create([
            'documentable_type' => $modelClass,
            'documentable_id' => $entityId,
        ]);
        
        // Disparar evento
        event(new \App\Events\DocumentUploaded($document, $module, $entityId));
        
        return $document;
    }
    
    /**
     * Delete a document
     * 
     * @param string $documentUuid
     * @return bool
     */
    public function delete(string $documentUuid)
    {
        $document = $this->documentRepository->findByUuid($documentUuid);
        
        // Eliminar archivo físico
        Storage::disk('public')->delete($document->path);
        
        // Eliminar registro
        $result = $this->documentRepository->deleteByUuid($documentUuid);
        
        // Disparar evento
        event(new \App\Events\DocumentDeleted($document));
        
        return $result;
    }
    
    /**
     * List documents for a specific entity
     * 
     * @param string $module
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function list(string $module, int $entityId)
    {
        return $this->documentRepository->listByEntity($module, $entityId);
    }
    
    /**
     * Get document by UUID
     * 
     * @param string $uuid
     * @return \App\Models\Document
     */
    public function getByUuid(string $uuid)
    {
        return $this->documentRepository->findByUuid($uuid);
    }
    
    /**
     * Get available document types for a module
     * 
     * @param string|null $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentTypes(string $module = null)
    {
        return $this->documentRepository->getDocumentTypes($module);
    }
}
