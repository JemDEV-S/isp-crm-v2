<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum MovementType: string
{
    case PURCHASE = 'purchase';           // Entrada por compra
    case SALE = 'sale';                   // Salida por venta
    case TRANSFER = 'transfer';           // Entre almacenes
    case ADJUSTMENT_IN = 'adjustment_in'; // Ajuste positivo
    case ADJUSTMENT_OUT = 'adjustment_out'; // Ajuste negativo
    case INSTALLATION = 'installation';   // Consumo en instalación
    case RETURN = 'return';               // Devolución de cliente
    case DAMAGE = 'damage';               // Baja por daño

    public function label(): string
    {
        return match($this) {
            self::PURCHASE => 'Compra',
            self::SALE => 'Venta',
            self::TRANSFER => 'Transferencia',
            self::ADJUSTMENT_IN => 'Ajuste Positivo',
            self::ADJUSTMENT_OUT => 'Ajuste Negativo',
            self::INSTALLATION => 'Instalación',
            self::RETURN => 'Devolución',
            self::DAMAGE => 'Baja por Daño',
        };
    }

    public function affectsStock(): bool
    {
        return match($this) {
            self::PURCHASE, self::ADJUSTMENT_IN, self::RETURN => true,
            self::SALE, self::ADJUSTMENT_OUT, self::INSTALLATION, self::DAMAGE => true,
            self::TRANSFER => false, // Afecta ambos almacenes
        };
    }

    public function isIncoming(): bool
    {
        return match($this) {
            self::PURCHASE, self::ADJUSTMENT_IN, self::RETURN => true,
            default => false,
        };
    }

    public function isOutgoing(): bool
    {
        return match($this) {
            self::SALE, self::ADJUSTMENT_OUT, self::INSTALLATION, self::DAMAGE => true,
            default => false,
        };
    }

    public function badgeVariant(): string
    {
        return match($this) {
            self::PURCHASE, self::RETURN, self::ADJUSTMENT_IN => 'success',
            self::SALE, self::INSTALLATION => 'primary',
            self::TRANSFER => 'info',
            self::ADJUSTMENT_OUT, self::DAMAGE => 'danger',
        };
    }
}
