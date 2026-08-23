<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\Module as ModuleModel;
use App\Modules\Support\ExternalModuleLoader;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Override;
use ReflectionClass;
use Throwable;

class ModuleServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        try {
            $this->registerModules();
            $this->registerExternalModules();
        } catch (Throwable) {
            // Silently skip during initial setup (no DB / cache yet)
        }
    }

    public function boot(): void
    {
        try {
            $this->bootModules();
        } catch (Throwable) {
            // Silently skip during initial setup (no DB / cache yet)
        }
    }

    protected function registerModules(): void
    {
        foreach ($this->modulePaths() as $modulesPath => $namespace) {
            if (! File::exists($modulesPath)) {
                continue;
            }

            foreach (File::directories($modulesPath) as $modulePath) {
                $moduleName = basename((string) $modulePath);
                $this->registerModule(
                    $moduleName,
                    $modulePath,
                    $namespace
                );
            }
        }
    }

    protected function registerModule(string $moduleName, string $modulePath, string $namespace): void
    {
        // Support both legacy (app/Modules/{Name}/) and modular (app-modules/{Name}/src/) layouts
        $srcPath = File::exists("{$modulePath}/src") ? "{$modulePath}/src" : $modulePath;

        $providerPath = $srcPath.'/Providers/'.$moduleName.'ServiceProvider.php';
        if (File::exists($providerPath)) {
            $providerClass = "{$namespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        $configPath = $srcPath.'/config';
        if (! File::exists($configPath)) {
            $configPath = $modulePath.'/config';
        }

        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $configFile) {
                $configName = Str::snake($moduleName).'.'.$configFile->getFilenameWithoutExtension();
                $this->mergeConfigFrom(
                    $configFile->getPathname(),
                    $configName
                );
            }
        }

        // Migrations always available (needed for artisan migrate even when module is disabled)
        $migrationsPath = $modulePath.'/database/migrations';
        if (! File::exists($migrationsPath)) {
            $migrationsPath = $srcPath.'/database/migrations';
        }

        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Routes, views, and translations only loaded for enabled modules
        if (! $this->isModuleEnabled($moduleName)) {
            return;
        }

        $this->registerModuleRoutes(
            $moduleName,
            $modulePath,
            $srcPath
        );

        foreach ([
            'resources/views' => Str::snake($moduleName),
            'resources/lang' => Str::snake($moduleName),
        ] as $subPath => $ns) {
            $fullPath = $srcPath.'/'.$subPath;
            if (! File::exists($fullPath)) {
                $fullPath = $modulePath.'/'.$subPath;
            }
            if (File::exists($fullPath)) {
                if (Str::endsWith(
                    $subPath,
                    'views'
                )) {
                    $this->loadViewsFrom(
                        $fullPath,
                        $ns
                    );
                } else {
                    $this->loadTranslationsFrom(
                        $fullPath,
                        $ns
                    );
                }
            }
        }
    }

    protected function registerModuleRoutes(string $moduleName, string $modulePath, string $srcPath): void
    {
        $routesPath = $modulePath.'/routes';
        if (! File::exists($routesPath)) {
            $routesPath = $srcPath.'/routes';
        }

        if (! File::exists($routesPath)) {
            return;
        }

        foreach ([
            'web.php',
            'api.php',
            'admin.php',
        ] as $file) {
            $path = $routesPath.'/'.$file;
            if (File::exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function bootModules(): void
    {
        foreach ($this->modulePaths() as $modulesPath => $namespace) {
            if (! File::exists($modulesPath)) {
                continue;
            }

            foreach (File::directories($modulesPath) as $modulePath) {
                $moduleName = basename((string) $modulePath);
                $this->bootModule(
                    $moduleName,
                    $modulePath,
                    $namespace
                );
            }
        }
    }

    protected function bootModule(string $moduleName, string $modulePath, string $namespace): void
    {
        $srcPath = File::exists("{$modulePath}/src") ? "{$modulePath}/src" : $modulePath;

        $assetsPath = $srcPath.'/resources/assets';
        if (! File::exists($assetsPath)) {
            $assetsPath = $modulePath.'/resources/assets';
        }

        if (File::exists($assetsPath)) {
            $this->publishes(
                [
                    $assetsPath => public_path("modules/{$moduleName}"),
                ],
                Str::snake($moduleName).'-assets'
            );
        }

        $configPath = $srcPath.'/config';
        if (! File::exists($configPath)) {
            $configPath = $modulePath.'/config';
        }

        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $configFile) {
                $this->publishes(
                    [
                        $configFile->getPathname() => config_path(Str::snake($moduleName).'.'.$configFile->getFilename()),
                    ],
                    Str::snake($moduleName).'-config'
                );
            }
        }

        $this->registerModuleFilament(
            $moduleName,
            $modulePath,
            $srcPath,
            $namespace
        );
    }

    protected function registerModuleFilament(string $moduleName, string $modulePath, string $srcPath, string $namespace): void
    {
        $filamentPath = $srcPath.'/Filament';
        if (! File::exists($filamentPath)) {
            $filamentPath = $modulePath.'/Filament';
        }

        if (! File::isDirectory($filamentPath) || ! class_exists(PanelRegistry::class)) {
            return;
        }

        $filamentNamespace = "{$namespace}\\{$moduleName}\\Filament";

        $this->callAfterResolving(
            PanelRegistry::class,
            function (PanelRegistry $registry) use ($filamentPath, $filamentNamespace): void {
                foreach ($registry->all() as $panel) {
                    if (File::isDirectory($filamentPath.'/Resources')) {
                        $panel->discoverResources(
                            in: $filamentPath.'/Resources',
                            for: $filamentNamespace.'\\Resources',
                        );
                    }

                    if (File::isDirectory($filamentPath.'/Pages')) {
                        $panel->discoverPages(
                            in: $filamentPath.'/Pages',
                            for: $filamentNamespace.'\\Pages',
                        );
                    }

                    if (File::isDirectory($filamentPath.'/Widgets')) {
                        $panel->discoverWidgets(
                            in: $filamentPath.'/Widgets',
                            for: $filamentNamespace.'\\Widgets',
                        );
                    }
                }
            }
        );
    }

    protected function registerExternalModules(): void
    {
        if (! config(
            'modules.load_composer_modules',
            false
        ) && empty(
            config(
                'modules.external_paths',
                []
            )
        )) {
            return;
        }

        $loader = new ExternalModuleLoader;

        foreach ($loader->load() as $module) {
            $moduleName = $module->getName();
            $this->registerModule(
                $moduleName,
                dirname(new ReflectionClass($module)->getFileName()),
                config(
                    'modules.namespace',
                    'App\\Modules'
                )
            );
        }
    }

    /**
     * Returns all module root paths mapped to their PSR-4 namespace.
     *
     * @return array<string, string>
     */
    protected function modulePaths(): array
    {
        $paths = [
            config(
                'modules.path',
                app_path('Modules')
            ) => config(
                'modules.namespace',
                'App\\Modules'
            ),
        ];

        $altPath = base_path('app-modules');
        $altNamespace = config(
            'modules.alt_namespace',
            'Modules'
        );

        if (File::exists($altPath)) {
            $paths[$altPath] = $altNamespace;
        }

        foreach (config(
            'modules.external_paths',
            []
        ) as $extPath) {
            if (File::exists($extPath)) {
                $paths[$extPath] = config(
                    'modules.namespace',
                    'App\\Modules'
                );
            }
        }

        return $paths;
    }

    protected function isModuleEnabled(string $moduleName): bool
    {
        try {
            $record = ModuleModel::where(
                'name',
                $moduleName
            )->first();
            if ($record !== null) {
                return (bool) $record->enabled;
            }
        } catch (Throwable) {
            // DB not ready (fresh install or no migration run yet) — default to enabled
        }

        return true;
    }
}
