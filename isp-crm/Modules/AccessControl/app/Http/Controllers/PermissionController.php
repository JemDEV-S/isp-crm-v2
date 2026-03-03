<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AccessControl\Entities\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accesscontrol.permission.view')->only('index');
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $module = $request->get('module');

        $query = Permission::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($module) {
            $query->where('module', $module);
        }

        $permissions = $query->orderBy('module')->orderBy('code')->paginate(50);
        $modules = Permission::query()->distinct()->pluck('module')->sort()->values();

        return view('accesscontrol::permissions.index', compact('permissions', 'modules', 'search', 'module'));
    }
}
