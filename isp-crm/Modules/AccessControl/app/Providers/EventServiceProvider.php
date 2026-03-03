<?php

declare(strict_types=1);

namespace Modules\AccessControl\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\AccessControl\Listeners\LogRoleActivity;
use Modules\AccessControl\Listeners\LogUserActivity;
use Modules\AccessControl\Listeners\LogZoneActivity;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, string>
     */
    protected $subscribe = [
        LogUserActivity::class,
        LogRoleActivity::class,
        LogZoneActivity::class,
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
