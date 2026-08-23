<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Catalog\Actions\CreateProduct;
use Liberu\Billing\Catalog\Queries\ListProducts;
use Livewire\Component;

final class ProductCatalog extends Component
{
    public string $name = '';

    public string $sku = '';

    public string $currency = 'USD';

    public int $basePriceMinor = 0;

    public bool $showCreate = false;

    public function save(CreateProduct $create): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'], 'sku' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3', 'alpha'], 'basePriceMinor' => ['required', 'integer', 'min:0'],
        ]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['name' => $this->name, 'sku' => $this->sku, 'currency' => $this->currency, 'base_price_minor' => $this->basePriceMinor, 'team_id' => $teamId]);
        $this->reset(['name', 'sku', 'basePriceMinor']);
        $this->currency = 'USD';
        $this->showCreate = false;
        session()->flash('billing-catalog-message', __('Product created.'));
    }

    public function render(ListProducts $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('billing-catalog-livewire::product-catalog', ['products' => $query->execute($teamId !== null ? (int) $teamId : null)]);
    }
}
