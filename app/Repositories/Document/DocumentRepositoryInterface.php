<?php

namespace App\Repositories\Document;

use App\Repositories\RepositoryInterface;

interface DocumentRepositoryInterface extends RepositoryInterface
{
    /**
     * List documents by entity (module and entity ID)
     * 
     * @param string $module
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listByEntity(string $module, int $entityId);
    
    /**
     * Get document types by module
     * 
     * @param string|null $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentTypes(string $module = null);
}
