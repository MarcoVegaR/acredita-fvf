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
        'return_reason',
        'suspended_at',
        'suspended_by',
        'suspension_reason'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'returned_at' => 'datetime',
        'suspended_at' => 'datetime',
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

    /**
     * Relación con credencial
     */
    public function credential()
    {
        return $this->hasOne(Credential::class);
    }

    /**
     * Usuario que revisó la solicitud
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Usuario que aprobó la solicitud
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Usuario que rechazó la solicitud
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Usuario que devolvió la solicitud
     */
    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Usuario que suspendió la solicitud
     */
    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
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

    /**
     * Generar timeline de eventos para la solicitud
     * 
     * @return array
     */
    public function getTimeline(): array
    {
        $timeline = [];
        
        // 1. Creación de la solicitud
        $timeline[] = [
            'type' => 'created',
            'timestamp' => $this->created_at,
            'user' => $this->creator,
            'message' => 'Solicitud de acreditación creada',
            'details' => $this->comments,
            'icon' => 'UserPlus',
            'color' => 'blue'
        ];
        
        // 2. Envío de la solicitud
        if ($this->requested_at) {
            $timeline[] = [
                'type' => 'submitted',
                'timestamp' => $this->requested_at,
                'user' => $this->creator,
                'message' => 'Solicitud enviada para revisión',
                'details' => null,
                'icon' => 'Send',
                'color' => 'indigo'
            ];
        }
        
        // 3. Revisión del área
        if ($this->reviewed_at) {
            $timeline[] = [
                'type' => 'reviewed',
                'timestamp' => $this->reviewed_at,
                'user' => $this->reviewedBy,
                'message' => 'Solicitud revisada y aprobada por el área',
                'details' => $this->review_comments,
                'icon' => 'Eye',
                'color' => 'yellow'
            ];
        }
        
        // 4. Aprobación final
        if ($this->approved_at) {
            $timeline[] = [
                'type' => 'approved',
                'timestamp' => $this->approved_at,
                'user' => $this->approvedBy,
                'message' => 'Solicitud aprobada - Credencial habilitada',
                'details' => null,
                'icon' => 'CheckCircle',
                'color' => 'green'
            ];
        }
        
        // 5. Rechazo
        if ($this->rejected_at) {
            $timeline[] = [
                'type' => 'rejected',
                'timestamp' => $this->rejected_at,
                'user' => $this->rejectedBy,
                'message' => 'Solicitud rechazada',
                'details' => $this->rejection_reason,
                'icon' => 'XCircle',
                'color' => 'red'
            ];
        }
        
        // 6. Devolución a borrador
        if ($this->returned_at) {
            $timeline[] = [
                'type' => 'returned',
                'timestamp' => $this->returned_at,
                'user' => $this->returnedBy,
                'message' => 'Solicitud devuelta para corrección',
                'details' => $this->return_reason,
                'icon' => 'RotateCcw',
                'color' => 'orange'
            ];
        }
        
        // 7. Suspensión
        if ($this->suspended_at) {
            $timeline[] = [
                'type' => 'suspended',
                'timestamp' => $this->suspended_at,
                'user' => $this->suspendedBy,
                'message' => 'Solicitud suspendida',
                'details' => $this->suspension_reason,
                'icon' => 'Pause',
                'color' => 'gray'
            ];
        }
        
        // Ordenar cronológicamente (más reciente primero)
        usort($timeline, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        
        return $timeline;
    }
}
