<?php

declare(strict_types=1);

namespace Modules\Workflow\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Services\WorkflowService;

trait HasWorkflow
{
    public function workflowTokens(): MorphMany
    {
        return $this->morphMany(Token::class, 'tokenable');
    }

    public function getActiveWorkflowToken(?string $workflowCode = null): ?Token
    {
        $query = $this->workflowTokens()->active();

        if ($workflowCode) {
            $query->whereHas('workflow', fn($q) => $q->where('code', $workflowCode));
        }

        return $query->latest()->first();
    }

    public function startWorkflow(string $workflowCode, array $context = []): Token
    {
        return app(WorkflowService::class)->startWorkflow($workflowCode, $this, $context);
    }

    public function executeTransition(string $transitionCode, array $metadata = [], ?string $comment = null): Token
    {
        $token = $this->getActiveWorkflowToken();

        if (!$token) {
            throw new \RuntimeException('No hay un workflow activo para esta entidad');
        }

        return app(WorkflowService::class)->executeTransition($token, $transitionCode, $metadata, $comment);
    }

    public function canTransition(string $transitionCode): bool
    {
        $token = $this->getActiveWorkflowToken();

        if (!$token) {
            return false;
        }

        return app(WorkflowService::class)->canTransition($token, $transitionCode);
    }

    public function getAvailableTransitions(): Collection
    {
        $token = $this->getActiveWorkflowToken();

        if (!$token) {
            return collect();
        }

        return app(WorkflowService::class)->getAvailableTransitions($token);
    }

    public function getCurrentWorkflowPlace(): ?string
    {
        $token = $this->getActiveWorkflowToken();

        return $token?->getCurrentPlaceCode();
    }

    public function getCurrentWorkflowPlaceName(): ?string
    {
        $token = $this->getActiveWorkflowToken();

        return $token?->getCurrentPlaceName();
    }

    public function isWorkflowCompleted(): bool
    {
        $token = $this->getActiveWorkflowToken();

        return $token?->isCompleted() ?? true;
    }

    public function isInWorkflowPlace(string $placeCode): bool
    {
        return $this->getCurrentWorkflowPlace() === $placeCode;
    }

    public function getWorkflowHistory(): Collection
    {
        $token = $this->getActiveWorkflowToken();

        if (!$token) {
            return collect();
        }

        return app(WorkflowService::class)->getPlaceHistory($token);
    }
}
