<?php

namespace App\Http\Requests\PrintBatch;

use Illuminate\Foundation\Http\FormRequest;

class CreateBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('print_batch.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|integer|exists:events,id',
            'area_id' => 'nullable|array',
            'area_id.*' => 'integer|exists:areas,id',
            'provider_id' => 'nullable|array',
            'provider_id.*' => 'integer|exists:providers,id',
            'only_unprinted' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'event_id.required' => 'El evento es obligatorio.',
            'event_id.exists' => 'El evento seleccionado no existe.',
            'area_id.*.exists' => 'Una o más áreas seleccionadas no existen.',
            'provider_id.*.exists' => 'Uno o más proveedores seleccionados no existen.',
            'only_unprinted.boolean' => 'El filtro "solo no impresas" debe ser verdadero o falso.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'event_id' => 'evento',
            'area_id' => 'áreas',
            'provider_id' => 'proveedores',
            'only_unprinted' => 'solo no impresas'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir area_id y provider_id a arrays si vienen como strings
        if ($this->has('area_id') && !is_array($this->area_id)) {
            $this->merge([
                'area_id' => $this->area_id ? [$this->area_id] : null
            ]);
        }

        if ($this->has('provider_id') && !is_array($this->provider_id)) {
            $this->merge([
                'provider_id' => $this->provider_id ? [$this->provider_id] : null
            ]);
        }

        // Establecer valor por defecto para only_unprinted
        if (!$this->has('only_unprinted')) {
            $this->merge(['only_unprinted' => true]);
        }
    }
}
