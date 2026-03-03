<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum WarehouseType: string
{
    case CENTRAL = 'central';
    case BRANCH = 'branch';
    case MOBILE = 'mobile';

    public function label(): string
    {
        return match($this) {
            self::CENTRAL => 'Almacén Central',
            self::BRANCH => 'Sucursal',
            self::MOBILE => 'Bodega Móvil',
        };
    }

    public function requiresUser(): bool
    {
        return $this === self::MOBILE;
    }
}
