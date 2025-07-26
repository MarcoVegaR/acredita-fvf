<?php

namespace App\Policies;

use App\Models\AccreditationRequest;
use App\Models\User;

class CredentialPolicy
{
    /**
     * Ver credencial
     */
    public function view(User $user, AccreditationRequest $request): bool
    {
        // Admin y security_manager pueden ver cualquier credencial
        if ($user->hasPermissionTo('accreditation_request.view_any') || $user->hasRole('security_manager')) {
            return true;
        }

        // Provider solo puede ver credenciales de sus empleados
        if ($user->provider_id && $request->employee->provider_id === $user->provider_id) {
            return true;
        }

        return false;
    }

    /**
     * Descargar credencial
     */
    public function download(User $user, AccreditationRequest $request): bool
    {
        // Debe poder ver la credencial Y estar aprobada Y tener credencial lista
        return $this->view($user, $request) 
            && $request->status === \App\Enums\AccreditationStatus::Approved
            && $request->credential?->isReady();
    }

    /**
     * Regenerar credencial (solo admin)
     */
    public function regenerate(User $user, AccreditationRequest $request): bool
    {
        return $user->hasPermissionTo('accreditation_request.approve') 
            && $request->status === \App\Enums\AccreditationStatus::Approved;
    }

    /**
     * Ver estado de credencial (para polling)
     */
    public function viewStatus(User $user, AccreditationRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
