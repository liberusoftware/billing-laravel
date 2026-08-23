<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ModuleAutoloadCommand extends Command
{
    protected $signature = 'module:dump-autoload';

    protected $description = 'Run composer dump-autoload to register new modules';

    public function handle(): int
    {
        $this->info('Refreshing Composer autoloader...');

        $composer = file_exists(base_path('composer.phar'))
            ? [
                PHP_BINARY,
                'composer.phar',
            ]
            : ['composer'];

        $process = new Process(
            [
                ...$composer,
                'dump-autoload',
                '--optimize',
            ],
            base_path()
        );

        $process->setTimeout(120);
        $process->run(
            function (string $type, string $buffer) {
                $this->output->write($buffer);
            }
        );

        if (! $process->isSuccessful()) {
            $this->error('composer dump-autoload failed.');

            return self::FAILURE;
        }

        $this->info('Autoloader refreshed successfully.');

        return self::SUCCESS;
    }
}
