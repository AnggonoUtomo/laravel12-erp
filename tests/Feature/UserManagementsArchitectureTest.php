<?php

namespace Tests\Feature;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

class UserManagementsArchitectureTest extends TestCase
{
    public function test_user_managements_uses_target_routes_without_changing_its_public_route_contract(): void
    {
        $path = app_path('Modules/Console/UserManagements');

        $this->assertFileExists($path.'/module.json');
        $this->assertFileExists($path.'/README.md');
        $this->assertFileExists($path.'/ServiceProvider.php');
        $this->assertFileExists($path.'/Presentation/Routes/web.php');
        $this->assertFileExists($path.'/Presentation/Http/Controllers/UserController.php');
        $this->assertFileExists($path.'/Application/Services/UserService.php');
        $this->assertFileExists($path.'/Application/Services/UserImpersonationService.php');
        $this->assertFileDoesNotExist($path.'/routes.php');

        $routes = array_map(fn (string $route) => str_replace('\\', '/', $route), ModuleRegistry::routeFiles());

        $this->assertContains(str_replace('\\', '/', $path.'/Presentation/Routes/web.php'), $routes);
    }
}
