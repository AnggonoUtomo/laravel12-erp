<?php

namespace Tests\Feature;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

class AuditLogsArchitectureTest extends TestCase
{
    public function test_audit_logs_uses_target_routes_without_changing_its_public_route_contract(): void
    {
        $path = app_path('Modules/Console/AuditLogs');

        $this->assertFileExists($path.'/module.json');
        $this->assertFileExists($path.'/README.md');
        $this->assertFileExists($path.'/ServiceProvider.php');
        $this->assertFileExists($path.'/Presentation/Routes/web.php');
        $this->assertFileExists($path.'/Presentation/Http/Controllers/AuditLogController.php');
        $this->assertFileExists($path.'/Application/Services/AuditLogService.php');
        $this->assertFileExists($path.'/Infrastructure/Models/AuditLog.php');
        $this->assertFileDoesNotExist($path.'/routes.php');

        $routes = array_map(fn (string $route) => str_replace('\\', '/', $route), ModuleRegistry::routeFiles());

        $this->assertContains(str_replace('\\', '/', $path.'/Presentation/Routes/web.php'), $routes);
    }
}
