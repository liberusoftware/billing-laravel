<?php

namespace App\Services;

use App\Models\SiteSettings;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    public function get($key = null)
    {
        $settings = Cache::remember(
            config('site-settings.cache_key', 'site_settings'),
            config('site-settings.cache_duration', 3600),
            fn () => SiteSettings::first() ?? new SiteSettings
        );

        return $key ? $settings->$key : $settings;
    }

    public function clear(): void
    {
        Cache::forget(config('site-settings.cache_key', 'site_settings'));
    }
}
