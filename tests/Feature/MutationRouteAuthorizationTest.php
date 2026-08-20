<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class MutationRouteAuthorizationTest extends TestCase
{
    public function test_every_privileged_domain_mutation_requires_authentication(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => $this->isPrivilegedMutation($route));

        $this->assertNotEmpty($routes, 'Tidak ada privileged mutation route yang ditemukan.');

        foreach ($routes as $route) {
            $this->assertContains(
                'auth',
                $route->gatherMiddleware(),
                "Mutation route [{$route->getName()}] wajib memakai middleware auth.",
            );
        }
    }

    private function isPrivilegedMutation(Route $route): bool
    {
        $name = (string) $route->getName();
        $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);
        $prefixes = [
            'access-control.',
            'activity-center.',
            'backup-restore.',
            'notification-templates.',
            'queue-monitor.',
            'scheduler-monitor.',
            'system-settings.',
            'users.',
        ];

        return $methods !== [] && Str::startsWith($name, $prefixes);
    }
}
