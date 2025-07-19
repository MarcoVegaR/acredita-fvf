<?php

namespace App\Models;

use App\Enums\AccreditationStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccreditationRequest extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'event_id',
        'status',
        'requested_at',
        'comments',
        'created_by',
        // Campos de flujo de trabajo
        'reviewed_at',
        'reviewed_by',
        'review_comments',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'returned_at',
        'returned_by',
        'return_reason'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'returned_at' => 'datetime',
        'status' => AccreditationStatus::class
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'accreditation_request_zone', 'request_id', 'zone_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft()
    {
        return $this->status === AccreditationStatus::Draft;
    }

    public function isSubmitted()
    {
        return $this->status === AccreditationStatus::Submitted;
    }

    /**
     * Scope para filtrar por estatus
     */
    public function scopeWithStatus($query, AccreditationStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar por evento
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}
