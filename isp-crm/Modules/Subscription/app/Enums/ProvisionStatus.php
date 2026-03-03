<?php

declare(strict_types=1);

namespace Modules\Subscription\Enums;

enum ProvisionStatus: string
{
    case PENDING = 'pending';
    case PROVISIONING = 'provisioning';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case FAILED = 'failed';
    case DEPROVISIONED = 'deprovisioned';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PROVISIONING => 'Aprovisionando',
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::FAILED => 'Fallido',
            self::DEPROVISIONED => 'Desaprovisionado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => '#F59E0B',
            self::PROVISIONING => '#3B82F6',
            self::ACTIVE => '#10B981',
            self::SUSPENDED => '#EF4444',
            self::FAILED => '#DC2626',
            self::DEPROVISIONED => '#6B7280',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
