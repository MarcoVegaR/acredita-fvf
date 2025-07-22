<?php

namespace App\Http\Requests\Employee;

use App\Repositories\Employee\EmployeeRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UpdateEmployeeRequest extends FormRequest
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
        // Permission check will be handled by the route middleware and policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'provider_id' => ['sometimes', 'required', 'exists:providers,id'],
            'document_type' => ['sometimes', 'required', 'string', 'in:V,E,P'],
            'document_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($employee) {
                    $documentType = $this->input('document_type', $employee->document_type);
                    
                    if ($this->employeeRepository->documentExistsGlobally(
                        $documentType,
                        $value,
                        $employee->id
                    )) {
                        $fail('El número de documento ya está registrado en el sistema.');
                    }
                },
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:50'],
            'last_name' => ['sometimes', 'required', 'string', 'max:50'],
            'function' => ['sometimes', 'required', 'string', 'max:100'],
            'photo' => ['nullable', function ($attribute, $value, $fail) {
                // Si es un archivo, validar como imagen
                if (is_file($value)) {
                    $validator = Validator::make([$attribute => $value], [
                        $attribute => 'image|max:2048',
                    ]);
                    
                    if ($validator->fails()) {
                        $fail($validator->errors()->first($attribute));
                    }
                }
                // Si es una string base64, validamos que sea una imagen
                elseif (is_string($value) && strpos($value, 'data:image') === 0) {
                    // Es una imagen base64 válida
                } 
                // Si no es ni archivo ni base64 de imagen, falla
                elseif (!is_null($value)) {
                    $fail('El campo fotografía debe ser una imagen válida.');
                }
            }],
            'active' => ['sometimes', 'boolean'],
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
