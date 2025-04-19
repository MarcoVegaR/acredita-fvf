<?php

namespace App\Http\Requests\Image;

use App\Helpers\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;

class DeleteImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $module = $this->route('module');
        
        // Check basic delete permission and module-specific permission if applicable
        return PermissionHelper::hasAllPermissions([
            'images.delete',
            "images.delete.{$module}"
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'exists:images,uuid'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'uuid.exists' => 'La imagen solicitada no existe.',
        ];
    }
}
