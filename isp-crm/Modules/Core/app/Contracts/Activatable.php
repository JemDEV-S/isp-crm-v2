<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface Activatable
{
    /**
     * Activate the entity.
     */
    public function activate(): void;

    /**
     * Deactivate the entity.
     */
    public function deactivate(): void;

    /**
     * Check if the entity is active.
     */
    public function isActive(): bool;
}
