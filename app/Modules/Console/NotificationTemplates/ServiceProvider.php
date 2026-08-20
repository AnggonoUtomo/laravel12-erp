<?php

namespace App\Modules\Console\NotificationTemplates;

use App\Modules\Console\NotificationTemplates\Infrastructure\Models\NotificationTemplate;
use App\Modules\Console\NotificationTemplates\Presentation\Policies\NotificationTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        Gate::policy(NotificationTemplate::class, NotificationTemplatePolicy::class);
    }
}
