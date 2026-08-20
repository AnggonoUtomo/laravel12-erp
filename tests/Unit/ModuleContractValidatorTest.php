<?php

namespace Tests\Unit;

use App\Support\Modules\ModuleContractValidator;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleContractValidatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/module-contracts/'.Str::uuid());
        config(['modules.backend_root' => $this->root]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_valid_contract_allows_explicitly_disabled_navigation_export(): void
    {
        $this->writeModule('Console', 'Activities', navigation: false);

        $this->assertSame([], app(ModuleContractValidator::class)->validate());
    }

    public function test_contract_reports_missing_export_and_unknown_dependency(): void
    {
        $this->writeModule('Inventory', 'Items', dependencies: ['MissingModule']);
        File::delete($this->root.'/Inventory/Items/routes.php');

        $codes = collect(app(ModuleContractValidator::class)->validate())->pluck('code');

        $this->assertTrue($codes->contains('missing_export'));
        $this->assertTrue($codes->contains('unknown_dependency'));
    }

    public function test_contract_rejects_navigation_without_group_items_shape(): void
    {
        $this->writeModule('Operations', 'Contracts');
        File::put(
            $this->root.'/Operations/Contracts/navigation.php',
            "<?php return [['group' => 'Operations', 'title' => 'Contracts', 'url' => '/operations/contracts']];",
        );

        $codes = collect(app(ModuleContractValidator::class)->validate())->pluck('code');

        $this->assertTrue($codes->contains('invalid_navigation'));
    }

    public function test_target_routes_are_validated_and_loaded_before_legacy_routes(): void
    {
        $this->writeModule('Console', 'Activities');
        $path = $this->root.'/Console/Activities';
        File::ensureDirectoryExists($path.'/Presentation/Routes');
        File::put($path.'/Presentation/Routes/web.php', '<?php return [];');

        $this->assertSame([], app(ModuleContractValidator::class)->validate());
        $this->assertSame(
            [str_replace('\\', '/', $path.'/Presentation/Routes/web.php')],
            array_map(fn (string $route) => str_replace('\\', '/', $route), ModuleRegistry::routeFiles()),
        );
    }

    private function writeModule(string $project, string $name, bool $navigation = true, array $dependencies = []): void
    {
        $path = $this->root."/{$project}/{$name}";
        File::ensureDirectoryExists($path);
        $manifest = [
            'name' => $name, 'project' => $project, 'title' => $name, 'slug' => Str::kebab($name),
            'description' => 'Test module.', 'version' => '1.0.0', 'enabled' => true, 'providers' => [],
            'dependencies' => $dependencies,
            'exports' => ['routes' => true, 'permissions' => true, 'navigation' => $navigation],
            'events' => [], 'listeners' => [], 'integrations' => [],
        ];
        File::put($path.'/module.php', '<?php return '.var_export($manifest, true).';');
        File::put($path.'/routes.php', '<?php return [];');
        File::put($path.'/permissions.php', '<?php return [];');
        if ($navigation) {
            File::put($path.'/navigation.php', "<?php return ['group' => '{$project}', 'items' => []];");
        }
    }
}
