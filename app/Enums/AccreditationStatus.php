<?php

namespace App\Enums;

enum AccreditationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Borrador',
            self::Submitted => 'Enviada',
            self::UnderReview => 'En revisión',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::Cancelled => 'Cancelada',
            self::Suspended => 'Suspendida',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Submitted => 'blue',
            self::UnderReview => 'yellow',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
            self::Suspended => 'orange',
        };
    }
}
