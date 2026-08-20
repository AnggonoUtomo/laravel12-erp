<?php

use App\Modules\Console\QueueMonitors\ServiceProvider;

return [
    'name' => 'QueueMonitors',
    'project' => 'Console',
    'title' => 'Queue Monitor',
    'slug' => 'queue-monitors',
    'description' => 'Monitoring queue, pending job, dan failed job.',
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
