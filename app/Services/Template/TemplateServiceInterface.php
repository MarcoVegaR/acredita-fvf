<?php

namespace App\Services\Template;

use App\Models\Template;

interface TemplateServiceInterface
{
    /**
     * Get templates for an event
     *
     * @param int $eventId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForEvent(int $eventId);
    
    /**
     * Get a template by its UUID
     *
     * @param string $uuid
     * @return \App\Models\Template|null
     */
    public function get(string $uuid);
    
    /**
     * Get the default template for an event
     *
     * @param int $eventId
     * @return \App\Models\Template|null
     */
    public function getDefaultForEvent(int $eventId);
    
    /**
     * Create a new template
     *
     * @param array $data
     * @return \App\Models\Template
     */
    public function create(array $data);
    
    /**
     * Update a template
     *
     * @param \App\Models\Template $template
     * @param array $data
     * @return \App\Models\Template
     */
    public function update(Template $template, array $data);
    
    /**
     * Set a template as default for its event
     *
     * @param \App\Models\Template $template
     * @return bool
     */
    public function setAsDefault(Template $template);
    
    /**
     * Delete a template
     *
     * @param \App\Models\Template $template
     * @return bool
     */
    public function delete(Template $template);
    
    /**
     * Get paginated templates with optional filters.
     *
     * @param int $perPage Elements per page
     * @param int $page Page number
     * @param string|null $search Search term
     * @param string $sortBy Sort field
     * @param string $sortOrder Sort direction (asc, desc)
     * @param int|null $eventId Filter by event ID
     * @return array Paginated data
     */
    public function getPaginatedTemplates(int $perPage = 10, int $page = 1, ?string $search = '', string $sortBy = 'created_at', string $sortOrder = 'desc', ?int $eventId = null);
}
