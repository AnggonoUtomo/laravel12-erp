<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use App\Support\Database\Commands\SchemaPreflightCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SchemaPreflightCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($ability === 'impersonate') {
                return null;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        app(SystemSettingService::class)->applyMailSettings();
        app(SystemSettingService::class)->applyLocalizationSettings();
        app(SystemSettingService::class)->applySecurityPolicy();
    }
}
