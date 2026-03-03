<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Enums;

enum WorkOrderType: string
{
    case INSTALLATION = 'installation';
    case REPAIR = 'repair';
    case RELOCATION = 'relocation';
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
    case EQUIPMENT_CHANGE = 'equipment_change';
    case CANCELLATION = 'cancellation';
    case PREVENTIVE = 'preventive';

    public function label(): string
    {
        return match($this) {
            self::INSTALLATION => 'Instalación',
            self::REPAIR => 'Reparación',
            self::RELOCATION => 'Mudanza',
            self::UPGRADE => 'Upgrade',
            self::DOWNGRADE => 'Downgrade',
            self::EQUIPMENT_CHANGE => 'Cambio de Equipo',
            self::CANCELLATION => 'Baja de Servicio',
            self::PREVENTIVE => 'Mantenimiento Preventivo',
        };
    }
}
