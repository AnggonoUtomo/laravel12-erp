<?php

use App\Modules\Console\LoginActivities\ServiceProvider;

return [
    'name' => 'LoginActivities',
    'project' => 'Console',
    'title' => 'Login Activity',
    'slug' => 'login-activities',
    'description' => 'Monitoring aktivitas login dan percobaan autentikasi.',
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
