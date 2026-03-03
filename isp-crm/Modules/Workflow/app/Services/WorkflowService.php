<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Workflow\Contracts\SideEffectActionInterface;
use Modules\Workflow\Entities\Place;
use Modules\Workflow\Entities\SideEffect;
use Modules\Workflow\Entities\Token;
use Modules\Workflow\Entities\Transition;
use Modules\Workflow\Entities\TransitionLog;
use Modules\Workflow\Entities\WorkflowDefinition;
use Modules\Workflow\Enums\TriggerPoint;
use Modules\Workflow\Events\TransitionExecuted;
use Modules\Workflow\Events\WorkflowCompleted;
use Modules\Workflow\Events\WorkflowStarted;
use Modules\Workflow\Exceptions\InvalidTransitionException;
use Modules\Workflow\Exceptions\UnauthorizedTransitionException;
use Modules\Workflow\Exceptions\WorkflowException;

class WorkflowService
{
    public function startWorkflow(string $workflowCode, Model $entity, array $context = []): Token
    {
        $workflow = WorkflowDefinition::where('code', $workflowCode)
            ->where('is_active', true)
            ->firstOrFail();

        $initialPlace = $workflow->getInitialPlace();

        if (!$initialPlace) {
            throw new WorkflowException("El workflow '{$workflowCode}' no tiene un estado inicial definido");
        }

        $token = Token::create([
            'workflow_id' => $workflow->id,
            'tokenable_type' => get_class($entity),
            'tokenable_id' => $entity->getKey(),
            'current_place_id' => $initialPlace->id,
            'context' => $context,
            'started_at' => now(),
        ]);

        event(new WorkflowStarted($token, $workflow));

        return $token;
    }

    public function getToken(Model $entity, ?string $workflowCode = null): ?Token
    {
        $query = Token::forEntity($entity)->active();

        if ($workflowCode) {
            $query->whereHas('workflow', fn($q) => $q->where('code', $workflowCode));
        }

        return $query->latest()->first();
    }

    public function canTransition(Token $token, string $transitionCode): bool
    {
        $transition = $this->findTransition($token, $transitionCode);

        if (!$transition) {
            return false;
        }

        return $this->hasPermissionForTransition($transition);
    }

    public function executeTransition(
        Token $token,
        string $transitionCode,
        array $metadata = [],
        ?string $comment = null
    ): Token {
        $transition = $this->findTransition($token, $transitionCode);

        if (!$transition) {
            throw new InvalidTransitionException($transitionCode, $token->currentPlace->code);
        }

        if (!$this->hasPermissionForTransition($transition)) {
            throw new UnauthorizedTransitionException($transitionCode);
        }

        return DB::transaction(function () use ($token, $transition, $metadata, $comment) {
            $fromPlace = $token->currentPlace;
            $toPlace = $transition->toPlace;

            // Ejecutar side effects BEFORE
            $this->executeSideEffects($transition, TriggerPoint::BEFORE, $token);

            // Ejecutar side effects ON_EXIT
            $this->executeSideEffects($transition, TriggerPoint::ON_EXIT, $token);

            // Actualizar token
            $token->update(['current_place_id' => $toPlace->id]);

            // Registrar en log
            TransitionLog::create([
                'token_id' => $token->id,
                'transition_id' => $transition->id,
                'from_place_id' => $fromPlace->id,
                'to_place_id' => $toPlace->id,
                'user_id' => auth()->id(),
                'metadata' => $metadata,
                'comment' => $comment,
                'executed_at' => now(),
            ]);

            // Ejecutar side effects ON_ENTER
            $this->executeSideEffects($transition, TriggerPoint::ON_ENTER, $token);

            // Ejecutar side effects AFTER
            $this->executeSideEffects($transition, TriggerPoint::AFTER, $token);

            // Verificar si el workflow ha terminado
            $token = $token->fresh(['currentPlace', 'workflow']);

            if ($token->currentPlace->isFinal()) {
                $token->update(['completed_at' => now()]);
                event(new WorkflowCompleted($token, $token->workflow));
            }

            event(new TransitionExecuted($token, $transition, $fromPlace, $toPlace, $metadata));

            return $token;
        });
    }

    public function getAvailableTransitions(Token $token): Collection
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        $roleIds = $user->roles->pluck('id');

        return Transition::where('workflow_id', $token->workflow_id)
            ->where(function ($query) use ($token) {
                $query->where('from_place_id', $token->current_place_id)
                      ->orWhere('from_any', true);
            })
            ->whereHas('permissions', fn($q) => $q->whereIn('role_id', $roleIds))
            ->with(['toPlace', 'fromPlace'])
            ->get();
    }

    public function getPlaceHistory(Token $token): Collection
    {
        return $token->logs()
            ->with(['fromPlace', 'toPlace', 'user', 'transition'])
            ->orderBy('executed_at', 'desc')
            ->get();
    }

    public function getCurrentPlaceInfo(Token $token): array
    {
        $place = $token->currentPlace;

        return [
            'code' => $place->code,
            'name' => $place->name,
            'color' => $place->color,
            'is_initial' => $place->is_initial,
            'is_final' => $place->is_final,
        ];
    }

    protected function findTransition(Token $token, string $transitionCode): ?Transition
    {
        return Transition::where('workflow_id', $token->workflow_id)
            ->where('code', $transitionCode)
            ->where(function ($query) use ($token) {
                $query->where('from_place_id', $token->current_place_id)
                      ->orWhere('from_any', true);
            })
            ->first();
    }

    protected function hasPermissionForTransition(Transition $transition): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Superadmin puede ejecutar cualquier transición
        if ($user->hasRole('superadmin')) {
            return true;
        }

        $roleIds = $user->roles->pluck('id');

        return $transition->permissions()
            ->whereIn('role_id', $roleIds)
            ->exists();
    }

    protected function executeSideEffects(Transition $transition, TriggerPoint $triggerPoint, Token $token): void
    {
        $sideEffects = $transition->sideEffects()
            ->active()
            ->byTriggerPoint($triggerPoint)
            ->orderBy('order')
            ->get();

        foreach ($sideEffects as $sideEffect) {
            $this->executeSideEffect($sideEffect, $token, $transition);
        }
    }

    protected function executeSideEffect(SideEffect $sideEffect, Token $token, Transition $transition): void
    {
        $actionClass = $sideEffect->action_class;

        if (!class_exists($actionClass)) {
            return;
        }

        $action = app($actionClass);

        if ($action instanceof SideEffectActionInterface) {
            $action->execute($token, $transition, $sideEffect->parameters ?? []);
        }
    }
}
