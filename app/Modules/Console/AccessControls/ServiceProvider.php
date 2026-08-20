<?php

namespace App\Modules\Console\AccessControls;

use App\Modules\Console\AccessControls\Presentation\Policies\AccessControlPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use App\Models\Role;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Role::class, AccessControlPolicy::class);
        Gate::define('access-control.manage', [AccessControlPolicy::class, 'manage']);
    }
}
