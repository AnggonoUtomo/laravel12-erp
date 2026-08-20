<?php

namespace App\Modules\Console\LoginActivities;

use App\Modules\Console\LoginActivities\Infrastructure\Models\LoginActivity;
use App\Modules\Console\LoginActivities\Presentation\Policies\LoginActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(LoginActivity::class, LoginActivityPolicy::class);
    }
}
