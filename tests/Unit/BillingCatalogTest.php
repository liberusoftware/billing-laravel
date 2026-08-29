<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Actions\CreateProduct;
use Liberu\Billing\Catalog\Actions\TransitionProductLifecycle;
use Liberu\Billing\Catalog\Enums\ProductStatus;
use Liberu\Billing\Catalog\Models\Plan;
use Liberu\Billing\Catalog\Models\Product;
use Liberu\Billing\Catalog\Policies\CatalogRecordPolicy;
use Liberu\Billing\Catalog\Policies\ProductPolicy;

uses(RefreshDatabase::class);

it('requires catalog write access for mutations and enforces team ownership', function () {
    $readUser = new class()
    {
        public int $current_team_id = 10;

        public function tokenCan(string $ability): bool
        {
            return $ability === 'billing.catalog.read';
        }
    };
    $writeUser = new class()
    {
        public int $current_team_id = 10;

        public function tokenCan(string $ability): bool
        {
            return $ability === 'billing.catalog.write';
        }
    };
    $product = app(CreateProduct::class)->execute([
        'team_id' => 10, 'name' => 'Hosting', 'sku' => 'HOST', 'base_price_minor' => 1000, 'currency' => 'USD',
    ]);
    $plan = app(CreateCatalogRecord::class)->execute(Plan::class, ['team_id' => 10, 'name' => 'Starter', 'code' => 'STARTER']);
    $otherTeamPlan = app(CreateCatalogRecord::class)->execute(Plan::class, ['team_id' => 20, 'name' => 'Other', 'code' => 'OTHER']);

    expect(app(ProductPolicy::class)->create($readUser))->toBeFalse()
        ->and(app(ProductPolicy::class)->update($readUser, $product))->toBeFalse()
        ->and(app(ProductPolicy::class)->update($writeUser, $product))->toBeTrue()
        ->and(app(CatalogRecordPolicy::class)->create($readUser))->toBeFalse()
        ->and(app(CatalogRecordPolicy::class)->update($writeUser, $plan))->toBeTrue()
        ->and(app(CatalogRecordPolicy::class)->update($writeUser, $otherTeamPlan))->toBeFalse();
});

it('does not reopen a product after its persisted state becomes archived', function (): void {
    $product = app(CreateProduct::class)->execute([
        'team_id' => 10, 'name' => 'Archived hosting', 'sku' => 'ARCHIVED-HOST', 'base_price_minor' => 1000, 'currency' => 'USD',
    ]);
    $product->refresh();
    Product::query()->whereKey($product->getKey())->update(['status' => 'archived']);

    expect(fn () => app(TransitionProductLifecycle::class)->execute($product, ProductStatus::Active))
        ->toThrow(InvalidArgumentException::class, 'An archived product cannot be reopened.');
});
