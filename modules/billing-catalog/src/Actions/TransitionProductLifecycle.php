<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Catalog\Enums\ProductStatus;
use Liberu\Billing\Catalog\Models\Product;

final class TransitionProductLifecycle
{
    public function execute(Product $product, ProductStatus $status): Product
    {
        if ($product->status === ProductStatus::Archived && $status !== ProductStatus::Archived) {
            throw new InvalidArgumentException('An archived product cannot be reopened.');
        }

        return DB::transaction(function () use ($product, $status): Product {
            $product->update(['status' => $status]);

            return $product->refresh();
        });
    }
}
