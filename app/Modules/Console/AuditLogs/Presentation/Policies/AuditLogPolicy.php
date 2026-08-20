<?php

namespace App\Modules\Console\AuditLogs\Presentation\Policies;

use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit-logs.view');
    }
}
