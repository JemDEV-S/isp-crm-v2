<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\DTOs\ProvisionResult;
use Modules\Core\Enums\ProvisionStatus;

interface Provisionable
{
    /**
     * Provision the resource.
     */
    public function provision(): ProvisionResult;

    /**
     * Deprovision the resource.
     */
    public function deprovision(): ProvisionResult;

    /**
     * Get the provision status of the resource.
     */
    public function getProvisionStatus(): ProvisionStatus;
}
