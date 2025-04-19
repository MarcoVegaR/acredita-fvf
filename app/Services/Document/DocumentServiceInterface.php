<?php

namespace App\Services\Document;

use Illuminate\Http\UploadedFile;

interface DocumentServiceInterface
{
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
    public function upload(UploadedFile $file, string $module, int $entityId, int $userId, int $typeId = null);
    
    /**
     * Delete a document
     * 
     * @param string $documentUuid
     * @return bool
     */
    public function delete(string $documentUuid);
    
    /**
     * List documents for a specific entity
     * 
     * @param string $module
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function list(string $module, int $entityId);
    
    /**
     * Get document by UUID
     * 
     * @param string $uuid
     * @return \App\Models\Document
     */
    public function getByUuid(string $uuid);
    
    /**
     * Get available document types for a module
     * 
     * @param string|null $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentTypes(string $module = null);
}
