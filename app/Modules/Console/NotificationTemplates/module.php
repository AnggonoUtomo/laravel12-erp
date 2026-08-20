<?php

use App\Modules\Console\NotificationTemplates\ServiceProvider;

return [
    'name' => 'NotificationTemplates',
    'project' => 'Console',
    'title' => 'Notification Templates',
    'slug' => 'notification-templates',
    'description' => 'Template notifikasi sistem untuk email dan channel lain.',
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
