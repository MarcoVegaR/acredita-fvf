<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DeleteUserRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Delegamos la verificación de permisos al middleware 'permission:users.delete'
        // Las validaciones específicas de eliminación se manejan en el método after()
        return $this->user()->can('users.delete');
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
            // 1. Prevenir auto-eliminación
            function (\Illuminate\Validation\Validator $validator) {
                $userToDelete = $this->route('user');
                $currentUser = $this->user();
                
                if ($currentUser->id === $userToDelete->id) {
                    $validator->errors()->add(
                        'id',
                        'No puedes eliminar tu propia cuenta.'
                    );
                }
            },
            
            // 2. Proteger cuenta de administrador principal
            function (\Illuminate\Validation\Validator $validator) {
                $userToDelete = $this->route('user');
                
                if ($userToDelete->id === 1) {
                    $validator->errors()->add(
                        'id',
                        'No puedes eliminar la cuenta principal de administrador.'
                    );
                }
            },
            
            // 3. Protección de cuentas críticas (similar a UpdateUserRequest)
            function (\Illuminate\Validation\Validator $validator) {
                $userToDelete = $this->route('user');
                $currentUser = $this->user();
                
                // Lista de correos electrónicos críticos que requieren protección especial
                $criticalUsers = [
                    'test@mailinator.com',
                    'proyectos@caracoders.com.ve',
                ];
                
                // Si el usuario es crítico y el usuario actual no es super-admin
                if (in_array($userToDelete->email, $criticalUsers) && !$currentUser->hasRole('super-admin')) {
                    $validator->errors()->add(
                        'email',
                        'Este usuario tiene protección especial y solo puede ser eliminado por un super administrador.'
                    );
                }
            }
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
    
    /**
     * Get the resource name for this request.
     */
    protected function getResourceName(): string
    {
        return 'Usuario';
    }
}
