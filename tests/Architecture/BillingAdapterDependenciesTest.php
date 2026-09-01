<?php

declare(strict_types=1);

it('declares framework dependencies directly in billing adapter packages', function (): void {
    foreach (billingAdapterPaths() as $modulePath) {
        $manifestPath = $modulePath.'/composer.json';
        if (! is_file($manifestPath)) {
            continue;
        }

        $package = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $name = basename($modulePath);
        $requires = $package['require'] ?? [];

        if (str_ends_with($name, '-api')) {
            expect($requires)->toHaveKeys(['illuminate/http', 'illuminate/routing', 'laravel/sanctum']);
        }

        if (str_ends_with($name, '-filament')) {
            expect($requires)->toHaveKey('filament/filament');
        }

        if (str_ends_with($name, '-livewire')) {
            expect($requires)->toHaveKey('livewire/livewire');
        }
    }
});

it('keeps every billing adapter attached to exactly one billing domain package', function (): void {
    $domains = [
        'billing-core' => 'liberusoftware/module-billing-billing-core',
        'billing-catalog' => 'liberusoftware/module-billing-catalog',
        'billing-orders' => 'liberusoftware/module-billing-orders',
        'billing-pricing' => 'liberusoftware/module-billing-pricing',
    ];

    foreach (billingAdapterPaths() as $modulePath) {
        $adapter = basename($modulePath);
        $surface = preg_replace('/-(api|filament|livewire)$/', '', $adapter);
        $surface = preg_replace('/^module-/', '', $surface);
        $domain = $domains[$surface] ?? 'liberusoftware/module-'.$surface;
        $package = json_decode((string) file_get_contents($modulePath.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        expect($package['require'] ?? [])->toHaveKey($domain);
    }
});

it('keeps canonical billing package identities aligned with their paths', function (): void {
    $paths = array_merge(
        [base_path('modules/module-billing-billing-core')],
        glob(base_path('modules/module-billing-*-api')) ?: [],
        glob(base_path('modules/module-billing-*-filament')) ?: [],
        glob(base_path('modules/module-billing-*-livewire')) ?: [],
    );

    foreach ($paths as $modulePath) {
        $directory = basename($modulePath);
        $composer = json_decode((string) file_get_contents($modulePath.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest = json_decode((string) file_get_contents($modulePath.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        $expectedComposerName = 'liberusoftware/'.($directory === 'billing-core' ? 'module-billing-billing-core' : $directory);

        expect($composer['name'])->toBe($expectedComposerName)
            ->and($composer['extra']['liberu']['name'] ?? null)->toBe($directory)
            ->and($manifest['name'])->toBe($directory);
    }
});

/** @return list<string> */
function billingAdapterPaths(): array
{
    return array_values(array_filter(array_merge(
        glob(base_path('modules/billing-*')) ?: [],
        glob(base_path('modules/module-billing-*')) ?: [],
    ), static fn (string $path): bool => is_dir($path)
        && is_file($path.'/composer.json')
        && preg_match('/-(api|filament|livewire)$/', basename($path)) === 1));
}
