<?php

namespace App\Modules\Console\UserManagements;

use App\Models\User;
use App\Modules\Console\UserManagements\Presentation\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }
}
