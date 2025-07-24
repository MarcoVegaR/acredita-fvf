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
        'providers'
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
     */
    public function getAreasAttribute()
    {
        if (empty($this->area_id)) {
            return collect();
        }
        return Area::whereIn('id', $this->area_id)->get();
    }
    
    /**
     * Obtener los proveedores asociados al lote
     */
    public function getProvidersAttribute()
    {
        if (empty($this->provider_id)) {
            return collect();
        }
        return Provider::whereIn('id', $this->provider_id)->get();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
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
