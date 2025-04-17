<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Http\Requests\BaseFormRequest;

class DeleteRoleRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Delegamos la verificación de permisos al middleware 'permission:roles.delete'
        // Las validaciones específicas de eliminación se manejan en el método after()
        return $this->user()->can('roles.delete');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No hay datos que validar en una solicitud de eliminación
        return [];
    }

    /**
     * Perform additional validation after initial validation rules pass.
     *
     * @return array
     */
    public function after(): array
    {
        return [
            // 1. Proteger el rol admin (no se puede eliminar)
            function (\Illuminate\Validation\Validator $validator) {
                $roleToDelete = $this->route('role');
                
                if ($roleToDelete->name === 'admin') {
                    $validator->errors()->add(
                        'name',
                        'No puedes eliminar el rol de administrador principal.'
                    );
                }
            },
            
            // 2. Verificar que el rol no esté asociado a usuarios
            function (\Illuminate\Validation\Validator $validator) {
                $roleToDelete = $this->route('role');
                
                if ($roleToDelete->users()->count() > 0) {
                    $validator->errors()->add(
                        'users',
                        'No puedes eliminar un rol que tiene usuarios asignados.'
                    );
                }
            }
        ];
    }
    
    /**
     * Get the resource name for this request.
     */
    protected function getResourceName(): string
    {
        return 'Rol';
    }
}
