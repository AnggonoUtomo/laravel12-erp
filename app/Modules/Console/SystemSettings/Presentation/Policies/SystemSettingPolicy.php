<?php

namespace App\Modules\Console\SystemSettings\Presentation\Policies;

use App\Models\User;

class SystemSettingPolicy
{
    public function view(User $user): bool
    {
        return $user->can('system-settings.view');
    }

    public function update(User $user): bool
    {
        return $user->can('system-settings.update');
    }
}
