<?php

namespace App\Modules\Console\AuditLogs;

use App\Modules\Console\AuditLogs\Infrastructure\Models\AuditLog;
use App\Modules\Console\AuditLogs\Presentation\Policies\AuditLogPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
