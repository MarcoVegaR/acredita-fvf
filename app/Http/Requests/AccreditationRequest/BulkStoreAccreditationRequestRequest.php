<?php

namespace App\Http\Requests\AccreditationRequest;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreAccreditationRequestRequest extends FormRequest
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
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'employee_zones' => ['required', 'array', 'min:1'],
            'employee_zones.*' => ['required', 'array', 'min:1'],
            'employee_zones.*.*' => ['integer', 'exists:zones,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        \Log::info('BulkStoreAccreditationRequestRequest::prepareForValidation - Datos originales', [
            'event_id' => $this->event_id,
            'employee_zones' => $this->employee_zones,
            'notes' => $this->notes,
        ]);

        $preparedData = [
            'event_id' => (int) $this->event_id,
            'notes' => $this->notes,
        ];

        // Convertir employee_zones a integers
        if ($this->has('employee_zones') && is_array($this->employee_zones)) {
            $employeeZones = [];
            foreach ($this->employee_zones as $employeeId => $zones) {
                if (is_array($zones)) {
                    $employeeZones[(int) $employeeId] = array_map('intval', $zones);
                } else {
                    \Log::warning('BulkStoreAccreditationRequestRequest - Zones no es array', [
                        'employee_id' => $employeeId,
                        'zones' => $zones,
                        'zones_type' => gettype($zones)
                    ]);
                }
            }
            $preparedData['employee_zones'] = $employeeZones;
        } else {
            \Log::warning('BulkStoreAccreditationRequestRequest - employee_zones no existe o no es array', [
                'has_employee_zones' => $this->has('employee_zones'),
                'employee_zones_type' => gettype($this->employee_zones)
            ]);
        }

        $this->merge($preparedData);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_id.required' => 'Debe seleccionar un evento',
            'event_id.exists' => 'El evento seleccionado no existe',
            'employee_zones.required' => 'Debe configurar zonas para al menos un empleado',
            'employee_zones.min' => 'Debe configurar zonas para al menos un empleado',
            'employee_zones.*.required' => 'Cada empleado debe tener al menos una zona asignada',
            'employee_zones.*.min' => 'Cada empleado debe tener al menos una zona asignada',
            'employee_zones.*.*.exists' => 'Una de las zonas seleccionadas no existe',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres',
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
            'event_id' => 'evento',
            'employee_zones' => 'configuración de zonas',
            'notes' => 'notas',
        ];
    }
}
