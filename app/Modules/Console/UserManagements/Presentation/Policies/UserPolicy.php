<?php

namespace App\Modules\Console\UserManagements\Presentation\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && ! $target->trashed() && $user->can('users.delete');
    }

    public function restore(User $user, User $target): bool
    {
        return $target->trashed() && $user->can('users.restore');
    }

    public function forceDelete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $target->trashed() && $user->can('users.force-delete');
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->id !== $target->id
            && ! session()->has('impersonator_id')
            && ! $target->isSuperAdmin()
            && $user->can('users.impersonate');
    }
}
