<?php

namespace Tests\Unit\Services;

use App\Services\InstallationScriptService;
use Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InstallationScriptSecurityTest extends TestCase
{
    public function test_database_password_is_written_as_data_without_shell_expansion(): void
    {
        $directory = sys_get_temp_dir().'/installation-security-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $password = '$(touch '.$directory.'/executed) `id` " \' $HOME \\ / & #';
        $service = new InstallationScriptService('cpanel', 'https://github.com/example/app.git', 'example.com', 'database', 'dbuser', $password);
        $script = $service->generateScript();
        $command = collect(explode("\n", $script))->first(fn (string $line): bool => str_starts_with($line, "printf '%s' "));

        try {
            file_put_contents($directory.'/.env', "APP_NAME=Example\nDB_DATABASE=old\nDB_USERNAME=old\nDB_PASSWORD=old\n");
            Process::fromShellCommandline($command, $directory)->mustRun();
            $environment = Dotenv::parse(file_get_contents($directory.'/.env'));

            $this->assertSame($password, $environment['DB_PASSWORD']);
            $this->assertSame('database', $environment['DB_DATABASE']);
            $this->assertSame('dbuser', $environment['DB_USERNAME']);
            $this->assertFileDoesNotExist($directory.'/executed');
            $this->assertSame(0600, fileperms($directory.'/.env') & 0777);
            $this->assertStringContainsString('chmod 600 .env', $script);

            file_put_contents($directory.'/.env', "APP_NAME=Example\n");
            Process::fromShellCommandline($command, $directory)->mustRun();
            $this->assertSame($password, Dotenv::parse(file_get_contents($directory.'/.env'))['DB_PASSWORD']);
        } finally {
            unlink($directory.'/.env');
            rmdir($directory);
        }
    }
}
