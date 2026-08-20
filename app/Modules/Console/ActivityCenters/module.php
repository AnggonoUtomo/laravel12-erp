<?php

use App\Modules\Console\ActivityCenters\ServiceProvider;

return [
    'name' => 'ActivityCenters',
    'project' => 'Console',
    'title' => 'Activity Center',
    'slug' => 'activity-centers',
    'description' => 'Pusat aktivitas dan notifikasi console.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [
        ServiceProvider::class,
    ],
    'dependencies' => [],
    'exports' => [
        'routes' => true,
        'permissions' => true,
        'navigation' => false,
    ],
    'events' => [],
    'listeners' => [],
    'integrations' => [],
];
