<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Catalog\Actions\CreateProduct;
use Liberu\Billing\Catalog\Models\Product;
use Liberu\Billing\Catalog\Queries\ListProducts;

final class ProductController extends Controller
{
    public function index(Request $request, ListProducts $query): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $products = $query->execute($teamId !== null ? (int) $teamId : null, $request->integer('per_page', 25));

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product): array => $this->resource($product))->values(),
            'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage()],
        ]);
    }

    public function store(Request $request, CreateProduct $create): JsonResponse
    {
        Gate::authorize('create', Product::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'base_price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'metadata' => ['sometimes', 'array'],
        ]);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $data['team_id'] = $teamId !== null ? (int) $teamId : null;

        return response()->json(['data' => $this->resource($create->execute($data))], 201);
    }

    private function resource(Product $product): array
    {
        return ['id' => (string) $product->getKey(), 'type' => 'billing-catalog-product', 'attributes' => [
            'name' => $product->name, 'sku' => $product->sku, 'description' => $product->description,
            'base_price_minor' => $product->base_price_minor, 'currency' => $product->currency,
            'status' => $product->status->value, 'metadata' => $product->metadata ?? [],
        ]];
    }
}
