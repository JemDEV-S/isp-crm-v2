<?php

declare(strict_types=1);

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\DTOs\CreatePromotionDTO;
use Modules\Catalog\DTOs\UpdatePromotionDTO;
use Modules\Catalog\Entities\Promotion;
use Modules\Catalog\Events\PromotionCreated;
use Modules\Catalog\Events\PromotionDeleted;
use Modules\Catalog\Events\PromotionUpdated;
use Modules\Core\Services\BaseService;

class PromotionService extends BaseService
{
    /**
     * Get all promotions with optional filters.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Promotion::query()->with(['plans']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['valid'])) {
            $query->valid();
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('valid_from', 'desc')->get();
    }

    /**
     * Get paginated promotions.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Promotion::query()->with(['plans']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['valid'])) {
            $query->valid();
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get valid promotions.
     */
    public function getValidPromotions(): Collection
    {
        return Promotion::query()
            ->valid()
            ->with(['plans'])
            ->orderBy('discount_value', 'desc')
            ->get();
    }

    /**
     * Find a promotion by ID.
     */
    public function findById(int $id): ?Promotion
    {
        return Promotion::with(['plans'])->find($id);
    }

    /**
     * Find a promotion by code.
     */
    public function findByCode(string $code): ?Promotion
    {
        return Promotion::with(['plans'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Create a new promotion.
     */
    public function create(CreatePromotionDTO $dto): Promotion
    {
        return $this->transaction(function () use ($dto) {
            $promotion = Promotion::create([
                ...$dto->toArray(),
                'uuid' => Str::uuid()->toString(),
                'created_by' => auth()->id(),
            ]);

            // Attach plans
            if (!empty($dto->planIds)) {
                $promotion->plans()->attach($dto->planIds);
            }

            $this->dispatchEvent(new PromotionCreated($promotion));

            return $promotion->fresh(['plans']);
        });
    }

    /**
     * Update a promotion.
     */
    public function update(Promotion $promotion, UpdatePromotionDTO $dto): Promotion
    {
        return $this->transaction(function () use ($promotion, $dto) {
            $oldData = $promotion->toArray();

            $updateData = $dto->toArray();
            if (!empty($updateData)) {
                $updateData['updated_by'] = auth()->id();
                $promotion->update($updateData);
            }

            // Sync plans if provided
            if ($dto->planIds !== null) {
                $promotion->plans()->sync($dto->planIds);
            }

            $this->dispatchEvent(new PromotionUpdated($promotion, $oldData));

            return $promotion->fresh(['plans']);
        });
    }

    /**
     * Delete a promotion.
     */
    public function delete(Promotion $promotion): bool
    {
        return $this->transaction(function () use ($promotion) {
            $this->dispatchEvent(new PromotionDeleted($promotion));

            return $promotion->delete();
        });
    }

    /**
     * Activate a promotion.
     */
    public function activate(Promotion $promotion): Promotion
    {
        $promotion->activate();
        return $promotion->fresh();
    }

    /**
     * Deactivate a promotion.
     */
    public function deactivate(Promotion $promotion): Promotion
    {
        $promotion->deactivate();
        return $promotion->fresh();
    }

    /**
     * Apply promotion (increment usage).
     */
    public function applyPromotion(Promotion $promotion): bool
    {
        if (!$promotion->isValid()) {
            return false;
        }

        $promotion->incrementUses();
        return true;
    }

    /**
     * Revert promotion application (decrement usage).
     */
    public function revertPromotion(Promotion $promotion): void
    {
        $promotion->decrementUses();
    }

    /**
     * Get promotions for a specific plan.
     */
    public function getPromotionsForPlan(int $planId): Collection
    {
        return Promotion::query()
            ->valid()
            ->whereHas('plans', function ($query) use ($planId) {
                $query->where('plans.id', $planId);
            })
            ->get();
    }

    /**
     * Validate promotion code.
     */
    public function validateCode(string $code, ?int $planId = null): ?Promotion
    {
        $query = Promotion::query()
            ->where('code', $code)
            ->valid();

        if ($planId) {
            $query->whereHas('plans', function ($q) use ($planId) {
                $q->where('plans.id', $planId);
            });
        }

        return $query->first();
    }
}
