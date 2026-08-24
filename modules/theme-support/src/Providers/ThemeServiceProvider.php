<?php

namespace Liberu\Foundation\Theme\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;
use Liberu\Foundation\Theme\Console\ThemeCacheCommand;
use Liberu\Foundation\Theme\Console\ThemeClearCommand;
use Liberu\Foundation\Theme\Console\ThemeValidateCommand;
use Liberu\Foundation\Theme\Services\ThemeManager;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class, fn () => new ThemeManager());
        $this->app->alias(ThemeManager::class, 'theme');
        foreach ($this->app->make(ThemeManager::class)->providers() as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'theme');
        if ($this->app->runningInConsole()) {
            $this->commands([ThemeCacheCommand::class, ThemeClearCommand::class, ThemeValidateCommand::class]);
        }
        $themeManager = $this->app->make(ThemeManager::class);
        $themeManager->setTheme($this->determineActiveTheme());

        $this->registerBladeDirectives();

        View::composer('*', function (ViewContract $view) use ($themeManager): void {
            // ponytail: re-derive per view render, not just once at boot. ThemeManager
            // is a singleton resolved once per app lifecycle (long-lived under Octane;
            // reused across a whole test method under Pest) — without this, an
            // admin-changed site theme (or session/user pref set mid-lifecycle) would
            // never be picked up until the process restarts. getSiteTheme() is a cheap
            // in-memory settings read, so re-running this per view is negligible.
            $themeManager->setTheme($this->determineActiveTheme());

            $view->with('activeTheme', $themeManager->getActiveTheme());
            $view->with('themeConfig', $themeManager->getThemeConfig());
        });

        // Some responses do not render through a view composer (for example a
        // redirect or a streamed response). Reconcile once the request has run
        // so long-lived managers still reflect the authenticated preference.
        $this->app['events']->listen(RequestHandled::class, function () use ($themeManager): void {
            $themeManager->setTheme($this->determineActiveTheme());
        });
    }

    /**
     * Determine the active theme: authenticated user preference → session → site theme → config default.
     */
    protected function determineActiveTheme(): string
    {
        $themeManager = $this->app->make(ThemeManager::class);

        $user = auth()->user();
        $userTheme = $user instanceof Authenticatable
            ? ($user->getAttributes()['theme_preference'] ?? null)
            : null;
        if (is_string($userTheme) && $userTheme !== '' && $themeManager->themeExists($userTheme)) {
            return $userTheme;
        }

        $session = session('theme_preference');
        if (is_string($session) && $session !== '' && $themeManager->themeExists($session)) {
            return $session;
        }

        // Admin-selected site-wide theme (validated; safe fallback to config default).
        return $themeManager->getSiteTheme();
    }

    /**
     * Register custom Blade directives for themes.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('themeAsset', fn (string $expression): string => "<?php echo app('theme')->assetUrl({$expression}); ?>");

        // ponytail: @themeCss/@themeJs gate on the Vite MANIFEST, not disk — per-theme
        // Vite inputs are deferred, so until themes/*/{css,js} are added to vite.config.js
        // input + built, these emit nothing rather than throwing "Unable to locate file
        // in Vite manifest". They light up automatically once the assets are built.
        Blade::directive('themeCss', fn (): string => "<?php \$__p = app('theme')->getThemeCss(); if (\$__p && app('theme')->viteHasAsset(\$__p)) { echo app(\Illuminate\Foundation\Vite::class)(\$__p); } ?>");

        Blade::directive('themeJs', fn (): string => "<?php \$__p = app('theme')->getThemeJs(); if (\$__p && app('theme')->viteHasAsset(\$__p)) { echo app(\Illuminate\Foundation\Vite::class)(\$__p); } ?>");

        Blade::directive('themeLayout', fn (string $expression): string => "<?php echo app('theme')->getLayout({$expression}); ?>");

        // Load the active theme's built CSS bundle (or app.css fallback) + main JS.
        Blade::directive('themeVite', fn (): string => "<?php echo app(\Illuminate\Foundation\Vite::class)(app('theme')->activeEntries()); ?>");
    }
}
