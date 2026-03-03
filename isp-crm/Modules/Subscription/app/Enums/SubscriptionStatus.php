<?php

declare(strict_types=1);

namespace Modules\Subscription\Enums;

enum SubscriptionStatus: string
{
    case DRAFT = 'draft';
    case PENDING_INSTALLATION = 'pending_installation';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case SUSPENDED_VOLUNTARY = 'suspended_voluntary';
    case CANCELLED = 'cancelled';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PENDING_INSTALLATION => 'Pendiente Instalación',
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::SUSPENDED_VOLUNTARY => 'Suspendido Voluntario',
            self::CANCELLED => 'Cancelado',
            self::TERMINATED => 'Terminado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => '#6B7280',
            self::PENDING_INSTALLATION => '#F59E0B',
            self::ACTIVE => '#10B981',
            self::SUSPENDED => '#EF4444',
            self::SUSPENDED_VOLUNTARY => '#8B5CF6',
            self::CANCELLED => '#DC2626',
            self::TERMINATED => '#374151',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELLED, self::TERMINATED]);
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return in_array($this, [self::SUSPENDED, self::SUSPENDED_VOLUNTARY]);
    }

    public function canBeSuspended(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeReactivated(): bool
    {
        return $this->isSuspended();
    }

    public function canBeCancelled(): bool
    {
        return !$this->isFinal();
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
