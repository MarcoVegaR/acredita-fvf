<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir tanto provider.manage como provider.manage_own_area
        return $this->user()->can('provider.manage') || $this->user()->can('provider.manage_own_area');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'rif' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9\-]+$/', 'unique:providers,rif'],
            'phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::in(['internal', 'external'])],
            'active' => ['nullable', 'boolean'],
        ];
        
        // Validaciones específicas para proveedores internos
        if ($this->type === 'internal') {
            // Validar que el área exista y no tenga ya un proveedor interno
            $rules['area_id'] = [
                'required',
                'exists:areas,id',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\Provider::where('area_id', $value)
                        ->where('type', 'internal')
                        ->exists();
                    if ($exists) {
                        $fail('Ya existe un proveedor interno para esta área.');
                    }
                }
            ];
            // El user_id es opcional para proveedores internos
            $rules['user_id'] = ['nullable', 'exists:users,id'];
        } else { // Proveedores externos
            // Área requerida sin validación adicional para externos
            $rules['area_id'] = ['required', 'exists:areas,id'];
            // Usuario requerido para proveedores externos
            $rules['user.name'] = ['required', 'string', 'max:255'];
            $rules['user.email'] = ['required', 'email', 'max:255', 'unique:users,email'];
            $rules['user.password'] = ['nullable', 'string', 'min:8'];
        }
        
        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rif.regex' => 'El RIF debe contener solo letras mayúsculas, números y guiones.',
            'rif.unique' => 'Ya existe un proveedor con este RIF.',
            'user.email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'area_id.required' => 'Debe seleccionar un área para el proveedor.',
            'area_id.exists' => 'El área seleccionada no existe en el sistema.',
            'user_id.exists' => 'El usuario seleccionado no existe en el sistema.',
        ];
    }
}
