<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Enums;

enum ExceptionType: string
{
    case CUSTOMER_ABSENT = 'customer_absent';
    case NO_MATERIALS = 'no_materials';
    case NO_FEASIBILITY = 'no_feasibility';
    case FAILED_PROVISION = 'failed_provision';
    case FAILED_VALIDATION = 'failed_validation';
    case EXTERNAL_ISSUE = 'external_issue';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER_ABSENT => 'Cliente ausente',
            self::NO_MATERIALS => 'Sin materiales',
            self::NO_FEASIBILITY => 'Sin factibilidad',
            self::FAILED_PROVISION => 'Aprovisionamiento fallido',
            self::FAILED_VALIDATION => 'Validacion fallida',
            self::EXTERNAL_ISSUE => 'Problema externo',
        };
    }
}
