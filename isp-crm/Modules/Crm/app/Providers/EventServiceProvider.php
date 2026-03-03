<?php

declare(strict_types=1);

namespace Modules\Crm\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Crm\Events\CustomerCreated;
use Modules\Crm\Events\LeadConverted;
use Modules\Crm\Events\LeadCreated;
use Modules\Crm\Listeners\LogLeadActivity;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LeadCreated::class => [
            // SendWelcomeNotification::class, (Notification module - to be implemented)
            // AssignToSalesperson::class, (Automation - to be implemented)
        ],
        LeadConverted::class => [
            // NotifySalesTeam::class, (Notification module - to be implemented)
            // UpdateStatistics::class, (Analytics - to be implemented)
        ],
        CustomerCreated::class => [
            // CreateWallet::class, (Finance module - to be implemented)
            // SendWelcomeEmail::class, (Notification module - to be implemented)
        ],
    ];

    protected static $shouldDiscoverEvents = true;
}
