<?php

namespace App\Support\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name : Module name, or namespace when the optional module argument is supplied}
        {module? : Optional module name, for example make:module StudentManagement Student}
        {--project= : Project/group namespace, for example Console or Accounting}
        {--force : Overwrite generated files when they already exist}
        {--without-frontend : Do not create the Inertia page scaffold}';

    protected $description = 'Create a DDD-lite modular feature scaffold under app/Modules/{Project}/{Module}.';

    public function handle(): int
    {
        [$project, $module] = $this->resolveNames(
            (string) $this->argument('name'),
            $this->argument('module'),
            $this->option('project'),
        );

        if ($module === '' || $project === '') {
            $this->error('Project dan module wajib punya nama valid.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $modulePath = rtrim((string) config('modules.backend_root', app_path('Modules')), '/\\')."/{$project}/{$module}";
        $namespace = "App\\Modules\\{$project}\\{$module}";
        $title = Str::headline($module);
        $projectSlug = $this->slugName($project);
        $moduleSlug = $this->slugName($module);
        $frontendPath = $projectSlug.'/'.$moduleSlug;
        $usesProjectRoutePrefix = $project !== 'Console';
        $routePrefix = $usesProjectRoutePrefix ? $projectSlug.'/'.$moduleSlug : $moduleSlug;
        $routeName = $usesProjectRoutePrefix
            ? Str::of($projectSlug)->replace('-', '.')->append('.')->append(Str::of($moduleSlug)->replace('-', '.'))->toString()
            : Str::of($moduleSlug)->replace('-', '.')->toString();

        $this->makeDirectories($modulePath);

        $files = [
            "{$modulePath}/module.php" => $this->moduleStub($namespace, $project, $module, $title, $moduleSlug),
            "{$modulePath}/module.json" => $this->jsonManifestStub($project, $module, $title, $moduleSlug),
            "{$modulePath}/README.md" => $this->readmeStub($project, $module, $title),
            "{$modulePath}/Presentation/Routes/web.php" => $this->routesStub($namespace, $module, $routePrefix, $routeName),
            "{$modulePath}/permissions.php" => $this->permissionsStub($moduleSlug),
            "{$modulePath}/navigation.php" => $this->navigationStub($title, $routePrefix),
            "{$modulePath}/ServiceProvider.php" => $this->providerStub($namespace, $module),
            "{$modulePath}/Presentation/Http/Controllers/{$module}Controller.php" => $this->controllerStub($namespace, $module, $frontendPath),
            "{$modulePath}/Application/Services/{$module}Service.php" => $this->serviceStub($namespace, $module),
        ];

        foreach ($files as $path => $contents) {
            $this->writeFile($path, $contents, $force);
        }

        if (! $this->option('without-frontend')) {
            $pagePath = config('modules.frontend_root').'/'.$frontendPath.'/index.tsx';
            $this->writeFile($pagePath, $this->frontendStub($title, $routePrefix), $force);
        }

        $this->components->info("Module {$project}/{$module} berhasil dibuat.");
        $this->line("Backend : app/Modules/{$project}/{$module}");
        $this->line("Route   : /{$routePrefix}");
        $this->line("Inertia : {$frontendPath}/index");

        return self::SUCCESS;
    }

    private function studlyName(string $name): string
    {
        return Str::of($name)
            ->replace(['-', '.', '/', '\\'], ' ')
            ->squish()
            ->studly()
            ->toString();
    }

    private function slugName(string $name): string
    {
        if (strtoupper($name) === $name) {
            return strtolower($name);
        }

        $words = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $name) ?? $name;
        $words = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $words) ?? $words;

        return Str::slug($words);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveNames(string $name, mixed $moduleArgument, mixed $projectOption): array
    {
        $project = (string) ($projectOption ?: config('modules.default_project', 'Console'));
        $module = $name;

        if (filled($moduleArgument)) {
            $project = (string) ($projectOption ?: $name);
            $module = (string) $moduleArgument;
        } elseif (! $projectOption && preg_match('/[:\/\\\\]/', $name) === 1) {
            $parts = preg_split('/[:\/\\\\]+/', $name, flags: PREG_SPLIT_NO_EMPTY) ?: [];

            if (count($parts) >= 2) {
                $project = (string) $parts[0];
                $module = (string) end($parts);
            }
        }

        return [
            $this->studlyName($project),
            $this->studlyName($module),
        ];
    }

    private function makeDirectories(string $modulePath): void
    {
        foreach ([
            'Application/DTOs',
            'Application/Services',
            'Database/Migrations',
            'Domain',
            'Infrastructure/Repositories',
            'Presentation/Http/Controllers',
            'Presentation/Http/Requests',
            'Presentation/Policies',
            'Presentation/Routes',
            'Tests/Feature',
        ] as $directory) {
            File::ensureDirectoryExists($modulePath.'/'.$directory);
        }
    }

    private function writeFile(string $path, string $contents, bool $force): void
    {
        File::ensureDirectoryExists(dirname($path));

        if (File::exists($path) && ! $force) {
            $this->components->warn("Skip existing: {$this->relativePath($path)}");

            return;
        }

        File::put($path, $contents);
        $this->components->task($this->relativePath($path));
    }

    private function relativePath(string $path): string
    {
        return Str::of($path)
            ->replace(base_path().DIRECTORY_SEPARATOR, '')
            ->replace('\\', '/')
            ->toString();
    }

    private function routesStub(string $namespace, string $module, string $routePrefix, string $routeName): string
    {
        return <<<PHP
<?php

use {$namespace}\\Presentation\\Http\\Controllers\\{$module}Controller;
use Illuminate\\Support\\Facades\\Route;

Route::middleware(['auth'])->prefix('{$routePrefix}')->name('{$routeName}.')->group(function () {
    Route::get('/', [{$module}Controller::class, 'index'])->name('index');
});

PHP;
    }

    private function moduleStub(string $namespace, string $project, string $module, string $title, string $routePrefix): string
    {
        return <<<PHP
<?php

use {$namespace}\\ServiceProvider;

return [
    'name' => '{$module}',
    'project' => '{$project}',
    'title' => '{$title}',
    'slug' => '{$routePrefix}',
    'description' => '{$title} module.',
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

PHP;
    }

    private function jsonManifestStub(string $project, string $module, string $title, string $slug): string
    {
        return json_encode([
            'name' => $module,
            'namespace' => $project,
            'title' => $title,
            'slug' => $slug,
            'architecture' => 'ddd-lite',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }

    private function readmeStub(string $project, string $module, string $title): string
    {
        return "# {$title}\n\n**Module:** `{$project}.{$module}`  \n**Architecture:** DDD-lite\n\n## Ownership\n\nJelaskan capability, ownership data, public contract, dan dependency module di sini sebelum menambah business rule.\n";
    }

    private function permissionsStub(string $routePrefix): string
    {
        return <<<PHP
<?php

return [
    'permissions' => [
        '{$routePrefix}.view',
        '{$routePrefix}.create',
        '{$routePrefix}.update',
        '{$routePrefix}.delete',
    ],
    'roles' => [
        'admin' => ['{$routePrefix}.view'],
        'staff' => [],
    ],
];

PHP;
    }

    private function navigationStub(string $title, string $routePrefix): string
    {
        return <<<PHP
<?php

return [
    'group' => 'Workspace',
    'sort' => 900,
    'items' => [
        [
            'title' => '{$title}',
            'url' => '/{$routePrefix}',
            'icon' => 'Boxes',
            'permissions' => ['{$routePrefix}.view'],
        ],
    ],
];

PHP;
    }

    private function providerStub(string $namespace, string $module): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\\Support\\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot(): void
    {
        //
    }
}

PHP;
    }

    private function controllerStub(string $namespace, string $module, string $frontendPath): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Presentation\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

class {$module}Controller
{
    public function index(Request \$request): Response
    {
        return Inertia::render('{$frontendPath}/index');
    }
}

PHP;
    }

    private function serviceStub(string $namespace, string $module): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Application\\Services;

class {$module}Service
{
    //
}

PHP;
    }

    private function transactionStub(string $namespace, string $module): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Transactions;

