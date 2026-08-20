<?php

use App\Modules\Console\GlobalSearches\ServiceProvider;

return [
    'name' => 'GlobalSearches',
    'project' => 'Console',
    'title' => 'Global Search',
    'slug' => 'global-searches',
    'description' => 'Command palette backend contract dan provider entity search read-only.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [ServiceProvider::class],
    'dependencies' => [
        'Console.UserManagements',
    ],
    'exports' => [
        'routes' => true,
        'permissions' => true,
        'navigation' => false,
    ],
    'events' => [],
    'listeners' => [],
    'integrations' => [
        'contracts' => [
            [
                'name' => 'EntitySearchProvider',
                'version' => '1.0.0',
                'direction' => 'internal',
                'description' => 'Read-only provider contract for permission-aware entity search.',
            ],
        ],
    ],
];
