<?php

namespace App\Http\Requests\Employee;

use App\Repositories\Employee\EmployeeRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    protected $employeeRepository;

    /**
     * Create a new form request instance.
     *
     * @param EmployeeRepositoryInterface $employeeRepository
     */
    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permission check will be handled by the route middleware
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
            'provider_id' => ['required', 'exists:providers,id'],
            'document_type' => ['required', 'string', 'in:V,E,P'],
            'document_number' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if ($this->employeeRepository->documentExistsForProvider(
                        $this->input('document_type'),
                        $value,
                        $this->input('provider_id')
                    )) {
                        $fail('El número de documento ya está registrado para este proveedor.');
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'function' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', function ($attribute, $value, $fail) {
                if ($value) {
                    // Si es string (base64), verificar formato
                    if (is_string($value)) {
                        if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $value)) {
                            $fail('El campo ' . $attribute . ' debe ser una imagen válida.');
                        }
                        // Verificar tamaño (aproximado, base64 es ~33% más grande)
                        $sizeBytes = strlen($value) * 0.75;
                        if ($sizeBytes > 2048 * 1024) { // 2MB
                            $fail('El campo ' . $attribute . ' no debe ser mayor a 2MB.');
                        }
                    } else {
                        // Si es archivo, usar validación normal
                        $validator = \Validator::make([$attribute => $value], [
                            $attribute => 'image|max:2048'
                        ]);
                        if ($validator->fails()) {
                            $fail($validator->errors()->first($attribute));
                        }
                    }
                }
            }],
            'active' => ['nullable', 'boolean'],
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
            'provider_id' => 'proveedor',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'function' => 'función',
            'photo' => 'fotografía',
            'active' => 'activo',
        ];
    }
}
