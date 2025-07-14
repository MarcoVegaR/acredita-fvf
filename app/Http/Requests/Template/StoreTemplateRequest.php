<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;

class StoreTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::user()->can('templates.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'template_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,bmp,pdf|max:5120', // 5MB máximo
            'layout_meta' => 'required|array',
            'layout_meta.fold_mm' => 'required|numeric',
            'layout_meta.rect_photo' => 'required|array',
            'layout_meta.rect_photo.x' => 'required|numeric|min:0',
            'layout_meta.rect_photo.y' => 'required|numeric|min:0',
            'layout_meta.rect_photo.width' => 'required|numeric|min:1',
            'layout_meta.rect_photo.height' => 'required|numeric|min:1',
            'layout_meta.rect_qr' => 'required|array',
            'layout_meta.rect_qr.x' => 'required|numeric|min:0',
            'layout_meta.rect_qr.y' => 'required|numeric|min:0',
            'layout_meta.rect_qr.width' => 'required|numeric|min:1',
            'layout_meta.rect_qr.height' => 'required|numeric|min:1',
            'layout_meta.text_blocks' => 'nullable|array',
            'layout_meta.text_blocks.*.id' => 'required|string|max:50',
            'layout_meta.text_blocks.*.x' => 'required|numeric|min:0',
            'layout_meta.text_blocks.*.y' => 'required|numeric|min:0',
            'layout_meta.text_blocks.*.width' => 'required|numeric|min:1',
            'layout_meta.text_blocks.*.height' => 'required|numeric|min:1',
            'layout_meta.text_blocks.*.font_size' => 'required|numeric|min:1',
            'layout_meta.text_blocks.*.alignment' => 'required|in:left,center,right',
            'is_default' => 'nullable|boolean',
            'version' => 'nullable|integer|min:1'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'event_id' => 'evento',
            'name' => 'nombre',
            'template_file' => 'archivo de plantilla',
            'layout_meta' => 'metadatos de diseño',
            'layout_meta.fold_mm' => 'línea de pliegue',
            'layout_meta.rect_photo' => 'rectángulo para foto',
            'layout_meta.rect_qr' => 'rectángulo para QR',
            'layout_meta.text_blocks' => 'bloques de texto',
            'is_default' => 'predeterminada',
            'version' => 'versión'
        ];
    }
}
