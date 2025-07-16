<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $provider = $this->route('provider');
        return $this->user()->can('update', $provider);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = $this->route('provider');
        
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'rif' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9\-]+$/', 
                Rule::unique('providers', 'rif')->ignore($provider->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable', 'boolean'],
        ];
        
        // Si es proveedor externo, se puede cambiar de área
        if ($provider->type === 'external') {
            $rules['area_id'] = ['required', 'exists:areas,id'];
            
            // Información de usuario
            $rules['user.name'] = ['required', 'string', 'max:255'];
            $rules['user.email'] = ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($provider->user_id)];
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
        ];
    }
}
