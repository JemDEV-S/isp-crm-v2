<?php

namespace Modules\Subscription\Enums;

enum PlanChangeStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
            self::EXECUTING => 'Ejecutando',
            self::COMPLETED => 'Completado',
            self::FAILED => 'Fallido',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED, self::EXECUTING]);
    }
}
