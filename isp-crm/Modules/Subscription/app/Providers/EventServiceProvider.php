<?php

declare(strict_types=1);

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\Subscription\Events\SubscriptionCancelled;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Events\SubscriptionSuspended;
use Modules\Subscription\Listeners\ActivateSubscriptionFromInstallationValidated;
use Modules\Subscription\Listeners\DeprovisionNetworkService;
use Modules\Subscription\Listeners\ReactivateNetworkService;
use Modules\Subscription\Listeners\SuspendNetworkService;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscriptionSuspended::class => [
            SuspendNetworkService::class,
        ],
        SubscriptionReactivated::class => [
            ReactivateNetworkService::class,
        ],
        SubscriptionCancelled::class => [
            DeprovisionNetworkService::class,
        ],
        InstallationValidated::class => [
            ActivateSubscriptionFromInstallationValidated::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
