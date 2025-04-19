<?php

namespace App\Http\Requests\Document;

use App\Helpers\PermissionHelper;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class DeleteDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        Log::info("Iniciando autorización para eliminar documento", [
            'document_uuid' => $this->route('document_uuid'), 
            'user_id' => $user?->id,
            'user_name' => $user?->name,
        ]);

        try {
            $document = Document::where('uuid', $this->route('document_uuid'))->firstOrFail();
            Log::info("Documento encontrado", ['document_id' => $document->id]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Documento no encontrado para UUID", ['document_uuid' => $this->route('document_uuid')]);
            return false;
        }

        $documentable = $document->documentables()->first();

        if (!$documentable) {
            Log::warning("Documentable no encontrado para el documento", ['document_id' => $document->id]);
            return false;
        }
        Log::info("Documentable encontrado", [
            'documentable_id' => $documentable->documentable_id,
            'documentable_type' => $documentable->documentable_type
        ]);

        // Extraer el nombre del módulo desde el tipo documentable
        $modelClass = $documentable->documentable_type;
        // Busca la clave del módulo en la configuración
        $modulesConfig = config('documents.modules');
        $moduleKey = false;
        foreach ($modulesConfig as $key => $config) {
            if ($config['model'] === $modelClass) {
                $moduleKey = $key;
                break;
            }
        }
        // $moduleKey = array_search($modelClass, array_column(config('documents.modules'), 'model')); // Forma original, puede fallar si la clave no es numérica
        Log::info("Clase del modelo y clave del módulo", ['modelClass' => $modelClass, 'moduleKey' => $moduleKey ?: 'No encontrado']);

        if ($moduleKey === false) { // Comprobar explícitamente false, ya que '0' es una clave válida
            Log::warning("No se encontró la clave del módulo para el modelo", ['modelClass' => $modelClass]);
            return false;
        }

        $permissionsToCheck = [
            'documents.delete',
            "documents.delete.{$moduleKey}"
        ];
        Log::info("Permisos a verificar", ['permissions' => $permissionsToCheck]);

        $hasPermission = PermissionHelper::hasAnyPermission($permissionsToCheck);
        Log::info("Resultado de la verificación de permisos", ['hasPermission' => $hasPermission]);

        return $hasPermission;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_uuid' => ['required', 'string', 'exists:documents,uuid']
        ];
    }
}
