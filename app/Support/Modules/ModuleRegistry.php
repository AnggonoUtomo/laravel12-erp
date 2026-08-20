<?php

namespace App\Support\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleRegistry
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function modules(): Collection
    {
        $root = (string) config('modules.backend_root', app_path('Modules'));

        if (! File::isDirectory($root)) {
            return collect();
        }

        return collect(File::directories($root))
            ->flatMap(function (string $path) {
                if (self::isModuleDirectory($path)) {
                    return [self::moduleDefinition($path)];
                }

                return collect(File::directories($path))
                    ->filter(fn (string $modulePath) => self::isModuleDirectory($modulePath))
                    ->map(fn (string $modulePath) => self::moduleDefinition($modulePath, basename($path)))
                    ->all();
            })
            ->reject(fn (array $module) => $module['name'] === 'SampleModule')
            ->filter(fn (array $module) => $module['enabled'])
            ->sortBy(fn (array $module) => ($module['group'] ?? '').'.'.$module['name'])
            ->values();
    }

    private static function isModuleDirectory(string $path): bool
    {
        return File::exists($path.DIRECTORY_SEPARATOR.'module.php')
            || File::exists($path.DIRECTORY_SEPARATOR.'module.json')
            || File::exists($path.DIRECTORY_SEPARATOR.'Presentation'.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'web.php')
            || File::exists($path.DIRECTORY_SEPARATOR.'routes.php')
            || File::exists($path.DIRECTORY_SEPARATOR.'permissions.php')
            || File::exists($path.DIRECTORY_SEPARATOR.'navigation.php')
            || File::isDirectory($path.DIRECTORY_SEPARATOR.'Providers');
    }

    /**
     * @return array<string, mixed>
     */
    private static function moduleDefinition(string $path, ?string $group = null): array
    {
        $name = basename($path);
        $manifest = self::manifest($path);
        $project = (string) ($manifest['project'] ?? $group ?? '');
        $namespace = (string) ($manifest['namespace'] ?? 'App\\Modules\\'.($project !== '' ? $project.'\\' : '').$name);

        return [
            'name' => (string) ($manifest['name'] ?? $name),
            'project' => $project !== '' ? $project : null,
            'group' => $project !== '' ? $project : null,
            'slug' => (string) ($manifest['slug'] ?? Str::kebab($name)),
            'title' => (string) ($manifest['title'] ?? Str::headline($name)),
            'description' => (string) ($manifest['description'] ?? ''),
            'version' => (string) ($manifest['version'] ?? '1.0.0'),
            'enabled' => (bool) ($manifest['enabled'] ?? true),
            'path' => $path,
            'namespace' => $namespace,
            'providers' => self::normalizeProviders($manifest['providers'] ?? null, $namespace, $path),
            'dependencies' => $manifest['dependencies'] ?? [],
            'exports' => $manifest['exports'] ?? [],
            'events' => $manifest['events'] ?? [],
            'listeners' => $manifest['listeners'] ?? [],
            'integrations' => $manifest['integrations'] ?? [],
            'manifest' => $manifest,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function manifest(string $path): array
    {
        $manifestPath = $path.DIRECTORY_SEPARATOR.'module.php';

        if (! File::exists($manifestPath)) {
            return [];
        }

        $manifest = require $manifestPath;

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * @return array<int, class-string>
     */
    private static function normalizeProviders(mixed $providers, string $namespace, string $path): array
    {
        if (is_array($providers)) {
            return collect($providers)
                ->filter(fn (mixed $provider) => is_string($provider) && class_exists($provider))
                ->values()
                ->all();
        }

        $providerPath = $path.DIRECTORY_SEPARATOR.'Providers';

        if (! File::isDirectory($providerPath)) {
            return [];
        }

        return collect(File::files($providerPath))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), 'ServiceProvider.php'))
            ->map(fn ($file) => $namespace.'\\Providers\\'.Str::before($file->getFilename(), '.php'))
            ->filter(fn (string $class) => class_exists($class))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function routeFiles(): array
    {
        return self::modules()
            ->map(function (array $module): ?string {
                $target = $module['path'].DIRECTORY_SEPARATOR.'Presentation'.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'web.php';
                $legacy = $module['path'].DIRECTORY_SEPARATOR.'routes.php';

                return File::exists($target) ? $target : (File::exists($legacy) ? $legacy : null);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, class-string>
     */
    public static function serviceProviders(): array
    {
        return self::modules()
            ->flatMap(fn (array $module) => $module['providers'] ?? [])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function navigations(): Collection
    {
        return self::modules()
            ->map(fn (array $module) => $module['path'].DIRECTORY_SEPARATOR.'navigation.php')
            ->filter(fn (string $path) => File::exists($path))
            ->map(fn (string $path) => require $path)
            ->filter(fn (mixed $navigation) => is_array($navigation))
            ->sortBy(fn (array $navigation) => $navigation['sort'] ?? 999)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    public static function permissionFiles(): Collection
    {
        return self::modules()
            ->map(fn (array $module) => $module['path'].DIRECTORY_SEPARATOR.'permissions.php')
            ->filter(fn (string $path) => File::exists($path))
            ->values();
    }

    /**
     * @return Collection<int, class-string>
     */
    public static function permissionProviders(): Collection
    {
        return self::modules()
            ->filter(fn (array $module) => File::exists($module['path'].DIRECTORY_SEPARATOR.'Support'.DIRECTORY_SEPARATOR.'Permissions.php'))
            ->map(fn (array $module) => $module['namespace'].'\\Support\\Permissions')
            ->filter(fn (string $class) => class_exists($class))
            ->filter(fn (string $class) => method_exists($class, 'permissions'))
            ->filter(fn (string $class) => method_exists($class, 'defaultRolePermissions'))
            ->values();
    }

    /**
     * @return Collection<class-string, array<int, class-string>>
     */
    public static function eventListeners(): Collection
    {
        return self::modules()
            ->reduce(function (Collection $events, array $module) {
                foreach (($module['listeners'] ?? []) as $event => $listeners) {
                    if (! is_string($event) || ! class_exists($event)) {
                        continue;
                    }

                    $normalizedListeners = collect(is_array($listeners) ? $listeners : [$listeners])
                        ->filter(fn (mixed $listener) => is_string($listener) && class_exists($listener))
                        ->values()
                        ->all();

                    if ($normalizedListeners !== []) {
                        $events->put($event, array_values(array_unique([
                            ...($events->get($event, [])),
                            ...$normalizedListeners,
                        ])));
                    }
                }

                return $events;
            }, collect());
    }
}
