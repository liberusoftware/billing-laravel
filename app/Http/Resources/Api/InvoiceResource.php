<?php

namespace App\Http\Resources\Api;

use App\Models\Invoice;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    #[Override]
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'issue_date' => $this->issue_date->toIso8601String(),
            'due_date' => $this->due_date->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'late_fee_amount' => $this->late_fee_amount,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'final_total' => $this->final_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'links' => [
                'self' => url("/api/invoices/{$this->id}"),
                'download' => url("/api/invoices/{$this->id}/download"),
            ],
        ];
    }
}
