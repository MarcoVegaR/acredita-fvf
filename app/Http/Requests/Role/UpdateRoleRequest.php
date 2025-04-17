<?php

namespace App\Http\Requests\Role;

use App\Models\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $role = $this->route('role');
        
        // El usuario debe tener permiso para editar roles
        if (!$this->user()->can('roles.edit')) {
            return false;
        }
        
        // Solo protecciones especiales para rol admin
        if ($role->name === 'admin') {
            // No permitir cambiar el nombre del rol admin
            if (isset($this->name) && $this->name !== 'admin') {
                $this->failedAuthorization();
                return false;
            }
            
            // Admin debe mantener todos los permisos
            $allPermissions = app(\App\Models\Permission::class)->pluck('name')->toArray();
            $requestPermissions = $this->input('permissions', []);
            
            // Verificar que todos los permisos estén incluidos
            foreach ($allPermissions as $permission) {
                if (!in_array($permission, $requestPermissions)) {
                    $this->failedAuthorization();
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $this->role->id],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,name']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Ya existe un rol con este nombre',
            'permissions.required' => 'Debe seleccionar al menos un permiso',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no existen'
        ];
    }
    
    /**
     * Handle a failed authorization attempt.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return void
     */
    protected function failedAuthorization()
    {
        $role = $this->route('role');
        
        if ($role && $role->name === 'admin') {
            if (isset($this->name) && $this->name !== 'admin') {
                throw new \Illuminate\Auth\Access\AuthorizationException('No se puede cambiar el nombre del rol administrador');
            } else {
                throw new \Illuminate\Auth\Access\AuthorizationException('No se pueden quitar permisos del rol administrador');
            }
        }
        
        throw new \Illuminate\Auth\Access\AuthorizationException('No tienes permiso para editar roles');
    }
}
