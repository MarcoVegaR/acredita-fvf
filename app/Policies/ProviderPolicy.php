<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProviderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any providers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('provider.view');
    }

    /**
     * Determine whether the user can view the provider.
     */
    public function view(User $user, Provider $provider): bool
    {
        // Administrador puede ver cualquier proveedor
        if ($user->can('provider.view')) {
            return true;
        }

        // Gerente de área puede ver proveedores en sus áreas gestionadas
        if ($user->can('provider.manage_own_area')) {
            // Obtener todas las áreas gestionadas
            $managedAreaIds = $user->managedAreas()->pluck('id')->toArray();
            if (in_array($provider->area_id, $managedAreaIds)) {
                return true;
            }
        }

        // Proveedor puede ver su propio perfil
        if ($user->can('provider.view_own') && $provider->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create providers.
     */
    public function create(User $user): bool
    {
        return $user->can('provider.manage') || $user->can('provider.manage_own_area');
    }

    /**
     * Determine whether the user can update the provider.
     */
    public function update(User $user, Provider $provider): bool
    {
        // Administrador puede actualizar cualquier proveedor
        if ($user->can('provider.manage')) {
            return true;
        }

        // Gerente de área puede actualizar proveedores en sus áreas gestionadas
        if ($user->can('provider.manage_own_area')) {
            // Obtener todas las áreas gestionadas
            $managedAreaIds = $user->managedAreas()->pluck('id')->toArray();
            if (in_array($provider->area_id, $managedAreaIds)) {
                return true;
            }
        }

        // Proveedor puede actualizar su propio perfil (datos limitados)
        if ($user->can('provider.manage_own') && $provider->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can toggle the active status of the provider.
     */
    public function toggleActive(User $user, Provider $provider): bool
    {
        // Administrador puede activar/desactivar cualquier proveedor
        if ($user->can('provider.manage')) {
            return true;
        }

        // Gerente de área puede activar/desactivar proveedores en sus áreas gestionadas
        if ($user->can('provider.manage_own_area')) {
            // Obtener todas las áreas gestionadas
            $managedAreaIds = $user->managedAreas()->pluck('id')->toArray();
            if (in_array($provider->area_id, $managedAreaIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can reset the provider's password.
     */
    public function resetPassword(User $user, Provider $provider): bool
    {
        // Solo administradores y gerentes de área pueden resetear contraseñas
        // Y solo para proveedores externos
        if ($provider->type !== 'external') {
            return false;
        }

        // Administrador puede resetear contraseña de cualquier proveedor
        if ($user->can('provider.manage')) {
            return true;
        }

        // Gerente de área puede resetear contraseñas de proveedores en sus áreas gestionadas
        if ($user->can('provider.manage_own_area')) {
            // Obtener todas las áreas gestionadas
            $managedAreaIds = $user->managedAreas()->pluck('id')->toArray();
            if (in_array($provider->area_id, $managedAreaIds)) {
                return true;
            }
        }

        return false;
    }
}
