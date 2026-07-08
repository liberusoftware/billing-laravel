<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Modules\ModuleManager;
use App\Observers\ProjectObserver;
use App\Observers\TaskObserver;
use App\Observers\TimeEntryObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Override;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(
            ModuleManager::class,
            fn (): ModuleManager => new ModuleManager
        );
    }

    public function boot(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $this->configureModels();
        $this->configureUrl();
        $this->configureVite();
        $this->configurePassword();
        $this->configureObservers();
    }

    private function configureObservers(): void
    {
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        TimeEntry::observe(TimeEntryObserver::class);
    }

    private function configureModels(): void
    {
        Model::shouldBeStrict();
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
    }

    private function configurePassword(): void
    {
        Password::defaults(
            static function () {
                return Password::min(12)        // NIST 800-63B: minimum 12 characters
                    ->mixedCase()      // At least one uppercase and one lowercase
                    ->numbers()        // At least one digit
                    ->symbols()        // At least one symbol (@$!%*#?&)
                    ->uncompromised(); // Check against breach database
            },
        );
    }

    private function configureUrl(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureVite(): void
    {
        Vite::usePrefetchStrategy('aggressive');
    }
}
