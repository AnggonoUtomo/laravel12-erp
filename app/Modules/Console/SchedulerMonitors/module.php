<?php

use App\Modules\Console\SchedulerMonitors\ServiceProvider;

return [
    'name' => 'SchedulerMonitors',
    'project' => 'Console',
    'title' => 'Scheduler Monitor',
    'slug' => 'scheduler-monitors',
    'description' => 'Monitoring scheduler, heartbeat, dan eksekusi command terjadwal.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [ServiceProvider::class],
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
