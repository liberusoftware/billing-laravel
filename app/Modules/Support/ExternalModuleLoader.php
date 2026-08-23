<?php

declare(strict_types=1);

namespace App\Modules\Support;

use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Facades\File;

class ExternalModuleLoader
{
    /** @var string[] Paths already scanned to prevent duplicate loading */
    protected array $loadedPaths = [];

    /**
     * Load modules from configured external paths and Composer vendor packages.
     *
     * @return ModuleInterface[]
     */
    public function load(): array
    {
        $modules = [];

        foreach (config(
            'modules.external_paths',
            []
        ) as $path) {
            array_push(
                $modules,
                ...$this->loadFromPath($path)
            );
        }

        if (config(
            'modules.load_composer_modules',
            false
        )) {
            array_push(
                $modules,
                ...$this->loadFromComposer()
            );
        }

        return $modules;
    }

    /**
     * Load modules from a filesystem path by scanning for module.json descriptors.
     *
     * @return ModuleInterface[]
     */
    public function loadFromPath(string $path): array
    {
        $realPath = realpath($path);

        if ($realPath === false || ! is_dir($realPath) || in_array(
            $realPath,
            $this->loadedPaths,
            true
        )) {
            return [];
        }

        $this->loadedPaths[] = $realPath;

        $modules = [];

        foreach (File::directories($realPath) as $modulePath) {
            $module = $this->resolveModule($modulePath);
            if ($module !== null) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Scan Composer vendor packages for packages that expose a laravel-module extra key.
     *
     * @return ModuleInterface[]
     */
    protected function loadFromComposer(): array
    {
        $modules = [];
        $vendorPath = base_path('vendor');
        $installedJson = $vendorPath.'/composer/installed.json';

        if (! File::exists($installedJson)) {
            return [];
        }

        $installed = json_decode(
            File::get($installedJson),
            true
        );
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            $extra = $package['extra']['laravel-module'] ?? null;

            if ($extra === null) {
                continue;
            }

            $packagePath = $vendorPath.'/'.($package['name'] ?? '');

            if (! is_dir($packagePath)) {
                continue;
            }

            $module = $this->resolveModule(
                $packagePath,
                $package['autoload']['psr-4'] ?? []
            );
            if ($module !== null) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Attempt to resolve a ModuleInterface instance from a directory.
     *
     * Tries several class naming conventions before giving up.
     *
     * @param  array<string,string>  $psr4Map  Optional PSR-4 namespace map for vendor packages
     */
    protected function resolveModule(string $modulePath, array $psr4Map = []): ?ModuleInterface
    {
        $moduleName = basename($modulePath);
        $candidates = $this->buildCandidateClasses(
            $moduleName,
            $modulePath,
            $psr4Map
        );

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                $instance = new $class;
                if ($instance instanceof ModuleInterface) {
                    return $instance;
                }
            }
        }

        return null;
    }

    /**
     * Build a list of candidate fully-qualified class names for a given module directory.
     *
     * @param  array<string,string>  $psr4Map
     * @return string[]
     */
    protected function buildCandidateClasses(string $moduleName, string $modulePath, array $psr4Map): array
    {
        $candidates = [
            "App\\Modules\\{$moduleName}\\{$moduleName}Module",
            "App\\Modules\\{$moduleName}\\{$moduleName}",
            "Modules\\{$moduleName}\\{$moduleName}Module",
            "Modules\\{$moduleName}\\{$moduleName}",
        ];

        foreach ($psr4Map as $namespace => $path) {
            $namespace = rtrim(
                $namespace,
                '\\'
            );
            $candidates[] = "{$namespace}\\{$moduleName}Module";
            $candidates[] = "{$namespace}\\{$moduleName}";
        }

        return array_unique($candidates);
    }
}
