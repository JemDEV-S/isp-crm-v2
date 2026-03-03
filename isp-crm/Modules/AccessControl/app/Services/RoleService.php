<?php

declare(strict_types=1);

namespace Modules\AccessControl\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AccessControl\DTOs\CreateRoleDTO;
use Modules\AccessControl\DTOs\UpdateRoleDTO;
use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Events\RoleCreated;
use Modules\AccessControl\Events\RoleDeleted;
use Modules\AccessControl\Events\RoleUpdated;

class RoleService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Role::query()
            ->withCount(['users', 'permissions']);

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

        if (isset($filters['is_system'])) {
            $query->where('is_system', $filters['is_system']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function all(): Collection
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(CreateRoleDTO $dto): Role
    {
        return DB::transaction(function () use ($dto) {
            $role = Role::create([
                'code' => $dto->code,
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
                'is_system' => false,
            ]);

            if (!empty($dto->permissionIds)) {
                $role->syncPermissions($dto->permissionIds);
            }

            event(new RoleCreated($role));

            return $role->loadCount(['users', 'permissions']);
        });
    }

    public function update(Role $role, UpdateRoleDTO $dto): Role
    {
        if ($role->is_system) {
            throw new \Exception('No se puede modificar un rol del sistema.');
        }

        return DB::transaction(function () use ($role, $dto) {
            $role->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            $role->syncPermissions($dto->permissionIds);

            event(new RoleUpdated($role));

            return $role->loadCount(['users', 'permissions']);
        });
    }

    public function delete(Role $role): bool
    {
        if ($role->is_system) {
            throw new \Exception('No se puede eliminar un rol del sistema.');
        }

        if ($role->users()->count() > 0) {
            throw new \Exception('No se puede eliminar un rol que tiene usuarios asignados.');
        }

        return DB::transaction(function () use ($role) {
            $role->permissions()->detach();

            $result = $role->delete();

            event(new RoleDeleted($role));

            return $result;
        });
    }

    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findOrFail(int $id): Role
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        if ($role->is_system && $role->code !== 'superadmin') {
            throw new \Exception('No se puede modificar permisos de un rol del sistema.');
        }

        $role->syncPermissions($permissionIds);

        event(new RoleUpdated($role));

        return $role->load('permissions');
    }
}
