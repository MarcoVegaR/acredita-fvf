<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UpdateUserRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to update users
        return $this->user()->can('users.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($this->route('user')->id)
            ],
            'password' => [
                'nullable', 
                'string', 
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
            'active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    /**
     * Perform additional validation after initial validation rules pass.
     */
    public function after(): array
    {
        return [
            // 1. Protección del último administrador
            function (\Illuminate\Validation\Validator $validator) {
                $user = $this->route('user');
                
                // Solo si se están cambiando roles
                if ($this->has('roles') && $user->hasRole('admin')) {
                    $newRoles = $this->input('roles', []);
                    
                    // Si están quitando el rol de admin
                    if (!in_array('admin', $newRoles)) {
                        // Verificar si es el último administrador
                        $adminCount = \App\Models\User::role('admin')->count();
                        if ($adminCount <= 1) {
                            $validator->errors()->add(
                                'roles',
                                'No puedes quitar el rol de administrador al último administrador del sistema.'
                            );
                        }
                    }
                }
            },
            
            // 2. Protección de cuenta propia (evitar auto-desactivación ya implementado)
            function (\Illuminate\Validation\Validator $validator) {
                $user = $this->route('user');
                $currentUser = $this->user();
                
                // Si está modificando su propia cuenta
                if ($currentUser->id == $user->id) {
                    // Ya tenemos la validación de auto-desactivación
                    
                    // Prevenir que un usuario modifique sus propios roles
                    if ($this->has('roles') && !$currentUser->hasRole('super-admin')) {
                        $validator->errors()->add(
                            'roles',
                            'No puedes modificar tus propios roles. Contacta a otro administrador.'
                        );
                    }
                }
            },
            
            // 3. Prevención de modificación de usuarios críticos
            function (\Illuminate\Validation\Validator $validator) {
                $user = $this->route('user');
                $currentUser = $this->user();
                
                // Lista de IDs o emails de usuarios críticos que deben protegerse
                $criticalUsers = [
                    'test@mailinator.com', // Ejemplo - actualizar con valores reales
                    'proyectos@caracoders.com.ve',
                ];
                
                // Si el usuario es crítico y el usuario actual no es super-admin
                if (in_array($user->email, $criticalUsers) && !$currentUser->hasRole('super-admin')) {
                    $validator->errors()->add(
                        'email',
                        'Este usuario tiene protección especial y solo puede ser modificado por un super administrador.'
                    );
                }
            },
            
            // 4. Prevenir que un usuario desactive su propia cuenta (original)
            function (\Illuminate\Validation\Validator $validator) {
                if ($this->user()->id == $this->route('user')->id && 
                    $this->has('active') && 
                    $this->input('active') === false) {
                    $validator->errors()->add('active', 'No puedes desactivar tu propia cuenta.');
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
        return [
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'active' => 'estado activo',
            'roles' => 'roles',
        ];
    }

    /**
     * Get the resource name for this request.
     */
    protected function getResourceName(): string
    {
        return 'Usuario';
    }
}
