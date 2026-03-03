<?php

declare(strict_types=1);

namespace Modules\Network\Enums;

enum NodeType: string
{
    case TOWER = 'tower';
    case DATACENTER = 'datacenter';
    case POP = 'pop';
    case CABINET = 'cabinet';

    /**
     * Get the label for the node type.
     */
    public function label(): string
    {
        return match($this) {
            self::TOWER => 'Torre',
            self::DATACENTER => 'Centro de Datos',
            self::POP => 'Punto de Presencia (POP)',
            self::CABINET => 'Gabinete',
        };
    }
}
