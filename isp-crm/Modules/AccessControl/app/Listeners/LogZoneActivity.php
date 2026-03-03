<?php

declare(strict_types=1);

namespace Modules\AccessControl\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\AccessControl\Events\ZoneCreated;
use Modules\AccessControl\Events\ZoneDeleted;
use Modules\AccessControl\Events\ZoneUpdated;

class LogZoneActivity
{
    public function handleZoneCreated(ZoneCreated $event): void
    {
        Log::info('Zona creada', [
            'zone_id' => $event->zone->id,
            'code' => $event->zone->code,
            'created_by' => auth()->id(),
        ]);
    }

    public function handleZoneUpdated(ZoneUpdated $event): void
    {
        Log::info('Zona actualizada', [
            'zone_id' => $event->zone->id,
            'code' => $event->zone->code,
            'updated_by' => auth()->id(),
        ]);
    }

    public function handleZoneDeleted(ZoneDeleted $event): void
    {
        Log::info('Zona eliminada', [
            'zone_id' => $event->zone->id,
            'code' => $event->zone->code,
            'deleted_by' => auth()->id(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            ZoneCreated::class => 'handleZoneCreated',
            ZoneUpdated::class => 'handleZoneUpdated',
            ZoneDeleted::class => 'handleZoneDeleted',
        ];
    }
}
