<?php

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\Inventory\Listeners\ConfirmMaterialConsumption;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InstallationValidated::class => [
            ConfirmMaterialConsumption::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