use Illuminate\\Support\\Facades\\DB;

class {$module}Transaction
{
    public function run(callable \$callback): mixed
    {
        return DB::transaction(\$callback);
    }
}

PHP;
    }

    private function supportPermissionsStub(string $namespace, string $routePrefix): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Support;

class Permissions
{
    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return [
            '{$routePrefix}.view',
            '{$routePrefix}.create',
            '{$routePrefix}.update',
            '{$routePrefix}.delete',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            'admin' => ['{$routePrefix}.view'],
            'staff' => [],
        ];
    }
}

PHP;
    }

    private function frontendStub(string $title, string $routePrefix): string
    {
        return <<<TSX
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Boxes } from 'lucide-react';

const breadcrumbs = [
    {
        title: '{$title}',
        href: '/{$routePrefix}',
    },
];

export default function {$this->studlyName($title)}Index() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="{$title}" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <section className="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border">
                    <div className="flex items-start gap-4">
                        <div className="rounded-2xl bg-primary/10 p-3 text-primary">
                            <Boxes className="h-6 w-6" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">Generated module</p>
                            <h1 className="mt-1 text-2xl font-semibold tracking-tight text-foreground">{$title}</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                Halaman awal module sudah siap. Lanjutkan dengan memecah komponen UI, DTO, service,
                                policy, request, dan transaction sesuai kebutuhan fitur.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
TSX;
    }
}
