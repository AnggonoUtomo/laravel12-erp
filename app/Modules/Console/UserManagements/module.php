<?php

use App\Modules\Console\UserManagements\ServiceProvider;

return [
    'name' => 'UserManagements',
    'project' => 'Console',
    'title' => 'Manajemen User',
    'slug' => 'user-managements',
    'description' => 'Manajemen user, avatar, role assignment, dan impersonation.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [
        ServiceProvider::class,
    ],
    'dependencies' => [
        'Console.AccessControls',
    ],
    'exports' => [
        'routes' => true,
        'permissions' => true,
        'navigation' => true,
    ],
    'events' => [],
    'listeners' => [],
    'integrations' => [],
];
