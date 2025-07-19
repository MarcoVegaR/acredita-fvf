<?php

namespace App\Policies;

use App\Models\AccreditationRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccreditationRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any accreditation requests.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function index(User $user)
    {
        return $user->can('accreditation_request.index');
    }

    /**
     * Determine whether the user can view the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AccreditationRequest $accreditationRequest)
    {
        // Si no tiene permiso base, denegar
        if (!$user->can('accreditation_request.view')) {
            return false;
        }

        // Administrador puede ver todas
        if ($user->hasRole('admin')) {
            return true;
        }

        // Gestor de área puede ver las de su área
        if ($user->hasRole('area_manager')) {
            return $accreditationRequest->employee->provider->area_id === $user->area_id;
        }

        // Proveedor solo puede ver sus propias solicitudes
        if ($user->hasRole('provider')) {
            return $user->provider && $accreditationRequest->employee->provider_id === $user->provider->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create accreditation requests.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('accreditation_request.create');
    }

    /**
     * Determine whether the user can update the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AccreditationRequest $accreditationRequest)
    {
        // Administrador puede actualizar cualquiera en cualquier estado
        if ($user->hasRole('admin')) {
            return $user->can('accreditation_request.update');
        }

        // Solo se pueden actualizar borradores para otros roles
        if (!$accreditationRequest->isDraft()) {
            return false;
        }

        // Proveedor solo puede actualizar sus propias solicitudes en borrador
        if ($user->hasRole('provider')) {
            return $user->can('accreditation_request.update') && 
                  $user->provider && 
                  $accreditationRequest->employee->provider_id === $user->provider->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AccreditationRequest $accreditationRequest)
    {
        // Administrador puede eliminar cualquiera en cualquier estado
        if ($user->hasRole('admin')) {
            return $user->can('accreditation_request.delete');
        }

        // Solo se pueden eliminar borradores para otros roles
        if (!$accreditationRequest->isDraft()) {
            return false;
        }

        // Proveedor solo puede eliminar sus propias solicitudes en borrador
        if ($user->hasRole('provider')) {
            return $user->can('accreditation_request.delete') && 
                  $user->provider && 
                  $accreditationRequest->employee->provider_id === $user->provider->id;
        }

        return false;
    }

    /**
     * Determine whether the user can submit the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function submit(User $user, AccreditationRequest $accreditationRequest)
    {
        // Solo se pueden enviar borradores
        if (!$accreditationRequest->isDraft()) {
            return false;
        }

        // Mismo criterio que update
        return $this->update($user, $accreditationRequest) && 
               $user->can('accreditation_request.submit');
    }
}
