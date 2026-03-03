<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AccessControl\DTOs\CreateRoleDTO;
use Modules\AccessControl\DTOs\UpdateRoleDTO;
use Modules\AccessControl\Entities\Permission;
use Modules\AccessControl\Entities\Role;
use Modules\AccessControl\Http\Requests\StoreRoleRequest;
use Modules\AccessControl\Http\Requests\UpdateRoleRequest;
use Modules\AccessControl\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
        $this->middleware('permission:accesscontrol.role.view')->only(['index', 'show']);
        $this->middleware('permission:accesscontrol.role.create')->only(['create', 'store']);
        $this->middleware('permission:accesscontrol.role.update')->only(['edit', 'update']);
        $this->middleware('permission:accesscontrol.role.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : null,
            'is_system' => $request->has('is_system') ? (bool) $request->get('is_system') : null,
        ];

        $roles = $this->roleService->paginate(15, array_filter($filters));

        return view('accesscontrol::roles.index', compact('roles', 'filters'));
    }

    public function create(): View
    {
        $permissions = Permission::groupedByModule();

        return view('accesscontrol::roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $dto = CreateRoleDTO::fromRequest($request);
        $this->roleService->create($dto);

        return redirect()
            ->route('accesscontrol.roles.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    public function show(Role $role): View
    {
        $role->load(['permissions', 'users']);
        $role->loadCount(['users', 'permissions']);

        return view('accesscontrol::roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        if ($role->is_system) {
            return redirect()
                ->route('accesscontrol.roles.index')
                ->with('error', 'No se puede editar un rol del sistema.');
        }

        $role->load('permissions');
        $permissions = Permission::groupedByModule();

        return view('accesscontrol::roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $dto = UpdateRoleDTO::fromRequest($request);
            $this->roleService->update($role, $dto);

            return redirect()
                ->route('accesscontrol.roles.index')
                ->with('success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $this->roleService->delete($role);

            return redirect()
                ->route('accesscontrol.roles.index')
                ->with('success', 'Rol eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->route('accesscontrol.roles.index')
                ->with('error', $e->getMessage());
        }
    }

    public function permissions(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::groupedByModule();

        return view('accesscontrol::roles.permissions', compact('role', 'permissions'));
    }

    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        try {
            $permissionIds = $request->input('permissions', []);
            $this->roleService->syncPermissions($role, $permissionIds);

            return redirect()
                ->route('accesscontrol.roles.show', $role)
                ->with('success', 'Permisos actualizados exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
