<?php

namespace Tests\Feature;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

class GlobalSearchesArchitectureTest extends TestCase
{
    public function test_global_searches_uses_target_routes_without_changing_its_public_route_contract(): void
    {
        $path = app_path('Modules/Console/GlobalSearches');
        $this->assertFileExists($path.'/module.json');
        $this->assertFileExists($path.'/README.md');
        $this->assertFileExists($path.'/ServiceProvider.php');
        $this->assertFileExists($path.'/Presentation/Routes/web.php');
        $this->assertFileExists($path.'/Presentation/Http/Controllers/GlobalSearchController.php');
        $this->assertFileExists($path.'/Application/Services/GlobalSearchService.php');
        $this->assertFileDoesNotExist($path.'/routes.php');
        $routes = array_map(fn (string $route) => str_replace('\\', '/', $route), ModuleRegistry::routeFiles());
        $this->assertContains(str_replace('\\', '/', $path.'/Presentation/Routes/web.php'), $routes);
    }
}
