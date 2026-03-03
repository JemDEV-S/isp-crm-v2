<?php

declare(strict_types=1);

namespace Modules\AccessControl\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;
use Modules\AccessControl\Policies\RolePolicy;
use Modules\AccessControl\Policies\UserPolicy;
use Modules\AccessControl\Policies\ZonePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Zone::class => ZonePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            return null;
        });
    }
}
