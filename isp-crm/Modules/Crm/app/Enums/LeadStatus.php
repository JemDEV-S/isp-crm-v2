<?php

declare(strict_types=1);

namespace Modules\Crm\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case PROPOSAL_SENT = 'proposal_sent';
    case NEGOTIATING = 'negotiating';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nuevo',
            self::CONTACTED => 'Contactado',
            self::QUALIFIED => 'Calificado',
            self::PROPOSAL_SENT => 'Propuesta Enviada',
            self::NEGOTIATING => 'Negociando',
            self::WON => 'Ganado',
            self::LOST => 'Perdido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => '#3B82F6',
            self::CONTACTED => '#8B5CF6',
            self::QUALIFIED => '#F59E0B',
            self::PROPOSAL_SENT => '#06B6D4',
            self::NEGOTIATING => '#EC4899',
            self::WON => '#10B981',
            self::LOST => '#EF4444',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::WON, self::LOST]);
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
