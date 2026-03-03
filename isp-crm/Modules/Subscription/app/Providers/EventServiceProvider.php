<?php

declare(strict_types=1);

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Events\SubscriptionCancelled;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Events\SubscriptionSuspended;
use Modules\Subscription\Listeners\DeprovisionNetworkService;
use Modules\Subscription\Listeners\ProvisionNetworkService;
use Modules\Subscription\Listeners\ReactivateNetworkService;
use Modules\Subscription\Listeners\SuspendNetworkService;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubscriptionActivated::class => [
            ProvisionNetworkService::class,
            // SendActivationEmail::class, (Finance module - to be implemented)
            // GenerateFirstInvoice::class, (Finance module - to be implemented)
        ],
        SubscriptionSuspended::class => [
            SuspendNetworkService::class,
            // NotifyCustomerSuspension::class, (Notification module - to be implemented)
        ],
        SubscriptionReactivated::class => [
            ReactivateNetworkService::class,
            // NotifyCustomerReactivation::class, (Notification module - to be implemented)
        ],
        SubscriptionCancelled::class => [
            DeprovisionNetworkService::class,
            // GenerateFinalInvoice::class, (Finance module - to be implemented)
            // ScheduleEquipmentPickup::class, (FieldOps module - to be implemented)
        ],
    ];

    protected static $shouldDiscoverEvents = true;
}
