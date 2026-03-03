<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum DocumentType: string
{
    case DNI = 'dni';
    case RUC = 'ruc';
    case CE = 'ce';
    case PASSPORT = 'passport';

    public function label(): string
    {
        return match ($this) {
            self::DNI => 'DNI',
            self::RUC => 'RUC',
            self::CE => 'Carné de Extranjería',
            self::PASSPORT => 'Pasaporte',
        };
    }

    public function length(): int
    {
        return match ($this) {
            self::DNI => 8,
            self::RUC => 11,
            self::CE => 12,
            self::PASSPORT => 12,
        };
    }

    public function validationPattern(): string
    {
        return match ($this) {
            self::DNI => '/^[0-9]{8}$/',
            self::RUC => '/^[0-9]{11}$/',
            self::CE => '/^[A-Z0-9]{9,12}$/',
            self::PASSPORT => '/^[A-Z0-9]{6,12}$/',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
