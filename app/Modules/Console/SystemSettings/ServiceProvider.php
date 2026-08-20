<?php

namespace App\Modules\Console\SystemSettings;

use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use App\Modules\Console\SystemSettings\Presentation\Policies\SystemSettingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SystemSettingService::class, SystemSettingPolicy::class);
    }
}
