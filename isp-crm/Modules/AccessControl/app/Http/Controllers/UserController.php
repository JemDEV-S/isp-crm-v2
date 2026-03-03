<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AccessControl\DTOs\CreateUserDTO;
use Modules\AccessControl\DTOs\UpdateUserDTO;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Http\Requests\StoreUserRequest;
use Modules\AccessControl\Http\Requests\UpdateUserRequest;
use Modules\AccessControl\Services\RoleService;
use Modules\AccessControl\Services\UserService;
use Modules\AccessControl\Services\ZoneService;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoleService $roleService,
        private readonly ZoneService $zoneService,
    ) {
        $this->middleware('permission:accesscontrol.user.view')->only(['index', 'show']);
        $this->middleware('permission:accesscontrol.user.create')->only(['create', 'store']);
        $this->middleware('permission:accesscontrol.user.update')->only(['edit', 'update']);
        $this->middleware('permission:accesscontrol.user.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : null,
            'zone_id' => $request->get('zone_id'),
            'role_id' => $request->get('role_id'),
        ];

        $users = $this->userService->paginate(15, array_filter($filters));
        $roles = $this->roleService->all();
        $zones = $this->zoneService->all();

        return view('accesscontrol::users.index', compact('users', 'roles', 'zones', 'filters'));
    }

    public function create(): View
    {
        $roles = $this->roleService->all();
        $zones = $this->zoneService->all();

        return view('accesscontrol::users.create', compact('roles', 'zones'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $dto = CreateUserDTO::fromRequest($request);
        $this->userService->create($dto);

        return redirect()
            ->route('accesscontrol.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'zone', 'sessions' => function ($query) {
            $query->orderBy('last_activity', 'desc')->limit(10);
        }]);

        return view('accesscontrol::users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'zone']);
        $roles = $this->roleService->all();
        $zones = $this->zoneService->all();

        return view('accesscontrol::users.edit', compact('user', 'roles', 'zones'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $dto = UpdateUserDTO::fromRequest($request);
        $this->userService->update($user, $dto);

        return redirect()
            ->route('accesscontrol.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->userService->delete($user);

            return redirect()
                ->route('accesscontrol.users.index')
                ->with('success', 'Usuario eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->route('accesscontrol.users.index')
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->userService->toggleStatus($user);
        $status = $user->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->back()
            ->with('success', "Usuario {$status} exitosamente.");
    }
}
