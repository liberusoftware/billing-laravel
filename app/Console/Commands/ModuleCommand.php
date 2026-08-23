<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\ModuleManager;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Manage application modules')]
#[Signature('module {action} {name?} {--force} {--format=table : Output format (table|json)}')]
class ModuleCommand extends Command
{
    public function __construct(protected ModuleManager $moduleManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listModules(),
            'enable' => $this->enableModule($this->argument('name')),
            'disable' => $this->disableModule($this->argument('name')),
            'install' => $this->installModule($this->argument('name')),
            'uninstall' => $this->uninstallModule($this->argument('name')),
            'create' => $this->createModule($this->argument('name')),
            'info' => $this->showModuleInfo($this->argument('name')),
            'health' => $this->healthCheck($this->argument('name')),
            default => $this->showHelp(),
        };
    }

    protected function listModules(): int
    {
        $modules = $this->moduleManager->all();

        if ($modules->isEmpty()) {
            $this->outputResult(
                [
                    'modules' => [],
                    'count' => 0,
                ]
            );

            return self::SUCCESS;
        }

        $rows = $modules->map(
            fn ($m): array => [
                'name' => $m->getName(),
                'version' => $m->getVersion(),
                'status' => $m->isEnabled() ? 'enabled' : 'disabled',
                'description' => $m->getDescription(),
                'dependencies' => implode(
                    ', ',
                    $m->getDependencies()
                ),
            ]
        )->values()->all();

        if ($this->option('format') === 'json') {
            $this->line(
                json_encode(
                    ['modules' => $rows],
                    JSON_PRETTY_PRINT
                )
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'Name',
                'Version',
                'Status',
                'Description',
                'Dependencies',
            ],
            array_map(
                fn (array $r): array => [
                    $r['name'],
                    $r['version'],
                    $r['status'] === 'enabled' ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                    $r['description'],
                    $r['dependencies'] ?: '—',
                ],
                $rows
            )
        );

        return self::SUCCESS;
    }

    protected function enableModule(?string $name): int
    {
        return $this->runModuleAction(
            'enable',
            $name,
            fn () => $this->moduleManager->enable($name)
        );
    }

    protected function disableModule(?string $name): int
    {
        return $this->runModuleAction(
            'disable',
            $name,
            fn () => $this->moduleManager->disable($name)
        );
    }

    protected function installModule(?string $name): int
    {
        return $this->runModuleAction(
            'install',
            $name,
            fn () => $this->moduleManager->install($name)
        );
    }

    protected function uninstallModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Uninstall module '{$name}'? This cannot be undone.")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        return $this->runModuleAction(
            'uninstall',
            $name,
            fn () => $this->moduleManager->uninstall($name)
        );
    }

    protected function createModule(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        return $this->call(
            'make:module',
            [
                'name' => $name,
                '--force' => $this->option('force'),
            ]
        );
    }

    protected function showModuleInfo(?string $name): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        $info = $this->moduleManager->getModuleInfo($name);

        if ($info === []) {
            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        }

        $this->outputResult($info);

        return self::SUCCESS;
    }

    protected function healthCheck(?string $name): int
    {
        $results = $name
            ? [$name => $this->moduleManager->checkHealth($name)]
            : $this->moduleManager->checkAllHealth();

        if ($this->option('format') === 'json') {
            $this->line(
                json_encode(
                    $results,
                    JSON_PRETTY_PRINT
                )
            );

            return self::SUCCESS;
        }

        $hasIssues = false;

        foreach ($results as $moduleName => $health) {
            $errors = $health['errors'] ?? $health['issues'] ?? [];
            $warnings = $health['warnings'] ?? [];

            if (empty($errors)) {
                $status = empty($warnings) ? 'healthy' : 'healthy (with warnings)';
                $this->line("<fg=green>✓</> {$moduleName}: {$status}");
                foreach ($warnings as $warning) {
                    $this->line("    <fg=yellow>⚠</> {$warning}");
                }
            } else {
                $hasIssues = true;
                $this->line("<fg=red>✗</> {$moduleName}:");
                foreach ($errors as $error) {
                    $this->line("    - {$error}");
                }
                foreach ($warnings as $warning) {
                    $this->line("    <fg=yellow>⚠</> {$warning}");
                }
            }
        }

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    protected function showHelp(): int
    {
        $this->info('Usage: php artisan module <action> [name] [options]');
        $this->newLine();
        $this->line('Actions:');
        $this->line('  list                  List all modules');
        $this->line('  enable   <name>       Enable a module');
        $this->line('  disable  <name>       Disable a module');
        $this->line('  install  <name>       Install a module');
        $this->line('  uninstall <name>      Uninstall a module (--force skips confirmation)');
        $this->line('  create   <name>       Scaffold a new module (delegates to make:module)');
        $this->line('  info     <name>       Show module information');
        $this->line('  health   [name]       Check module health (all modules if name omitted)');
        $this->newLine();
        $this->line('Options:');
        $this->line('  --format=json         Output as JSON (list, info, health)');

        return self::SUCCESS;
    }

    private function runModuleAction(string $verb, ?string $name, callable $action): int
    {
        if (! $name) {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        try {
            if ($action()) {
                $this->info("Module '{$name}' {$verb}d.");

                return self::SUCCESS;
            }

            $this->error("Module '{$name}' not found.");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->error("Failed to {$verb} '{$name}': ".$e->getMessage());

            return self::FAILURE;
        }
    }

    private function outputResult(array $data): void
    {
        if ($this->option('format') === 'json') {
            $this->line(
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                )
            );
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = implode(
                        ', ',
                        $value
                    ) ?: '—';
                }
                $this->line(ucfirst((string) $key).': '.(string) $value);
            }
        }
    }
}
