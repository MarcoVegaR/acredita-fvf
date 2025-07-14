<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'event_id',
        'name',
        'file_path',
        'layout_meta',
        'version',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'layout_meta' => 'array',
            'is_default' => 'boolean',
            'version' => 'integer',
        ];
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the event that owns the template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
    
    /**
     * Validates that the layout_meta has the minimum required fields.
     * Returns true if valid, or an array of missing required fields if invalid.
     *
     * @return bool|array
     */
    public function validateLayoutMeta()
    {
        // Lista de campos mínimos requeridos
        $requiredFields = [
            'fold_mm',
            'rect_photo',
            'rect_qr'
        ];
        
        $layoutMeta = $this->layout_meta;
        
        if (!is_array($layoutMeta)) {
            return ['error' => 'layout_meta must be an array'];
        }
        
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($layoutMeta[$field])) {
                $missingFields[] = $field;
            }
        }
        
        return empty($missingFields) ? true : $missingFields;
    }
}
