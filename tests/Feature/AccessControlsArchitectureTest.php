<?php

namespace Tests\Feature;

use App\Support\Modules\ModuleRegistry;
use App\Support\Modules\ModulePermissionRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AccessControlsArchitectureTest extends TestCase
{
    public function test_access_controls_uses_the_ddd_lite_module_layout_without_changing_its_route_contract(): void
    {
        $path = app_path('Modules/Console/AccessControls');

        $this->assertFileExists($path.'/module.json');
        $this->assertFileExists($path.'/README.md');
        $this->assertFileExists($path.'/ServiceProvider.php');
        $this->assertFileExists($path.'/Presentation/Routes/web.php');
        $this->assertFileExists($path.'/Presentation/Http/Controllers/AccessControlController.php');
        $this->assertFileExists($path.'/Application/Services/AccessControlService.php');
        $this->assertFileDoesNotExist($path.'/routes.php');

        $routeFile = str_replace('\\', '/', ModuleRegistry::routeFiles()[0]);

        $this->assertTrue(File::exists($routeFile));
        $this->assertStringEndsWith('/Console/AccessControls/Presentation/Routes/web.php', $routeFile);
        $this->assertContains('access-control.view', ModulePermissionRegistry::permissions());
    }
}
