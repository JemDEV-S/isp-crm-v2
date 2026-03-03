<?php

declare(strict_types=1);

namespace Modules\AccessControl\Enums;

enum RoleCode: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case TECHNICIAN = 'technician';
    case SALES = 'sales';
    case BILLING = 'billing';
    case SUPPORT = 'support';

    /**
     * Get the label for the role.
     */
    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Administrador',
            self::ADMIN => 'Administrador',
            self::SUPERVISOR => 'Supervisor',
            self::TECHNICIAN => 'Técnico',
            self::SALES => 'Ventas',
            self::BILLING => 'Facturación',
            self::SUPPORT => 'Soporte',
        };
    }

    /**
     * Check if this role is a system role (cannot be deleted).
     */
    public function isSystem(): bool
    {
        return in_array($this, [self::SUPERADMIN, self::ADMIN]);
    }

    /**
     * Get all role codes as array.
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
