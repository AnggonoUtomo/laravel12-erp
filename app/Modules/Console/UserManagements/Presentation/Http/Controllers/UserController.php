<?php

namespace App\Modules\Console\UserManagements\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use App\Modules\Console\UserManagements\Application\Services\UserImpersonationService;
use App\Modules\Console\UserManagements\Application\Services\UserService;
use App\Modules\Console\UserManagements\Presentation\Http\Requests\StoreUserRequest;
use App\Modules\Console\UserManagements\Presentation\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Permission;
use App\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly SystemSettingService $settings,
        private readonly UserImpersonationService $impersonation,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->toString();
        $role = $request->string('role')->toString();
        $canSeeSuperSystemRole = (bool) $request->user()?->isSuperAdmin();
        $role = $role === User::SUPER_SYSTEM_ROLE && ! $canSeeSuperSystemRole ? '' : $role;
        $archive = $request->string('archive', 'active')->toString();
        $perPage = $this->perPage($request);

        $users = User::query()
            ->with(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name'])
            ->with('media')
            ->when($archive === 'with-trashed', fn ($query) => $query->withTrashed())
            ->when($archive === 'only-trashed', fn ($query) => $query->onlyTrashed())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, fn ($query) => $query->role($role))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) use ($canSeeSuperSystemRole, $request) {
                $roles = $user->roles
                    ->pluck('name')
                    ->when(! $canSeeSuperSystemRole, fn ($roles) => $roles->reject(fn (string $role) => $role === User::SUPER_SYSTEM_ROLE))
                    ->values();
                $rolePermissions = $user->roles
                    ->when(! $canSeeSuperSystemRole, fn ($roles) => $roles->reject(fn (Role $role) => $role->name === User::SUPER_SYSTEM_ROLE))
                    ->mapWithKeys(fn (Role $role) => [
                        $role->name => $role->permissions
                            ->pluck('name')
                            ->sort()
                            ->values(),
                    ]);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initials' => $this->initials($user->name),
                    'avatar' => $user->avatar,
                    'roles' => $roles,
                    'rolePermissions' => $rolePermissions,
                    'permissions' => $user->permissions->pluck('name')->values(),
                    'effectivePermissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
                    'primaryRole' => $roles->first(),
                    'status' => $user->trashed() ? 'archived' : ($user->email_verified_at ? 'active' : 'inactive'),
                    'lastLogin' => null,
                    'created_at' => $user->created_at?->format('d M Y'),
                    'deleted_at' => $user->deleted_at?->format('d M Y'),
                    'can' => [
                        'update' => $request->user()?->can('update', $user),
                        'delete' => $request->user()?->can('delete', $user),
                        'restore' => $request->user()?->can('restore', $user),
                        'forceDelete' => $request->user()?->can('forceDelete', $user),
                        'impersonate' => $request->user()?->can('impersonate', $user),
                    ],
                ];
            });

        return Inertia::render('console/users/index', [
            'users' => $users,
            'roles' => Role::query()
                ->select('id', 'name')
                ->with('permissions:id,name')
                ->when(! $canSeeSuperSystemRole, fn ($query) => $query->where('name', '!=', User::SUPER_SYSTEM_ROLE))
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions
                        ->pluck('name')
                        ->sort()
                        ->values(),
                ]),
            'permissionGroups' => Permission::query()
                ->select('id', 'name', 'guard_name')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Permission $permission) => Str::before($permission->name, '.'))
                ->map(fn ($permissions, string $module) => [
                    'module' => $module ?: 'general',
                    'permissions' => $permissions->values(),
                ])
                ->values(),
            'filters' => [
                'search' => $search,
                'role' => $role ?: null,
                'archive' => $archive,
                'per_page' => $perPage,
            ],
            'can' => [
                'create' => $request->user()?->can('create', User::class),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->toDto());

        return back()->with('success', 'User berhasil dibuat. Tautan aktivasi dikirim jika otomasi email aktif.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->toDto());

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user);

        return back()->with('success', 'User dipindahkan ke arsip.');
    }

    public function restore(string $user): RedirectResponse
    {
        $target = User::withTrashed()->findOrFail($user);

        $this->authorize('restore', $target);

        $this->users->restore($target);

        return back()->with('success', 'User berhasil dipulihkan.');
    }

    public function forceDestroy(string $user): RedirectResponse
    {
        $target = User::withTrashed()->findOrFail($user);

        $this->authorize('forceDelete', $target);

        $this->users->forceDelete($target);

        return back()->with('success', 'User berhasil dihapus permanen.');
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($message = $this->impersonation->canStart($request, $user)) {
            return back()->with('error', $message);
        }

        $this->impersonation->start($request, $user);

        return redirect()->route('dashboard')->with('success', "Sekarang kamu sedang masuk sebagai {$user->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $impersonator = $this->impersonation->stop($request);

        if (! $impersonator) {
            return redirect()->route('dashboard')->with('error', 'Session impersonate tidak ditemukan.');
        }

        return redirect()->route('users.index')->with('success', "Kamu sudah kembali sebagai {$impersonator->name}.");
    }

    private function perPage(Request $request): int
    {
        return $this->settings->resolvePerPage($request);
    }

    private function initials(string $name): string
    {
        return Str::upper(Str::of($name)
            ->explode(' ')
            ->filter()
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->take(2)
            ->implode(''));
    }
}
