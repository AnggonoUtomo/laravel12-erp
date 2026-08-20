<?php

namespace App\Modules\Console\NotificationTemplates\Presentation\Policies;

use App\Models\User;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notification-templates.view');
    }

    public function update(User $user): bool
    {
        return $user->can('notification-templates.update');
    }
}
