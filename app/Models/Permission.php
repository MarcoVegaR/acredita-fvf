<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;
    
    /**
     * Get the roles associated with the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            config('permission.table_names.role_has_permissions'),
            config('permission.column_names.permission_pivot_key', 'permission_id'),
            config('permission.column_names.role_pivot_key', 'role_id')
        );
    }
    
    /**
     * Get users that have this permission directly
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.permission_pivot_key', 'permission_id'),
            config('permission.column_names.model_morph_key', 'model_id')
        );
    }
}
