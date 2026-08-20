<?php

use App\Modules\Console\AccessControls\ServiceProvider;

return [
    'name' => 'AccessControls',
    'project' => 'Console',
    'title' => 'Kontrol Akses',
    'slug' => 'access-controls',
    'description' => 'Role dan permission management untuk console.',
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
