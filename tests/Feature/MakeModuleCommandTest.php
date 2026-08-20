<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    private string $backendRoot;

    private string $frontendRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureRoot = storage_path('framework/testing/module-generator/'.Str::uuid());
        $this->backendRoot = $fixtureRoot.'/backend';
        $this->frontendRoot = $fixtureRoot.'/frontend';

        config([
            'modules.backend_root' => $this->backendRoot,
            'modules.frontend_root' => $this->frontendRoot,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->backendRoot));

        parent::tearDown();
    }

    public function test_it_generates_a_module_inside_an_explicit_project(): void
    {
        $backendPath = $this->backendRoot.'/TmpProject/SandboxModule';
        $frontendPath = $this->frontendRoot.'/tmp-project/sandbox-module';

        File::deleteDirectory($backendPath);
        File::deleteDirectory($frontendPath);

        try {
            $this->artisan('make:module', [
                'name' => 'SandboxModule',
                '--project' => 'TmpProject',
            ])->assertSuccessful();

            $this->assertFileExists($backendPath.'/module.php');
            $this->assertFileExists($backendPath.'/module.json');
            $this->assertFileExists($backendPath.'/README.md');
            $this->assertDirectoryExists($backendPath.'/Application/DTOs');
            $this->assertDirectoryExists($backendPath.'/Infrastructure/Repositories');
            $this->assertDirectoryDoesNotExist($backendPath.'/Application/Commands');
            $this->assertDirectoryDoesNotExist($backendPath.'/Infrastructure/Adapters');
            $this->assertDirectoryDoesNotExist($backendPath.'/Infrastructure/Integrations');
            $this->assertFileExists($backendPath.'/Presentation/Routes/web.php');
            $this->assertFileExists($backendPath.'/permissions.php');
            $this->assertFileExists($backendPath.'/navigation.php');
            $this->assertFileExists($backendPath.'/ServiceProvider.php');
            $this->assertFileExists($backendPath.'/Presentation/Http/Controllers/SandboxModuleController.php');
            $this->assertFileExists($backendPath.'/Application/Services/SandboxModuleService.php');
            $this->assertStringContainsString('extends LaravelServiceProvider', File::get($backendPath.'/ServiceProvider.php'));
            $this->assertFileExists($frontendPath.'/index.tsx');
        } finally {
            File::deleteDirectory($backendPath);
            File::deleteDirectory($frontendPath);
        }
    }

    public function test_it_accepts_project_and_module_shorthand(): void
    {
        $backendPath = $this->backendRoot.'/TmpProject/SandboxModule';

        File::deleteDirectory($backendPath);

        try {
            $this->artisan('make:module', [
                'name' => 'TmpProject:SandboxModule',
                '--without-frontend' => true,
            ])->assertSuccessful();

            $this->assertFileExists($backendPath.'/module.php');
            $this->assertFileExists($backendPath.'/Presentation/Routes/web.php');
            $this->assertFileDoesNotExist($this->frontendRoot.'/tmp-project/sandbox-module/index.tsx');
        } finally {
            File::deleteDirectory($backendPath);
        }
    }

    public function test_it_accepts_namespace_and_module_as_positional_arguments(): void
    {
        $backendPath = $this->backendRoot.'/StudentManagement/Students';

        try {
            $this->artisan('make:module', [
                'name' => 'StudentManagement',
                'module' => 'Students',
                '--without-frontend' => true,
            ])->assertSuccessful();

            $this->assertFileExists($backendPath.'/Presentation/Routes/web.php');
            $this->assertFileExists($backendPath.'/ServiceProvider.php');
        } finally {
            File::deleteDirectory($backendPath);
        }
    }

    public function test_it_preserves_acronyms_as_single_slug_segments(): void
    {
        $this->artisan('make:module', [
            'name' => 'APIReferenceData',
            '--project' => 'ERP',
        ])->assertSuccessful();

        $this->assertFileExists($this->frontendRoot.'/erp/api-reference-data/index.tsx');
        $this->assertFileDoesNotExist($this->frontendRoot.'/e-r-p/api-reference-data/index.tsx');
        $this->assertFileDoesNotExist($this->frontendRoot.'/erp/a-p-i-reference-data/index.tsx');
    }
}
