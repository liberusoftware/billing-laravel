<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Catalog\Enums\ProductStatus;
use Liberu\Billing\Catalog\Events\ProductStatusChanged;
use Liberu\Billing\Catalog\Models\Product;

final class TransitionProductLifecycle
{
    public function execute(Product $product, ProductStatus $status): Product
    {
        if ($product->status === ProductStatus::Archived && $status !== ProductStatus::Archived) {
            throw new InvalidArgumentException('An archived product cannot be reopened.');
        }

        return DB::transaction(function () use ($product, $status): Product {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            if ($locked->status === ProductStatus::Archived && $status !== ProductStatus::Archived) {
                throw new InvalidArgumentException('An archived product cannot be reopened.');
            }

            $from = $locked->status;
            $locked->update(['status' => $status]);
            ProductStatusChanged::dispatch($locked, $from->value, $status->value);

            return $locked->refresh();
        });
    }
}
