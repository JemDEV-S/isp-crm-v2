<?php

declare(strict_types=1);

namespace Modules\Workflow\Contracts;

use Modules\Workflow\Entities\Token;

interface WorkflowableInterface
{
    public function getWorkflowToken(): ?Token;

    public function getWorkflowCode(): string;
}
