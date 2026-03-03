<?php

declare(strict_types=1);

namespace Modules\AccessControl\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\AccessControl\DTOs\CreateUserDTO;
use Modules\AccessControl\DTOs\UpdateUserDTO;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Events\UserCreated;
use Modules\AccessControl\Events\UserDeleted;
use Modules\AccessControl\Events\UserUpdated;

class UserService
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'zone']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (!empty($filters['role_id'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('roles.id', $filters['role_id']);
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'phone' => $dto->phone,
                'zone_id' => $dto->zoneId,
                'is_active' => $dto->isActive,
            ]);

            if (!empty($dto->roleIds)) {
                $user->syncRoles($dto->roleIds);
            }

            event(new UserCreated($user));

            return $user->load(['roles', 'zone']);
        });
    }

    public function update(User $user, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto) {
            $data = [
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'zone_id' => $dto->zoneId,
                'is_active' => $dto->isActive,
            ];

            if ($dto->password !== null) {
                $data['password'] = Hash::make($dto->password);
            }

            $user->update($data);

            if (!empty($dto->roleIds)) {
                $user->syncRoles($dto->roleIds);
            }

            event(new UserUpdated($user));

            return $user->load(['roles', 'zone']);
        });
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->sessions()->delete();

            $result = $user->delete();

            event(new UserDeleted($user));

            return $result;
        });
    }

    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);

        event(new UserUpdated($user));

        return $user;
    }

    public function find(int $id): ?User
    {
        return User::with(['roles', 'zone'])->find($id);
    }

    public function findOrFail(int $id): User
    {
        return User::with(['roles', 'zone'])->findOrFail($id);
    }
}
