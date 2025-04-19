<?php

namespace App\Http\Requests\Document;

use App\Helpers\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $module = $this->route('module');
        
        return PermissionHelper::hasAnyPermission([
            'documents.upload',
            "documents.upload.{$module}"
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $module = $this->route('module');
        $config = config("documents.modules.{$module}");
        $maxSize = $config['max_size'] ?? 10240; // 10MB default
        $allowedMimes = $config['allowed_mimes'] ?? 'pdf';
        
        return [
            'file' => ["required", "file", "mimes:{$allowedMimes}", "max:{$maxSize}"],
            'document_type_id' => ['sometimes', 'nullable', 'exists:document_types,id']
        ];
    }
}
