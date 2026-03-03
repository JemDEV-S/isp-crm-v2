<?php

declare(strict_types=1);

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\DTOs\CreatePlanDTO;
use Modules\Catalog\DTOs\UpdatePlanDTO;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Events\PlanCreated;
use Modules\Catalog\Events\PlanDeleted;
use Modules\Catalog\Events\PlanUpdated;
use Modules\Core\Services\BaseService;

class PlanService extends BaseService
{
    /**
     * Get all plans with optional filters.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Plan::query()
            ->with(['parameters', 'promotions', 'addons']);

        if (isset($filters['technology'])) {
            $query->where('technology', $filters['technology']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', $filters['is_visible']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('download_speed')->get();
    }

    /**
     * Get paginated plans.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Plan::query()
            ->with(['parameters', 'promotions', 'addons']);

        if (isset($filters['technology'])) {
            $query->where('technology', $filters['technology']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', $filters['is_visible']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('download_speed')->paginate($perPage);
    }

    /**
     * Get public plans for customer display.
     */
    public function getPublicPlans(): Collection
    {
        return Plan::query()
            ->public()
            ->with(['activePromotions', 'includedAddons'])
            ->orderBy('download_speed')
            ->get();
    }

    /**
     * Find a plan by ID.
     */
    public function findById(int $id): ?Plan
    {
        return Plan::with(['parameters', 'promotions', 'addons'])->find($id);
    }

    /**
     * Find a plan by code.
     */
    public function findByCode(string $code): ?Plan
    {
        return Plan::with(['parameters', 'promotions', 'addons'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Create a new plan.
     */
    public function create(CreatePlanDTO $dto): Plan
    {
        return $this->transaction(function () use ($dto) {
            $plan = Plan::create([
                ...$dto->toArray(),
                'uuid' => Str::uuid()->toString(),
                'created_by' => auth()->id(),
            ]);

            // Create parameters
            foreach ($dto->parameters as $key => $value) {
                $plan->setParameter($key, $value);
            }

            // Attach promotions
            if (!empty($dto->promotionIds)) {
                $plan->promotions()->attach($dto->promotionIds);
            }

            // Attach addons
            if (!empty($dto->addonIds)) {
                $plan->addons()->attach($dto->addonIds);
            }

            $this->dispatchEvent(new PlanCreated($plan));

            return $plan->fresh(['parameters', 'promotions', 'addons']);
        });
    }

    /**
     * Update a plan.
     */
    public function update(Plan $plan, UpdatePlanDTO $dto): Plan
    {
        return $this->transaction(function () use ($plan, $dto) {
            $oldData = $plan->toArray();

            $updateData = $dto->toArray();
            if (!empty($updateData)) {
                $updateData['updated_by'] = auth()->id();
                $plan->update($updateData);
            }

            // Update parameters if provided
            if ($dto->parameters !== null) {
                $plan->parameters()->delete();
                foreach ($dto->parameters as $key => $value) {
                    $plan->setParameter($key, $value);
                }
            }

            // Sync promotions if provided
            if ($dto->promotionIds !== null) {
                $plan->promotions()->sync($dto->promotionIds);
            }

            // Sync addons if provided
            if ($dto->addonIds !== null) {
                $plan->addons()->sync($dto->addonIds);
            }

            $this->dispatchEvent(new PlanUpdated($plan, $oldData));

            return $plan->fresh(['parameters', 'promotions', 'addons']);
        });
    }

    /**
     * Delete a plan.
     */
    public function delete(Plan $plan): bool
    {
        return $this->transaction(function () use ($plan) {
            $this->dispatchEvent(new PlanDeleted($plan));

            return $plan->delete();
        });
    }

    /**
     * Activate a plan.
     */
    public function activate(Plan $plan): Plan
    {
        $plan->activate();
        return $plan->fresh();
    }

    /**
     * Deactivate a plan.
     */
    public function deactivate(Plan $plan): Plan
    {
        $plan->deactivate();
        return $plan->fresh();
    }

    /**
     * Toggle plan visibility.
     */
    public function toggleVisibility(Plan $plan): Plan
    {
        $plan->update(['is_visible' => !$plan->is_visible]);
        return $plan->fresh();
    }

    /**
     * Attach promotion to plan.
     */
    public function attachPromotion(Plan $plan, int $promotionId): void
    {
        $plan->promotions()->attach($promotionId);
    }

    /**
     * Detach promotion from plan.
     */
    public function detachPromotion(Plan $plan, int $promotionId): void
    {
        $plan->promotions()->detach($promotionId);
    }

    /**
     * Attach addon to plan.
     */
    public function attachAddon(Plan $plan, int $addonId, bool $isIncluded = false): void
    {
        $plan->addons()->attach($addonId, ['is_included' => $isIncluded]);
    }

    /**
     * Detach addon from plan.
     */
    public function detachAddon(Plan $plan, int $addonId): void
    {
        $plan->addons()->detach($addonId);
    }

    /**
     * Duplicate a plan.
     */
    public function duplicate(Plan $plan, string $newCode, string $newName): Plan
    {
        return $this->transaction(function () use ($plan, $newCode, $newName) {
            $newPlan = $plan->replicate(['uuid', 'code', 'name']);
            $newPlan->uuid = Str::uuid()->toString();
            $newPlan->code = $newCode;
            $newPlan->name = $newName;
            $newPlan->created_by = auth()->id();
            $newPlan->save();

            // Copy parameters
            foreach ($plan->parameters as $parameter) {
                $newPlan->setParameter($parameter->key, $parameter->value, $parameter->display_name);
            }

            // Copy promotions
            $newPlan->promotions()->attach($plan->promotions->pluck('id'));

            // Copy addons with pivot data
            foreach ($plan->addons as $addon) {
                $newPlan->addons()->attach($addon->id, ['is_included' => $addon->pivot->is_included]);
            }

            $this->dispatchEvent(new PlanCreated($newPlan));

            return $newPlan->fresh(['parameters', 'promotions', 'addons']);
        });
    }
}
