<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\ScopeService;

trait HasScope
{
    /**
     * Boot the HasScope trait for a model.
     */
    protected static function bootHasScope(): void
    {
        static::addGlobalScope('user_scope', function (Builder $builder) {
            $user = auth()->user();

            // Sin usuario o superadmin: sin restricciones
            if (!$user || $user->hasRole('superadmin')) {
                return;
            }

            $scopeService = app(ScopeService::class);
            $scopeService->applyScope($builder, $user, static::class);
        });
    }

    /**
     * Método para deshabilitar scope temporalmente.
     */
    public static function withoutUserScope(): Builder
    {
        return static::withoutGlobalScope('user_scope');
    }

    /**
     * Get the scope constraints for this model.
     * Override this method in your model to define custom scope constraints.
     *
     * @return array
     */
    public static function getScopeConstraints(): array
    {
        return [];
    }
}
