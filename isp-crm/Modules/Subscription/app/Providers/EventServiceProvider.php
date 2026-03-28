<?php

declare(strict_types=1);

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\FieldOps\app\Events\InstallationValidated;
use Modules\Subscription\Events\PlanChangeExecuted;
use Modules\Subscription\Events\PlanChangeProvisioningFailed;
use Modules\Subscription\Events\PlanChangeRequested;
use Modules\Subscription\Events\SubscriptionCancelled;
use Modules\Subscription\Events\SubscriptionPlanChanged;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Events\SubscriptionSuspended;
use Modules\Subscription\Listeners\ActivateSubscriptionFromInstallationValidated;
use Modules\Subscription\Listeners\DeprovisionNetworkService;
use Modules\Subscription\Listeners\LogPlanChange;
use Modules\Subscription\Listeners\NotifyPlanChangeFailed;
use Modules\Subscription\Listeners\NotifyPlanChangeRequest;
use Modules\Subscription\Listeners\NotifyPlanChangeResult;
use Modules\Subscription\Listeners\ReactivateNetworkService;
use Modules\Subscription\Listeners\SuspendNetworkService;
use Modules\Subscription\Listeners\UpdateCommercialSnapshot;

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
        PlanChangeRequested::class => [
            NotifyPlanChangeRequest::class,
        ],
        PlanChangeExecuted::class => [
            NotifyPlanChangeResult::class,
        ],
        PlanChangeProvisioningFailed::class => [
            NotifyPlanChangeFailed::class,
        ],
        SubscriptionPlanChanged::class => [
            UpdateCommercialSnapshot::class,
            LogPlanChange::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
