<?php

namespace App\Http\Requests\AccreditationRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccreditationRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $accreditationRequest = $this->route('accreditation_request');
        return $this->user()->can('update', $accreditationRequest);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'zones' => ['required', 'array', 'min:1'],
            'zones.*' => ['exists:zones,id'],
            'comments' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'zones.required' => 'Debe seleccionar al menos una zona',
            'zones.min' => 'Debe seleccionar al menos una zona',
        ];
    }
}
