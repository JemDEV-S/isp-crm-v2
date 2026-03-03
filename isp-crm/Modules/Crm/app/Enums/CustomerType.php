<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum CustomerType: string
{
    case PERSONAL = 'personal';
    case BUSINESS = 'business';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Persona Natural',
            self::BUSINESS => 'Empresa',
        };
    }

    public function defaultDocumentType(): DocumentType
    {
        return match ($this) {
            self::PERSONAL => DocumentType::DNI,
            self::BUSINESS => DocumentType::RUC,
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
