<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InstallationScriptService
{
    protected string $controlPanel;

    protected string $gitRepo;

    protected string $domain;

    protected string $dbName;

    protected string $dbUser;

    public function __construct(
        string $controlPanel,
        string $gitRepo,
        string $domain,
        string $dbName,
        string $dbUser,
        protected mixed $dbPass,
    ) {
        $this->controlPanel = strtolower($controlPanel);
        $this->gitRepo = $this->validateGitRepo($gitRepo);
        $this->domain = $this->validateIdentifier(
            $domain,
            'domain',
            '/^[a-zA-Z0-9._-]+$/'
        );
        $this->dbName = $this->validateIdentifier(
            $dbName,
            'database name',
            '/^\w+$/'
        );
        $this->dbUser = $this->validateIdentifier(
            $dbUser,
            'database user',
            '/^\w+$/'
        );
    }

    protected function validateGitRepo(string $repo): string
    {
        if (! filter_var(
            $repo,
            FILTER_VALIDATE_URL
        ) && ! preg_match(
            '/^git@[a-zA-Z0-9._-]+:[a-zA-Z0-9._\/-]+\.git$/',
            $repo
        )) {
            throw new Exception('Invalid git repository URL');
        }

        return $repo;
    }

    protected function validateIdentifier(string $value, string $name, string $pattern): string
    {
        if (! preg_match(
            $pattern,
            $value
        )) {
            throw new Exception('Invalid '.$name.': only alphanumeric characters, underscores, hyphens, and dots allowed'); // phpcs:ignore WordPress.Security.EscapeOutput -- Laravel exception message, not HTML output
        }

        return $value;
    }

    public function generateScript(): string
    {
        $domain = escapeshellarg($this->domain);    // phpcs:ignore -- nosemgrep
        $gitRepo = escapeshellarg($this->gitRepo);   // phpcs:ignore -- nosemgrep
        $dbName = escapeshellarg($this->dbName);    // phpcs:ignore -- nosemgrep
        $dbUser = escapeshellarg($this->dbUser);    // phpcs:ignore -- nosemgrep
        $dbPass = escapeshellarg((string) $this->dbPass);    // phpcs:ignore -- nosemgrep

        $installDir = "~/laravel-apps/{$domain}";
        $publicHtmlPath = $this->getPublicHtmlPath();

        $script = [
            '#!/bin/bash',
            'set -e',
            '',
            '# Create installation directory',
            "mkdir -p {$installDir}",
            "cd {$installDir}",
            '',
            '# Clone repository',
            "git clone {$gitRepo} .",
            '',
            '# Install composer dependencies',
            'composer install --no-scripts --no-dev --optimize-autoloader',
            '',
            '# Install npm dependencies and build assets',
            'npm install',
            'npm run build',
            '',
            '# Setup Laravel environment',
            'cp .env.example .env',
            "sed -i \"s/^DB_DATABASE=.*/DB_DATABASE={$dbName}/\" .env",
            "sed -i \"s/^DB_USERNAME=.*/DB_USERNAME={$dbUser}/\" .env",
            "sed -i \"s/^DB_PASSWORD=.*/DB_PASSWORD={$dbPass}/\" .env",
            '',
            '# Generate application key',
            'php artisan key:generate',
            '',
            '# Create storage link',
            'php artisan storage:link',
            '',
            '# Run migrations and seeders',
            'php artisan migrate --force',
            'php artisan db:seed --force',
            '',
            '# Set proper permissions',
            'find . -type f -exec chmod 644 {} \\;',
            'find . -type d -exec chmod 755 {} \\;',
            'chmod -R 775 storage bootstrap/cache',
            '',
            '# Create symbolic link',
            "ln -sf {$installDir}/public {$publicHtmlPath}",
            '',
            'echo "Installation completed successfully!"',
        ];

        return implode(
            "\n",
            $script
        );
    }

    protected function getPublicHtmlPath(): string
    {
        return match ($this->controlPanel) {
            'cpanel' => '~/public_html',
            'plesk' => '~/httpdocs',
            'directadmin' => "~/domains/{$this->domain}/public_html",
            'virtualmin' => '~/public_html',
            default => throw new Exception("Unsupported control panel: {$this->controlPanel}"),
        };
    }

    public function execute(): bool
    {
        try {
            $script = $this->generateScript();
            $scriptPath = tempnam(
                sys_get_temp_dir(),
                'laravel_install_'
            );

            File::put(
                $scriptPath,
                $script
            );
            chmod(
                $scriptPath,
                0755
            );

            $output = shell_exec($scriptPath.' 2>&1');
            unlink($scriptPath);

            Log::info(
                'Installation script executed successfully',
                [
                    'domain' => $this->domain,
                    'output' => $output,
                ]
            );

            return true;
        } catch (Exception $e) {
            Log::error(
                'Installation script failed',
                [
                    'domain' => $this->domain,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}
