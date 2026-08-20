<?php

namespace App\Modules\Console\AccessControls\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Console\AccessControls\Application\Services\AccessControlService;
use App\Modules\Console\AccessControls\Presentation\Http\Requests\StorePermissionRequest;
use App\Modules\Console\AccessControls\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\Console\AccessControls\Presentation\Http\Requests\SyncRolePermissionsRequest;
use App\Modules\Console\AccessControls\Presentation\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Permission;
use App\Models\Role;

class AccessControlController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Role::class, only: ['index']),
            new Middleware('can:create,'.Role::class, only: ['storeRole']),
            new Middleware('can:update,role', only: ['updateRole', 'syncPermissions']),
            new Middleware('can:delete,role', only: ['destroyRole']),
            new Middleware('can:access-control.manage', only: ['storePermission', 'destroyPermission']),
        ];
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'console/access-control/index',
            $this->accessControl->getPageData($request->string('role')->toString() ?: null, $request->user()),
        );
    }

    public function storeRole(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->accessControl->createRole($request->toDto());

        return redirect()
            ->route('access-control.index', ['role' => $role->id])
            ->with('success', 'Role created.');
    }

    public function updateRole(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->accessControl->updateRole($role, $request->toDto());

        return back()->with('success', 'Role updated.');
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->accessControl->syncPermissions($role, $request->toDto());

        return back()->with('success', 'Role permissions updated.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        abort_if($role->name === User::SUPER_SYSTEM_ROLE, 403, 'The super-system role cannot be deleted.');

        $this->accessControl->deleteRole($role);

        return back()->with('success', 'Role deleted.');
    }

    public function storePermission(StorePermissionRequest $request): RedirectResponse
    {
        $this->accessControl->createPermission($request->toDto());

        return back()->with('success', 'Permission created.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        abort_if($permission->name === 'roles.manage', 403, 'The roles.manage permission cannot be deleted.');

        $this->accessControl->deletePermission($permission);

        return back()->with('success', 'Permission deleted.');
    }
}
