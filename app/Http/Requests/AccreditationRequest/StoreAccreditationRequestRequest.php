<?php

namespace App\Http\Requests\AccreditationRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccreditationRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('accreditation_request.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'zones' => ['required', 'array', 'min:1'],
            'zones.*' => ['integer', 'exists:zones,id'],
            'comments' => ['nullable', 'string', 'max:500'],
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'employee_id' => (int) $this->employee_id,
            'event_id' => (int) $this->event_id,
            'zones' => array_map('intval', $this->zones ?? []),
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Debe seleccionar un empleado',
            'event_id.required' => 'Debe seleccionar un evento',
            'zones.required' => 'Debe seleccionar al menos una zona',
            'zones.min' => 'Debe seleccionar al menos una zona',
        ];
    }
}
