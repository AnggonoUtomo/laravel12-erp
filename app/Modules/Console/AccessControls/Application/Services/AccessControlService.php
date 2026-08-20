<?php

namespace App\Modules\Console\AccessControls\Application\Services;

use App\Models\User;
use App\Modules\Console\AccessControls\Application\DTOs\PermissionData;
use App\Modules\Console\AccessControls\Application\DTOs\RoleData;
use App\Modules\Console\AccessControls\Application\DTOs\SyncRolePermissionsData;
use App\Modules\Console\AccessControls\Infrastructure\Transactions\AccessControlTransaction;
use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlService
{
    public function __construct(
        private readonly AccessControlTransaction $transaction,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPageData(?string $selectedRoleId = null, ?User $actor = null): array
    {
        $roles = Role::query()
            ->with([
                'permissions' => fn ($query) => $query
                    ->select('id', 'name', 'guard_name')
                    ->orderBy('name'),
            ])
            ->select('id', 'name', 'guard_name')
            ->when(! $actor?->isSuperAdmin(), fn ($query) => $query->where('name', '!=', User::SUPER_SYSTEM_ROLE))
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'is_protected' => $role->name === User::SUPER_SYSTEM_ROLE,
            ]);

        $permissionGroups = Permission::query()
            ->select('id', 'name', 'guard_name')
            ->orderBy('name')
            ->get()
            ->map(function (Permission $permission) {
                $module = Str::contains($permission->name, '.')
                    ? Str::before($permission->name, '.')
                    : 'general';

                $action = Str::contains($permission->name, '.')
                    ? Str::after($permission->name, '.')
                    : $permission->name;

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'module' => $module,
                    'module_label' => Str::headline($module),
                    'action' => $action,
                    'label' => Str::headline(str_replace(['.', '_', '-'], ' ', $action)),
                ];
            })
            ->groupBy('module')
            ->map(fn ($permissions, string $module) => [
                'module' => $module,
                'label' => Str::headline($module),
                'permissions' => $permissions->values(),
            ])
            ->values();

        return [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'selectedRoleId' => $selectedRoleId ?: ($roles->first()['id'] ?? null),
        ];
    }

    public function createRole(RoleData $data): Role
    {
        return $this->transaction->run(function () use ($data) {
            $role = Role::create([
                'name' => $data->name,
                'guard_name' => $data->guardName,
            ]);

            $role->syncPermissions($data->permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'role.created',
                auditable: $role,
                description: "Created role {$role->name}",
                newValues: [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $data->permissions,
                ],
            );

            return $role->refresh();
        });
    }

    public function updateRole(Role $role, RoleData $data): Role
    {
        return $this->transaction->run(function () use ($role, $data) {
            $oldValues = [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions()->pluck('name')->values()->all(),
            ];

            $role->update(['name' => $data->name]);
            $role->syncPermissions($data->permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'role.updated',
                auditable: $role,
                description: "Updated role {$role->name}",
                oldValues: $oldValues,
                newValues: [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $data->permissions,
                ],
            );

            return $role->refresh();
        });
    }

    public function syncPermissions(Role $role, SyncRolePermissionsData $data): Role
    {
        return $this->transaction->run(function () use ($role, $data) {
            $oldPermissions = $role->permissions()->pluck('name')->values()->all();

            $role->syncPermissions($data->permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'role.permissions_synced',
                auditable: $role,
                description: "Synced permissions for role {$role->name}",
                oldValues: ['permissions' => $oldPermissions],
                newValues: ['permissions' => $data->permissions],
            );

            return $role->refresh();
        });
    }

    public function deleteRole(Role $role): void
    {
        $this->transaction->run(function () use ($role) {
            $oldValues = [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions()->pluck('name')->values()->all(),
            ];

            $role->syncPermissions([]);
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'role.deleted',
                auditable: $role,
                description: "Deleted role {$oldValues['name']}",
                oldValues: $oldValues,
            );
        });
    }

    public function createPermission(PermissionData $data): Permission
    {
        return $this->transaction->run(function () use ($data) {
            $permission = Permission::create([
                'name' => $data->name,
                'guard_name' => 'web',
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'permission.created',
                auditable: $permission,
                description: "Created permission {$permission->name}",
                newValues: [
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ],
            );

            return $permission;
        });
    }

    public function deletePermission(Permission $permission): void
    {
        $this->transaction->run(function () use ($permission) {
            $oldValues = [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ];

            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit->record(
                module: 'access-control',
                event: 'permission.deleted',
                auditable: $permission,
                description: "Deleted permission {$oldValues['name']}",
                oldValues: $oldValues,
            );
        });
    }
}
