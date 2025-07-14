<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any templates.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return $user->can('templates.index');
    }

    /**
     * Determine whether the user can view the template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return bool
     */
    public function view(User $user, Template $template)
    {
        return $user->can('templates.show');
    }

    /**
     * Determine whether the user can create templates.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->can('templates.create');
    }

    /**
     * Determine whether the user can update the template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return bool
     */
    public function update(User $user, Template $template)
    {
        return $user->can('templates.edit');
    }

    /**
     * Determine whether the user can delete the template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return bool
     */
    public function delete(User $user, Template $template)
    {
        // No se puede eliminar la plantilla predeterminada
        if ($template->is_default) {
            return false;
        }
        
        return $user->can('templates.delete');
    }
    
    /**
     * Determine whether the user can set the template as default.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template  $template
     * @return bool
     */
    public function setAsDefault(User $user, Template $template)
    {
        // No se puede establecer como predeterminada si ya lo es
        if ($template->is_default) {
            return false;
        }
        
        return $user->can('templates.set_default');
    }
}
