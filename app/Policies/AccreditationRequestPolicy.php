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

        // Administrador y security_manager pueden ver todas
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
            return true;
        }

        // Gestor de área puede ver las de su área
        if ($user->hasRole('area_manager')) {
            return $user->managedArea && $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
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
        // Administrador y security_manager pueden actualizar cualquiera en cualquier estado
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
            return $user->can('accreditation_request.update');
        }
        
        // Area manager puede dar visto bueno o devolver para corrección
        if ($user->hasRole('area_manager')) {
            // Si tiene el permiso de revisión y la solicitud está enviada
            if ($user->can('accreditation_request.review') && $accreditationRequest->status->value === 'submitted') {
                return $user->managedArea && 
                       $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
            }
            
            // Si tiene el permiso de devolución y la solicitud está enviada o en revisión
            if ($user->can('accreditation_request.return') && 
                in_array($accreditationRequest->status->value, ['submitted', 'under_review'])) {
                return $user->managedArea && 
                       $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
            }
            
            // Para otras operaciones de actualización, solo en borrador
            if ($user->can('accreditation_request.update') && $accreditationRequest->isDraft()) {
                return $user->managedArea && 
                       $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
            }
            
            return false;
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
        // Administrador y security_manager pueden eliminar cualquiera en cualquier estado
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
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

    /**
     * Determine whether the user can approve the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, AccreditationRequest $accreditationRequest)
    {
        // Solo admin puede aprobar solicitudes
        if (!$user->hasRole('admin') || !$user->can('accreditation_request.approve')) {
            return false;
        }

        // Solo se pueden aprobar solicitudes enviadas o bajo revisión
        return in_array($accreditationRequest->status->value, ['submitted', 'under_review']);
    }

    /**
     * Determine whether the user can reject the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function reject(User $user, AccreditationRequest $accreditationRequest)
    {
        // Solo admin puede rechazar solicitudes
        if (!$user->hasRole('admin') || !$user->can('accreditation_request.reject')) {
            return false;
        }

        // Solo se pueden rechazar solicitudes enviadas o bajo revisión
        return in_array($accreditationRequest->status->value, ['submitted', 'under_review']);
    }

    /**
     * Determine whether the user can review the accreditation request.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function review(User $user, AccreditationRequest $accreditationRequest)
    {
        // Solo se puede revisar si tiene el permiso base
        if (!$user->can('accreditation_request.review')) {
            return false;
        }

        // Solo se pueden revisar solicitudes enviadas
        if ($accreditationRequest->status->value !== 'submitted') {
            return false;
        }

        // Admin puede revisar todas
        if ($user->hasRole('admin')) {
            return true;
        }

        // Area manager puede dar visto bueno a solicitudes de su área
        if ($user->hasRole('area_manager')) {
            return $user->managedArea && 
                   $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
        }

        return false;
    }

    /**
     * Determine whether the user can return the accreditation request to draft.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AccreditationRequest  $accreditationRequest
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function returnToDraft(User $user, AccreditationRequest $accreditationRequest)
    {
        // Solo se puede devolver si tiene el permiso base
        if (!$user->can('accreditation_request.return')) {
            return false;
        }

        // Solo se pueden devolver solicitudes enviadas o bajo revisión
        if (!in_array($accreditationRequest->status->value, ['submitted', 'under_review'])) {
            return false;
        }

        // Admin puede devolver todas
        if ($user->hasRole('admin')) {
            return true;
        }

        // Area manager puede devolver solicitudes de su área
        if ($user->hasRole('area_manager')) {
            return $user->managedArea && 
                   $accreditationRequest->employee->provider->area_id === $user->managedArea->id;
        }

        return false;
    }
}
