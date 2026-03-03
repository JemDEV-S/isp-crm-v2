<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum ProvisionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case FAILED = 'failed';
    case DEPROVISIONED = 'deprovisioned';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::FAILED => 'Fallido',
            self::DEPROVISIONED => 'Desprovisionado',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::FAILED => 'danger',
            self::DEPROVISIONED => 'default',
        };
    }
}
