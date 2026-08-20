<?php

use App\Modules\Console\SystemSettings\ServiceProvider;

return [
    'name' => 'SystemSettings',
    'project' => 'Console',
    'title' => 'System Settings',
    'slug' => 'system-settings',
    'description' => 'Konfigurasi aplikasi, email, security, maintenance, dan health.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [
        ServiceProvider::class,
    ],
    'dependencies' => [],
    'exports' => [
        'routes' => true,
        'permissions' => true,
        'navigation' => true,
    ],
    'events' => [],
    'listeners' => [],
    'integrations' => [],
];
