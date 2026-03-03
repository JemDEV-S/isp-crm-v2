<?php

declare(strict_types=1);

namespace Modules\AccessControl\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AccessControl\DTOs\CreateZoneDTO;
use Modules\AccessControl\DTOs\UpdateZoneDTO;
use Modules\AccessControl\Entities\Zone;
use Modules\AccessControl\Events\ZoneCreated;
use Modules\AccessControl\Events\ZoneDeleted;
use Modules\AccessControl\Events\ZoneUpdated;

class ZoneService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Zone::query()
            ->with('parent')
            ->withCount(['children', 'users']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function all(): Collection
    {
        return Zone::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getTree(): Collection
    {
        return Zone::query()
            ->with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(CreateZoneDTO $dto): Zone
    {
        return DB::transaction(function () use ($dto) {
            $zone = Zone::create([
                'code' => $dto->code,
                'name' => $dto->name,
                'description' => $dto->description,
                'parent_id' => $dto->parentId,
                'polygon' => $dto->polygon,
                'is_active' => $dto->isActive,
            ]);

            event(new ZoneCreated($zone));

            return $zone->load('parent');
        });
    }

    public function update(Zone $zone, UpdateZoneDTO $dto): Zone
    {
        return DB::transaction(function () use ($zone, $dto) {
            // Validar que no se cree un ciclo en la jerarquía
            if ($dto->parentId !== null) {
                $this->validateNoCircularReference($zone, $dto->parentId);
            }

            $zone->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'parent_id' => $dto->parentId,
                'polygon' => $dto->polygon,
                'is_active' => $dto->isActive,
            ]);

            event(new ZoneUpdated($zone));

            return $zone->load('parent');
        });
    }

    public function delete(Zone $zone): bool
    {
        if ($zone->hasChildren()) {
            throw new \Exception('No se puede eliminar una zona que tiene zonas hijas.');
        }

        if ($zone->users()->count() > 0) {
            throw new \Exception('No se puede eliminar una zona que tiene usuarios asignados.');
        }

        return DB::transaction(function () use ($zone) {
            $result = $zone->delete();

            event(new ZoneDeleted($zone));

            return $result;
        });
    }

    public function find(int $id): ?Zone
    {
        return Zone::with(['parent', 'children'])->find($id);
    }

    public function findOrFail(int $id): Zone
    {
        return Zone::with(['parent', 'children'])->findOrFail($id);
    }

    public function toggleStatus(Zone $zone): Zone
    {
        $zone->update(['is_active' => !$zone->is_active]);

        event(new ZoneUpdated($zone));

        return $zone;
    }

    private function validateNoCircularReference(Zone $zone, int $parentId): void
    {
        $descendantIds = $zone->descendants();

        if (in_array($parentId, $descendantIds)) {
            throw new \Exception('No se puede asignar una zona descendiente como zona padre.');
        }
    }
}
