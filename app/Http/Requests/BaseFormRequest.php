<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Esta función debe ser implementada por las clases hijas.
     */
    abstract public function authorize(): bool;

    /**
     * Get the validation rules that apply to the request.
     * Esta función debe ser implementada por las clases hijas.
     */
    abstract public function rules(): array;

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
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     * Personaliza el comportamiento cuando falla la validación.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422));
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the success message for a controller action.
     * Proporciona mensajes de éxito estandarizados para acciones CRUD.
     */
    public function getSuccessMessage(string $action = 'saved'): string
    {
        $messages = [
            'store' => 'creado',
            'update' => 'actualizado',
            'delete' => 'eliminado',
            'restore' => 'restaurado',
        ];

        $action = $messages[$action] ?? $action;
        $resourceName = $this->getResourceName();

        return "{$resourceName} {$action} correctamente.";
    }

    /**
     * Get the resource name for this request.
     * Las clases hijas pueden sobrescribir este método.
     */
    protected function getResourceName(): string
    {
        // Por defecto, usa el nombre del controlador sin "Controller" y en singular
        $reflection = new \ReflectionClass($this);
        $className = $reflection->getShortName();
        
        // Quita "Request" y cualquier prefijo como "Store" o "Update"
        $resourceName = preg_replace('/(Store|Update|Delete|)Request$/', '', $className);
        
        // Si no se pudo determinar, usa "Registro"
        return $resourceName ?: 'Registro';
    }
}
