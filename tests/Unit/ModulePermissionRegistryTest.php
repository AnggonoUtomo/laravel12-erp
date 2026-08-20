<?php

namespace Tests\Unit;

use App\Modules\Console\SystemSettings\ServiceProvider;
use App\Support\Modules\ModulePermissionRegistry;
use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

class ModulePermissionRegistryTest extends TestCase
{
    public function test_it_collects_permissions_from_modules(): void
    {
        $permissions = ModulePermissionRegistry::permissions();

        $this->assertContains('users.view', $permissions);
        $this->assertContains('access-control.view', $permissions);
        $this->assertContains('system-settings.view', $permissions);
        $this->assertContains('audit-logs.view', $permissions);
    }

    public function test_it_collects_default_role_permissions_from_modules(): void
    {
        $adminPermissions = ModulePermissionRegistry::defaultRolePermissions('admin');
        $staffPermissions = ModulePermissionRegistry::defaultRolePermissions('staff');

        $this->assertContains('users.create', $adminPermissions);
        $this->assertContains('audit-logs.view', $adminPermissions);
        $this->assertContains('users.view', $staffPermissions);
        $this->assertNotContains('system-settings.update', $staffPermissions);
    }

    public function test_it_reads_formal_module_manifests(): void
    {
        $modules = ModuleRegistry::modules();
        $systemSettings = $modules->firstWhere('name', 'SystemSettings');

        $this->assertNotNull($systemSettings);
        $this->assertSame('Console', $systemSettings['project']);
        $this->assertSame('Console', $systemSettings['group']);
        $this->assertSame('System Settings', $systemSettings['title']);
        $this->assertSame('system-settings', $systemSettings['slug']);
        $this->assertTrue($systemSettings['enabled']);
        $this->assertContains(
            ServiceProvider::class,
            $systemSettings['providers'],
        );
    }
}
