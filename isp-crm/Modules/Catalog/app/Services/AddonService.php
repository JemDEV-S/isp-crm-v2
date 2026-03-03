<?php

declare(strict_types=1);

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\DTOs\CreateAddonDTO;
use Modules\Catalog\DTOs\UpdateAddonDTO;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Events\AddonCreated;
use Modules\Catalog\Events\AddonDeleted;
use Modules\Catalog\Events\AddonUpdated;
use Modules\Core\Services\BaseService;

class AddonService extends BaseService
{
    /**
     * Get all addons with optional filters.
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Addon::query()->with(['plans']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_recurring'])) {
            $query->where('is_recurring', $filters['is_recurring']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get paginated addons.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Addon::query()->with(['plans']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_recurring'])) {
            $query->where('is_recurring', $filters['is_recurring']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get active addons.
     */
    public function getActiveAddons(): Collection
    {
        return Addon::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Find an addon by ID.
     */
    public function findById(int $id): ?Addon
    {
        return Addon::with(['plans'])->find($id);
    }

    /**
     * Find an addon by code.
     */
    public function findByCode(string $code): ?Addon
    {
        return Addon::with(['plans'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Create a new addon.
     */
    public function create(CreateAddonDTO $dto): Addon
    {
        return $this->transaction(function () use ($dto) {
            $addon = Addon::create([
                ...$dto->toArray(),
                'uuid' => Str::uuid()->toString(),
                'created_by' => auth()->id(),
            ]);

            // Attach plans
            if (!empty($dto->planIds)) {
                $addon->plans()->attach($dto->planIds);
            }

            $this->dispatchEvent(new AddonCreated($addon));

            return $addon->fresh(['plans']);
        });
    }

    /**
     * Update an addon.
     */
    public function update(Addon $addon, UpdateAddonDTO $dto): Addon
    {
        return $this->transaction(function () use ($addon, $dto) {
            $oldData = $addon->toArray();

            $updateData = $dto->toArray();
            if (!empty($updateData)) {
                $updateData['updated_by'] = auth()->id();
                $addon->update($updateData);
            }

            // Sync plans if provided
            if ($dto->planIds !== null) {
                $addon->plans()->sync($dto->planIds);
            }

            $this->dispatchEvent(new AddonUpdated($addon, $oldData));

            return $addon->fresh(['plans']);
        });
    }

    /**
     * Delete an addon.
     */
    public function delete(Addon $addon): bool
    {
        return $this->transaction(function () use ($addon) {
            $this->dispatchEvent(new AddonDeleted($addon));

            return $addon->delete();
        });
    }

    /**
     * Activate an addon.
     */
    public function activate(Addon $addon): Addon
    {
        $addon->activate();
        return $addon->fresh();
    }

    /**
     * Deactivate an addon.
     */
    public function deactivate(Addon $addon): Addon
    {
        $addon->deactivate();
        return $addon->fresh();
    }

    /**
     * Get addons for a specific plan.
     */
    public function getAddonsForPlan(int $planId): Collection
    {
        return Addon::query()
            ->active()
            ->whereHas('plans', function ($query) use ($planId) {
                $query->where('plans.id', $planId);
            })
            ->get();
    }

    /**
     * Get recurring addons.
     */
    public function getRecurringAddons(): Collection
    {
        return Addon::query()
            ->active()
            ->recurring()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get one-time addons.
     */
    public function getOneTimeAddons(): Collection
    {
        return Addon::query()
            ->active()
            ->oneTime()
            ->orderBy('name')
            ->get();
    }
}
