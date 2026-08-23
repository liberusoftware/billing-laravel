<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string|null $description
 * @property int|null $product_service_id
 * @property int $quantity
 * @property string $unit_price
 * @property string $total_price
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Currency|null $currency
 * @property-read Invoice|null $invoice
 * @property-read Products_Service|null $productService
 * @property-read Products_Service|null $product
 */
#[Fillable([
    'invoice_id',
    'product_service_id',
    'description',
    'quantity',
    'unit_price',
    'total_price',
    'currency',
])]
#[Table(name: 'invoice_items')]
class Invoice_Item extends Model
{
    use HasTeam;

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Currency::class,
            'currency',
            'code'
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(
            Products_Service::class,
            'product_service_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->productService();
    }
}
