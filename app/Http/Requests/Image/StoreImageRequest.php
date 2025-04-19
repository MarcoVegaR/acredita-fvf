<?php

namespace App\Http\Requests\Image;

use App\Helpers\PermissionHelper;
use App\Models\ImageType;
use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $module = $this->input('module');
        
        // Check basic upload permission and module-specific permission if applicable
        return PermissionHelper::hasAllPermissions([
            'images.upload',
            "images.upload.{$module}"
        ]);
    }
    
    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si el archivo ya tiene errores (por ser demasiado grande para PHP),
            // añadimos un mensaje de error más descriptivo
            if (!$this->hasFile('file') && $this->isMethod('POST')) {
                $uploadMaxSize = $this->parseIniSize(ini_get('upload_max_filesize'));
                $postMaxSize = $this->parseIniSize(ini_get('post_max_size'));
                $phpMaxSize = min($uploadMaxSize, $postMaxSize);
                $maxSizeInMB = floor($phpMaxSize / 1024 / 1024);
                
                $validator->errors()->add('file', "El archivo es demasiado grande. El tamaño máximo permitido por el servidor es {$maxSizeInMB}MB.");
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Detectar el límite de PHP para subida de archivos (menor entre upload_max_filesize y post_max_size)
        $uploadMaxSize = $this->parseIniSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseIniSize(ini_get('post_max_size'));
        $phpMaxSize = min($uploadMaxSize, $postMaxSize);
        
        // Convertir a MB para la regla de validación (redondeando hacia abajo)
        $maxSizeInMB = floor($phpMaxSize / 1024 / 1024);
        
        // Si el límite es mayor a 5MB, limitamos a 5MB por política de la aplicación
        $maxSizeInMB = min($maxSizeInMB, 5);
        
        // Reglas de validación
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:' . ($maxSizeInMB * 1024), // Convertir MB a KB para la regla 'max'
            ],
            'module' => ['required', 'string', 'in:' . implode(',', config('images.modules', []))],
            'entity_id' => ['required', 'integer', 'min:1'],
            'type_code' => ['required', 'string', 'exists:image_types,code,module,' . $this->input('module')],
        ];
    }
    
    /**
     * Parse PHP ini size value to bytes
     * 
     * @param string $size
     * @return int
     */
    private function parseIniSize(string $size): int
    {
        $size = trim($size);
        $unit = strtolower($size[strlen($size) - 1]);
        $value = intval(substr($size, 0, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // Fall through intentionally
            case 'm':
                $value *= 1024;
                // Fall through intentionally
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        // Detectar el límite de PHP para subida de archivos
        $uploadMaxSize = $this->parseIniSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseIniSize(ini_get('post_max_size'));
        $phpMaxSize = min($uploadMaxSize, $postMaxSize);
        
        // Convertir a MB para el mensaje de error
        $maxSizeInMB = floor($phpMaxSize / 1024 / 1024);
        $maxSizeInMB = min($maxSizeInMB, 5); // Limitamos a 5MB
        
        return [
            'file.max' => "El archivo no debe ser mayor a {$maxSizeInMB}MB. La configuración del servidor permite máximo {$maxSizeInMB}MB.",
            'file.mimes' => 'El archivo debe ser una imagen (jpg, jpeg, png, webp).',
            'module.in' => 'El módulo seleccionado no es válido.',
            'type_code.exists' => 'El tipo de imagen seleccionado no es válido para este módulo.',
        ];
    }
}
