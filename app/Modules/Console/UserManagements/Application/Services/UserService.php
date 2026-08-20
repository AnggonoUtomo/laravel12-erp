<?php

namespace App\Modules\Console\UserManagements\Application\Services;

use App\Models\User;
use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use App\Modules\Console\SystemSettings\Infrastructure\Jobs\SendUserActivationLinkJob;
use App\Modules\Console\UserManagements\Application\DTOs\UserData;
use App\Modules\Console\UserManagements\Infrastructure\Transactions\UserTransaction;
use Illuminate\Support\Str;
use Throwable;

class UserService
{
    public function __construct(
        private readonly UserTransaction $transaction,
        private readonly SystemSettingService $settings,
        private readonly AuditLogService $audit,
    ) {}

    public function create(UserData $data): User
    {
        $user = $this->transaction->run(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Str::password(32),
                'email_verified_at' => null,
            ]);

            $this->syncRoles($user, $data->roles);
            $this->syncPermissions($user, $data->permissions);
            $this->syncAvatar($user, $data);
            $this->audit->record(
                module: 'user-management',
                event: 'user.created',
                auditable: $user,
                description: "Created user {$user->email}",
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $data->roles,
                    'permissions' => $data->permissions,
                    'activation_link_requested' => $this->settings->shouldSendCredentialsOnCreate(),
                ],
            );

            return $user->refresh();
        });

        if ($this->settings->shouldSendCredentialsOnCreate()) {
            $this->sendActivationLink($user);
        }

        return $user;
    }

    public function update(User $user, UserData $data): User
    {
        $updatedUser = $this->transaction->run(function () use ($user, $data) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values()->all(),
                'permissions' => $user->permissions()->pluck('name')->values()->all(),
                'avatar' => $user->avatar,
            ];

            $user->update([
                'name' => $data->name,
                'email' => $data->email,
            ]);
            $this->syncRoles($user, $data->roles);
            $this->syncPermissions($user, $data->permissions);
            $this->syncAvatar($user, $data);

            $this->audit->record(
                module: 'user-management',
                event: 'user.updated',
                auditable: $user,
                description: "Updated user {$user->email}",
                oldValues: $oldValues,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password_reset_link_requested' => $data->sendPasswordResetLink,
                    'roles' => $data->roles,
                    'permissions' => $data->permissions,
                    'avatar_changed' => (bool) $data->avatar,
                    'avatar_removed' => $data->removeAvatar,
                ],
            );

            return $user->refresh();
        });

        if ($data->sendPasswordResetLink && $this->settings->shouldSendCredentialsOnPasswordUpdate()) {
            $this->sendActivationLink($updatedUser);
        }

        return $updatedUser;
    }

    public function delete(User $user): void
    {
        $this->transaction->run(function () use ($user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values()->all(),
                'permissions' => $user->permissions()->pluck('name')->values()->all(),
            ];

            $user->delete();

            $this->audit->record(
                module: 'user-management',
                event: 'user.deleted',
                auditable: $user,
                description: "Archived user {$oldValues['email']}",
                oldValues: $oldValues,
            );
        });
    }

    public function restore(User $user): void
    {
        $this->transaction->run(function () use ($user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'deleted_at' => $user->deleted_at?->toISOString(),
            ];

            $user->restore();

            $this->audit->record(
                module: 'user-management',
                event: 'user.restored',
                auditable: $user,
                description: "Restored user {$user->email}",
                oldValues: $oldValues,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'deleted_at' => null,
                ],
            );
        });
    }

    public function forceDelete(User $user): void
    {
        $this->transaction->run(function () use ($user) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values()->all(),
                'permissions' => $user->permissions()->pluck('name')->values()->all(),
                'deleted_at' => $user->deleted_at?->toISOString(),
            ];

            $this->audit->record(
                module: 'user-management',
                event: 'user.force-deleted',
                auditable: $user,
                description: "Force deleted user {$oldValues['email']}",
                oldValues: $oldValues,
            );

            $user->forceDelete();
        });
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        if ($user->isSuperAdmin() && ! in_array(User::SUPER_SYSTEM_ROLE, $roles, true)) {
            $roles[] = User::SUPER_SYSTEM_ROLE;
        }

        $user->syncRoles($roles);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function syncPermissions(User $user, array $permissions): void
    {
        $user->syncPermissions($permissions);
    }

    private function syncAvatar(User $user, UserData $data): void
    {
        if ($data->removeAvatar) {
            $user->clearMediaCollection('avatar');
        }

        if (! $data->avatar) {
            return;
        }

        $user
            ->addMedia($data->avatar)
            ->usingName($user->name)
            ->toMediaCollection('avatar');
    }

    private function sendActivationLink(User $user): void
    {
        try {
            SendUserActivationLinkJob::dispatch($user->id);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
