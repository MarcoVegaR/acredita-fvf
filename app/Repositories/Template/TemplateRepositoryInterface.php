<?php

namespace App\Repositories\Template;

use App\Models\Template;
use App\Repositories\RepositoryInterface;

interface TemplateRepositoryInterface extends RepositoryInterface
{
    /**
     * Get templates for a specific event
     *
     * @param int $eventId
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listByEvent(int $eventId, array $relations = []);
    
    /**
     * Find template by UUID
     *
     * @param string $uuid
     * @param array $relations
     * @return \App\Models\Template|null
     */
    public function findByUuid(string $uuid, array $relations = []);
    
    /**
     * Get the default template for an event
     *
     * @param int $eventId
     * @param array $relations
     * @return \App\Models\Template|null
     */
    public function getDefaultForEvent(int $eventId, array $relations = []);
    
    /**
     * Set a template as default for its event
     * (and unset any existing default)
     * 
     * @param \App\Models\Template $template
     * @return bool
     */
    public function setAsDefault(Template $template);
}
