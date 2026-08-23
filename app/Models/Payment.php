<?php

namespace App\Models;

use App\Traits\HasTeam;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $payment_gateway_id
 * @property int|null $customer_id
 * @property Carbon $payment_date
 * @property float $amount
 * @property string $currency
 * @property string $payment_method
 * @property string $transaction_id
 * @property string $refund_status
 * @property string|null $status
 * @property float|null $refunded_amount
 * @property string|null $refund_reason
 * @property string|null $reconciliation_status
 * @property string|null $reconciliation_notes
 * @property string|null $stripe_token
 * @property string|null $square_token
 * @property string|null $google_pay_token
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice|null $invoice
 * @property-read PaymentGateway|null $paymentGateway
 * @property-read Currency|null $currency
 * @property-read Affiliate|null $affiliate
 * @property-read string $reconciliation_status_badge
 * @property-read string $refund_status_badge
 */
#[Fillable([
    'invoice_id',
    'payment_gateway_id',
    'payment_date',
    'amount',
    'currency',
    'payment_method',
    'transaction_id',
    'refund_status',
    'refunded_amount',
    'affiliate_id',
    'affiliate_commission',
    'refund_reason',
    'reconciliation_status',
    'reconciliation_notes',
    'stripe_token',
    'square_token',
    'google_pay_token',
    'payment_method_details',
    'status',
])]
#[Guarded([
    'status',
    'refund_status',
    'refunded_amount',
    'refund_reason',
    'reconciliation_status',
    'reconciliation_notes',
    'stripe_token',
    'square_token',
    'google_pay_token',
])]
class Payment extends Model
{
    use HasFactory;
    use HasTeam;

    #[Override]
    protected $fillable = [
        'invoice_id',
        'payment_gateway_id',
        'payment_date',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'affiliate_id',
        'affiliate_commission',
        'payment_method_details',
    ];

    #[Override]
    protected function casts(): array
    {

        return [
            'amount' => 'float',
            'refunded_amount' => 'float',
            'payment_date' => 'datetime',
            'payment_method_details' => 'array',
            'status' => 'string',
        ];

    }

    protected function reconciliationStatusBadge(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->reconciliation_status) {
                'reconciled' => '<span class="badge badge-success">Reconciled</span>',
                'unmatched' => '<span class="badge badge-warning">Unmatched</span>',
                'discrepancy' => '<span class="badge badge-danger">Discrepancy</span>',
                'failed' => '<span class="badge badge-danger">Failed</span>',
                default => '<span class="badge badge-secondary">Pending</span>',
            }
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Currency::class,
            'currency',
            'code'
        );
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function isRefundable(): bool
    {
        return $this->refund_status === 'none' || $this->refund_status === 'partial';
    }

    public function getRemainingRefundableAmount(): int|float
    {
        return $this->amount - ($this->refunded_amount ?? 0);
    }

    public function processRefund(float $amount, ?string $reason = null): bool
    {
        if (! $this->isRefundable()) {
            throw new Exception('This payment is not eligible for refund');
        }

        if ($amount > $this->getRemainingRefundableAmount()) {
            throw new Exception('Refund amount exceeds remaining refundable amount');
        }

        $this->refunded_amount = ($this->refunded_amount ?? 0) + $amount;
        $this->refund_status = $this->refunded_amount >= $this->amount ? 'full' : 'partial';
        $this->refund_reason = $reason;
        $this->save();

        return true;
    }

    public function getFormattedAmount(): string
    {
        return number_format(
            $this->amount,
            2
        ).' '.$this->currency;
    }

    public function getFormattedRefundedAmount(): string
    {
        return number_format(
            $this->refunded_amount ?? 0,
            2
        ).' '.$this->currency;
    }

    protected function refundStatusBadge(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->refund_status) {
                'none' => '<span class="badge badge-danger">No Refund</span>',
                'partial' => '<span class="badge badge-warning">Partial Refund</span>',
                'full' => '<span class="badge badge-success">Full Refund</span>',
                default => '<span class="badge badge-secondary">Unknown</span>',
            }
        );
    }
}
