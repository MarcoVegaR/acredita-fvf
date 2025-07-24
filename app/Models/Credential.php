<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'accreditation_request_id',
        'status',
        'employee_snapshot',
        'template_snapshot',
        'event_snapshot',
        'zones_snapshot',
        'qr_code',
        'qr_image_path',
        'credential_image_path',
        'credential_pdf_path',
        'generated_at',
        'expires_at',
        'is_active',
        'printed_at',
        'print_batch_id',
        'error_message',
        'retry_count'
    ];

    protected $casts = [
        'employee_snapshot' => 'array',
        'template_snapshot' => 'array',
        'event_snapshot' => 'array',
        'zones_snapshot' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'printed_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'is_ready',
        'is_expired'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($credential) {
            if (empty($credential->uuid)) {
                $credential->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationship to AccreditationRequest
     */
    public function accreditationRequest(): BelongsTo
    {
        return $this->belongsTo(AccreditationRequest::class);
    }

    /**
     * Check if credential is ready for download
     */
    public function getIsReadyAttribute(): bool
    {
        return $this->status === 'ready' && $this->is_active && !$this->is_expired;
    }

    /**
     * Check if credential is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if credential is expired (public method)
     */
    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    /**
     * Check if credential is valid (ready, active, and not expired)
     */
    public function isValid(): bool
    {
        return $this->status === 'ready' && $this->is_active && !$this->is_expired;
    }

    /**
     * Scope for pending credentials
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for generating credentials
     */
    public function scopeGenerating($query)
    {
        return $query->where('status', 'generating');
    }

    /**
     * Scope for ready credentials
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope for failed credentials
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for expired credentials
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<', now())
              ->orWhere('is_active', false);
        });
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get the formatted error message
     */
    public function getFormattedErrorMessageAttribute(): ?string
    {
        if (!$this->error_message) {
            return null;
        }

        try {
            $decoded = json_decode($this->error_message, true);
            return $decoded['message'] ?? $this->error_message;
        } catch (\Exception $e) {
            return $this->error_message;
        }
    }

    /**
     * Get the print batch that this credential belongs to
     */
    public function printBatch(): BelongsTo
    {
        return $this->belongsTo(PrintBatch::class);
    }

    /**
     * Check if credential has been printed
     */
    public function getIsPrintedAttribute(): bool
    {
        return !is_null($this->printed_at);
    }

    /**
     * Scope for unprinted credentials
     */
    public function scopeUnprinted($query)
    {
        return $query->whereNull('printed_at');
    }

    /**
     * Scope for printed credentials
     */
    public function scopePrinted($query)
    {
        return $query->whereNotNull('printed_at');
    }

    /**
     * Mark credential as printed
     */
    public function markAsPrinted(PrintBatch $printBatch): void
    {
        $this->update([
            'printed_at' => now(),
            'print_batch_id' => $printBatch->id
        ]);
    }
}
