<?php

namespace Modules\Finance\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Crm\Events\CustomerCreated;
use Modules\Finance\Listeners\CreateWalletForCustomer;
use Modules\Finance\Listeners\GenerateInitialInvoice;
use Modules\Subscription\Events\SubscriptionActivated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CustomerCreated::class => [
            CreateWalletForCustomer::class,
        ],
        SubscriptionActivated::class => [
            GenerateInitialInvoice::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
