<?php

namespace App\Modules\Console\AccessControls\Presentation\Policies;

use App\Models\User;
use App\Models\Role;

class AccessControlPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->can('access-control.view');
    }

    public function create(User $user): bool
    {
        return $this->canManage($user) || $user->can('access-control.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $role->name !== User::SUPER_SYSTEM_ROLE && ($this->canManage($user) || $user->can('access-control.update'));
    }

    public function delete(User $user, Role $role): bool
    {
        return $role->name !== User::SUPER_SYSTEM_ROLE && ($this->canManage($user) || $user->can('access-control.delete'));
    }

    public function manage(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->can('roles.manage');
    }
}
