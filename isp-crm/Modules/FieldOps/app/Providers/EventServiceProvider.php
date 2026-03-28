<?php

namespace Modules\FieldOps\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\FieldOps\app\Listeners\EmitInstallationDomainEvents;
use Modules\Workflow\Events\TransitionExecuted;
use Modules\Workflow\Events\WorkflowStarted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WorkflowStarted::class => [
            EmitInstallationDomainEvents::class . '@handleWorkflowStarted',
        ],
        TransitionExecuted::class => [
            EmitInstallationDomainEvents::class . '@handleTransitionExecuted',
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
