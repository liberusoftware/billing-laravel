<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\Module as ModuleModel;
use App\Modules\Contracts\ModuleInterface;
use App\Modules\Support\ExternalModuleLoader;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleManager
{
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = collect();
        $this->loadModules();
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    public function enabled(): Collection
    {
        return $this->modules->filter(fn ($module) => $module->isEnabled());
    }

    public function disabled(): Collection
    {
        return $this->modules->filter(fn ($module): bool => ! $module->isEnabled());
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn ($module): bool => $module->getName() === $name);
    }

    public function find(string $name): ?ModuleInterface
    {
        return $this->get($name);
    }

    public function has(string $name): bool
    {
        return $this->modules->contains(fn ($module): bool => $module->getName() === $name);
    }

    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->enable();

        try {
            $record = ModuleModel::firstOrNew(['name' => $module->getName()]);
            $record->enabled = true;
            $record->version = $module->getVersion();
            $record->description = $module->getDescription();
            $record->dependencies = $module->getDependencies();
            $record->config = $module->getConfig();
            $record->save();
        } catch (Throwable $e) {
            Log::warning("Failed to persist enabled state for module '{$name}': ".$e->getMessage());
        }

        $this->clearCache();

        return true;
    }

    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();

        try {
            $record = ModuleModel::firstOrNew(['name' => $module->getName()]);
            $record->enabled = false;
            $record->save();
        } catch (Throwable $e) {
            Log::warning("Failed to persist disabled state for module '{$name}': ".$e->getMessage());
        }

        $this->clearCache();

        return true;
    }

    public function install(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->install();

        try {
            $record = ModuleModel::firstOrNew(['name' => $module->getName()]);
            $record->enabled = true;
            $record->version = $module->getVersion();
            $record->description = $module->getDescription();
            $record->dependencies = $module->getDependencies();
            $record->config = $module->getConfig();
            $record->save();
        } catch (Throwable $e) {
            Log::warning("Failed to persist install state for module '{$name}': ".$e->getMessage());
        }

        $this->clearCache();

        return true;
    }

    public function uninstall(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot uninstall module {$name} as other modules depend on it.");
        }

        $module->uninstall();

        try {
            $record = ModuleModel::firstOrNew(['name' => $module->getName()]);
            $record->enabled = false;
            $record->save();
        } catch (Throwable $e) {
            Log::warning("Failed to persist uninstall state for module '{$name}': ".$e->getMessage());
        }

        $this->clearCache();

        return true;
    }

    public function register(ModuleInterface $module): void
    {
        $this->modules->put(
            $module->getName(),
            $module
        );
    }

    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return [];
        }

        return [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'description' => $module->getDescription(),
            'dependencies' => $module->getDependencies(),
            'enabled' => $module->isEnabled(),
            'config' => $module->getConfig(),
        ];
    }

    public function getAllModulesInfo(): array
    {
        return $this->modules->map(fn ($module): array => $this->getModuleInfo($module->getName()))->toArray();
    }

    public function clearCache(): void
    {
        Cache::forget(
            config(
                'modules.cache_key',
                'app.modules'
            )
        );
    }

    public function checkHealth(string $name): array
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return [
                'name' => $name,
                'healthy' => false,
                'errors' => ["Module '{$name}' not found."],
                'warnings' => [],
            ];
        }

        $errors = [];
        $warnings = [];

        foreach ($module->getDependencies() as $dep) {
            $depModule = $this->get($dep);
            if (! $depModule) {
                $errors[] = "Missing dependency: {$dep}";
            } elseif (! $depModule->isEnabled()) {
                $warnings[] = "Dependency disabled: {$dep}";
            }
        }

        if ($module->isEnabled() && ! $this->checkDependencies($module)) {
            $errors[] = 'Module is enabled but has unmet dependencies.';
        }

        return [
            'name' => $name,
            'healthy' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function checkAllHealth(): array
    {
        return $this->modules
            ->map(fn ($m): array => $this->checkHealth($m->getName()))
            ->keyBy('name')
            ->toArray();
    }

    protected function loadModules(): void
    {
        $cacheEnabled = config(
            'modules.cache',
            true
        ) && ! config(
            'modules.development',
            false
        );

        if ($cacheEnabled) {
            $cached = Cache::get(
                config(
                    'modules.cache_key',
                    'app.modules'
                )
            );
            if ($cached !== null) {
                $this->modules = collect($cached);

                return;
            }
        }

        $this->discoverLocalModules();
        $this->loadExternalModules();

        if ($cacheEnabled) {
            Cache::put(
                config(
                    'modules.cache_key',
                    'app.modules'
                ),
                $this->modules->toArray(),
                config(
                    'modules.cache_ttl',
                    3600
                )
            );
        }
    }

    protected function discoverLocalModules(): void
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
        if (File::exists($altPath)) {
            $paths[$altPath] = config(
                'modules.alt_namespace',
                'Modules'
            );
        }

        foreach ($paths as $modulesPath => $namespace) {
            if (! File::exists($modulesPath)) {
                continue;
            }

            foreach (File::directories($modulesPath) as $modulePath) {
                $moduleName = basename((string) $modulePath);

                // Try two conventions:
                //   1. Dir = "Blog"      → class App\Modules\Blog\BlogModule  (make:module Blog)
                //   2. Dir = "BlogModule"→ class App\Modules\BlogModule\BlogModule (manual/legacy)
                $candidates = [
                    "{$namespace}\\{$moduleName}\\{$moduleName}Module",
                    "{$namespace}\\{$moduleName}\\{$moduleName}",
                ];

                $moduleClass = null;
                foreach ($candidates as $candidate) {
                    if (class_exists($candidate)) {
                        $moduleClass = $candidate;
                        break;
                    }
                }

                if ($moduleClass === null) {
                    continue;
                }

                try {
                    $module = new $moduleClass;
                } catch (Throwable $e) {
                    Log::warning("Failed to instantiate module '{$moduleName}': ".$e->getMessage());

                    continue;
                }

                if ($module instanceof ModuleInterface) {
                    $this->register($module);

                    try {
                        ModuleModel::updateOrCreate(
                            ['name' => $module->getName()],
                            [
                                'version' => $module->getVersion(),
                                'description' => $module->getDescription(),
                                'dependencies' => $module->getDependencies(),
                                'config' => $module->getConfig(),
                            ]
                        );
                    } catch (Throwable) {
                        // DB not ready during initial setup — skip silently
                    }
                }
            }
        }
    }

    protected function loadExternalModules(): void
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
            if (! $this->has($module->getName())) {
                $this->register($module);
            }
        }
    }

    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dependencyModule = $this->get($dependency);
            if (! $dependencyModule || ! $dependencyModule->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    protected function hasDependents(string $moduleName): bool
    {
        return $this->enabled()->contains(
            fn ($module): bool => in_array(
                $moduleName,
                $module->getDependencies()
            )
        );
    }
}
