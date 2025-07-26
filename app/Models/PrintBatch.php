<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Area;
use App\Models\Provider;

class PrintBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'event_id',
        'area_id', 
        'provider_id',
        'generated_by',
        'status',
        'filters_snapshot',
        'total_credentials',
        'processed_credentials',
        'pdf_path',
        'started_at',
        'finished_at',
        'error_message',
        'retry_count'
    ];

    protected $casts = [
        'area_id' => 'array',
        'provider_id' => 'array', 
        'filters_snapshot' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime'
    ];

    protected $appends = [
        'is_ready',
        'is_processing',
        'progress_percentage',
        'duration',
        'areas',
        'providers',
        'generated_by_user'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relaciones
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // NOTA: area_id y provider_id ahora son arrays JSON, no foreign keys
    // Las relaciones se obtienen mediante métodos helpers
    
    /**
     * Obtener las áreas asociadas al lote
     * Para uso en API/JSON
     * @return array
     */
    public function getAreasAttribute()
    {
        // Si area_id es null o vacío, significa que el lote incluye todas las áreas
        if (empty($this->area_id)) {
            return [
                [
                    'id' => null,
                    'name' => 'Todas las áreas'
                ]
            ];
        }
        
        // Asegurarse de que area_id sea un array
        $areaIds = is_array($this->area_id) ? $this->area_id : [$this->area_id];
        
        // Obtener las áreas específicas
        $areas = Area::whereIn('id', $areaIds)
            ->select(['id', 'name'])
            ->get();
            
        return $areas->map(function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name
            ];
        })->toArray();
    }
    
    /**
     * Obtener las áreas como colección de modelos (para uso interno)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function areasCollection()
    {
        if (empty($this->area_id)) {
            return collect([]);
        }
        
        $areaIds = is_array($this->area_id) ? $this->area_id : [$this->area_id];
        return Area::whereIn('id', $areaIds)->get();
    }
    
    /**
     * Obtener los proveedores asociados al lote
     * Para uso en API/JSON
     * @return array
     */
    public function getProvidersAttribute()
    {
        // Si provider_id es null o vacío, significa que el lote incluye todos los proveedores
        if (empty($this->provider_id)) {
            return [
                [
                    'id' => null,
                    'name' => 'Todos los proveedores'
                ]
            ];
        }
        
        // Asegurarse de que provider_id sea un array
        $providerIds = is_array($this->provider_id) ? $this->provider_id : [$this->provider_id];
        
        // Obtener los proveedores específicos
        $providers = Provider::whereIn('id', $providerIds)
            ->select(['id', 'name'])
            ->get();
            
        return $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->name
            ];
        })->toArray();
    }
    
    /**
     * Obtener los proveedores como colección de modelos (para uso interno)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function providersCollection()
    {
        if (empty($this->provider_id)) {
            return collect([]);
        }
        
        $providerIds = is_array($this->provider_id) ? $this->provider_id : [$this->provider_id];
        return Provider::whereIn('id', $providerIds)->get();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
    
    /**
     * Obtener el usuario que generó el lote, formateado para API/JSON
     * @return array
     */
    public function getGeneratedByUserAttribute()
    {
        $user = $this->generatedBy()->select(['id', 'name', 'email'])->first();
        
        if (!$user) {
            return [
                'id' => null,
                'name' => 'Sistema',
                'email' => null
            ];
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    // Scopes
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['queued', 'processing']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['archived']);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    // Accessors
    public function getIsReadyAttribute(): bool
    {
        return $this->status === 'ready';
    }

    public function getIsProcessingAttribute(): bool
    {
        return in_array($this->status, ['queued', 'processing']);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_credentials === 0) {
            return 0;
        }
        
        return round(($this->processed_credentials / $this->total_credentials) * 100, 1);
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }
        
        return $this->finished_at->diffInSeconds($this->started_at);
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = intval($this->duration / 60);
        $seconds = $this->duration % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'queued' => ['label' => 'En Cola', 'color' => 'blue'],
            'processing' => ['label' => 'Procesando', 'color' => 'yellow'],
            'ready' => ['label' => 'Listo', 'color' => 'green'],
            'failed' => ['label' => 'Fallido', 'color' => 'red'],
            'archived' => ['label' => 'Archivado', 'color' => 'gray'],
            default => ['label' => 'Desconocido', 'color' => 'gray']
        };
    }

    // Métodos de utilidad
    public function canBeDownloaded(): bool
    {
        return $this->status === 'ready' && !empty($this->pdf_path);
    }

    public function canBeRetried(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null
        ]);
    }

    public function markAsReady(string $pdfPath): void
    {
        $this->update([
            'status' => 'ready',
            'pdf_path' => $pdfPath,
            'finished_at' => now(),
            'processed_credentials' => $this->total_credentials
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'finished_at' => now()
        ]);
    }

    public function updateProgress(int $processedCount): void
    {
        $this->update([
            'processed_credentials' => $processedCount
        ]);
    }
}
