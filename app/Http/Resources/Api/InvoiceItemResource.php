<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\Invoice_Item;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Invoice_Item */
class InvoiceItemResource extends JsonResource
{
    #[Override]
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->unit_price,
            'total' => $this->total_price,
        ];
    }
}
