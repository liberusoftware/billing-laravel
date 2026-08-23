<?php

namespace App\Models;

use App\Events\InvoiceStatusChanged;
use App\Mail\InvoiceGenerated;
use App\Services\AuditLogService;
use App\Services\CurrencyService;
use App\Services\PaymentGatewayService;
use App\Services\TaxService;
use App\Traits\HasTeam;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Override;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $subscription_id
 * @property string $invoice_number
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property numeric-string $total_amount
 * @property string $currency
 * @property string $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $viewed_at
 * @property Carbon|null $paid_at
 * @property array|null $status_history
 * @property numeric-string $late_fee_amount
 * @property Carbon|null $last_late_fee_date
 * @property bool|null $upcoming_reminder_sent
 * @property int|null $reminder_count
 * @property Carbon|null $last_reminder_date
 * @property int|null $discount_id
 * @property numeric-string|null $discount_amount
 * @property int|null $parent_invoice_id
 * @property bool $is_installment
 * @property numeric-string|null $tax_amount
 * @property bool|null $is_recurring
 * @property int|null $invoice_template_id
 * @property string|null $notes
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|float $remaining_amount
 * @property-read int|float $subtotal
 * @property-read int|float $final_total
 * @property-read float|int|array $total_with_late_fee
 * @property-read string $formatted_total_with_late_fee
 * @property-read float|int|null $remaining_late_fee
 * @property-read Customer|null $customer
 * @property-read Subscription|null $subscription
 * @property-read Currency|null $currency
 * @property-read Discount|null $discount
 * @property-read Invoice|null $parentInvoice
 * @property-read InvoiceTemplate|null $template
 * @property-read Collection<int, InvoiceDispute> $disputes
 * @property-read Collection<int, Invoice> $installments
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Invoice_Item> $items
 * @property-read PaymentPlan|null $paymentPlan
 * @property-read RecurringBillingConfiguration|null $recurringConfiguration
 */
#[Fillable([
    'customer_id',
    'project_id',
    'subscription_id',
    'invoice_number',
    'issue_date',
    'due_date',
    'total_amount',
    'currency',
    'status',
    'parent_invoice_id',
    'is_installment',
    'discount_id',
    'discount_amount',
    'invoice_template_id',
    'late_fee_amount',
    'last_late_fee_date',
    'is_recurring',
    'tax_amount',
    'viewed_at',
    'sent_at',
    'paid_at',
    'status_history',
    'reminder_count',
    'last_reminder_date',
    'upcoming_reminder_sent',
])]
class Invoice extends Model
{
    use HasFactory;
    use HasTeam;

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(
            static function (Invoice $invoice): void {
                $attrs = $invoice->getAttributes();

                if (empty($attrs['invoice_number'])) {
                    $invoice->invoice_number = 'INV-'.str_pad(
                        (string) (static::max('id') + 1),
                        6,
                        '0',
                        STR_PAD_LEFT
                    );
                }

                if (empty($attrs['currency'])) {
                    $invoice->currency = 'USD';
                }

                if (empty($attrs['status'])) {
                    $invoice->status = 'pending';
                }
            }
        );

        static::created(
            static function (?Model $invoice): void {
                app(AuditLogService::class)->log(
                    'invoice_created',
                    $invoice,
                    null,
                    $invoice->toArray()
                );
            }
        );

        static::updated(
            static function (?Model $invoice): void {
                app(AuditLogService::class)->log(
                    'invoice_updated',
                    $invoice,
                    $invoice->getOriginal(),
                    $invoice->getChanges()
                );
            }
        );

        static::deleted(
            static function (?Model $invoice): void {
                app(AuditLogService::class)->log(
                    'invoice_deleted',
                    $invoice,
                    $invoice->toArray()
                );
            }
        );
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(InvoiceDispute::class);
    }

    public function activeDispute(): ?InvoiceDispute
    {
        /** @var InvoiceDispute|null */
        return $this->disputes()->whereIn(
            'status',
            [
                'open',
                'under_review',
            ]
        )->latest()->first();
    }

    public function isDisputed(): bool
    {
        return $this->activeDispute() !== null;
    }

    #[Override]
    protected function casts(): array
    {

        return [
            'issue_date' => 'datetime',
            'due_date' => 'datetime',
            'last_late_fee_date' => 'datetime',
            'late_fee_amount' => 'decimal:2',
            'viewed_at' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'status_history' => 'array',
        ];

    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Currency::class,
            'currency',
            'code'
        );
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function paymentPlan(): HasOne
    {
        return $this->hasOne(PaymentPlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Invoice_Item::class);
    }

    /**
     * @throws Exception
     */
    public function processPayment(string $paymentMethod, float $amount): bool
    {
        if ($amount <= 0 || $amount > $this->remaining_amount) {
            throw new Exception('Invalid payment amount');
        }

        $payment = new Payment(
            [
                'invoice_id' => $this->id,
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'currency' => $this->currency,
                'payment_date' => now(),
            ]
        );

        $paymentGatewayService = app(PaymentGatewayService::class);
        $result = $paymentGatewayService->processPayment($payment);

        if ($result['success']) {
            $payment->transaction_id = $result['transaction_id'];
            $payment->save();

            $this->updateStatus();

            return true;
        }

        throw new Exception($result['message']);
    }

    public function updateStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');

        if ($totalPaid >= $this->total_amount) {
            $this->status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'partially_paid';
        }

        $this->save();
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::make(get: fn (): int|float => $this->total_amount - $this->payments()->sum('amount'));
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(
            __CLASS__,
            'parent_invoice_id'
        );
    }

    public function installments(): HasMany
    {
        return $this->hasMany(
            __CLASS__,
            'parent_invoice_id'
        );
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(get: fn () => $this->items->sum('total_price'));
    }

    protected function finalTotal(): Attribute
    {
        return Attribute::make(get: fn (): int|float => $this->subtotal + ($this->tax_amount ?? 0) - ($this->discount_amount ?? 0));
    }

    public function calculateTax()
    {
        return app(TaxService::class)->calculateTax($this);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            InvoiceTemplate::class,
            'invoice_template_id'
        );
    }

    public function sendInvoiceEmail(): void
    {
        Mail::to($this->customer->email)->send(new InvoiceGenerated($this));
    }

    public function createPaymentPlan($totalInstallments, $frequency = 'monthly')
    {
        if ($this->is_installment) {
            throw new Exception('Cannot create payment plan for an installment invoice');
        }

        $installmentAmount = round(
            $this->total_amount / $totalInstallments,
            2
        );
        $startDate = now();

        return PaymentPlan::create(
            [
                'invoice_id' => $this->id,
                'total_installments' => $totalInstallments,
                'installment_amount' => $installmentAmount,
                'frequency' => $frequency,
                'start_date' => $startDate,
                'next_due_date' => $this->calculateNextDueDate(
                    $startDate,
                    $frequency
                ),
                'status' => 'active',
            ]
        );
    }

    private function calculateNextDueDate(CarbonInterface $date, $frequency): CarbonInterface
    {
        return match ($frequency) {
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            default => $date->addMonth(),
        };
    }

    public function convertAmountTo(string $targetCurrency): float
    {
        return app(CurrencyService::class)->convert(
            (float) $this->total_amount,
            $this->currency,
            $targetCurrency
        );
    }

    public function getFormattedAmount(): string
    {
        return number_format(
            (float) $this->total_amount,
            2
        ).' '.$this->currency;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    public function calculateLateFee(): int|float
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        $config = LateFeeConfiguration::where(
            'team_id',
            $this->team_id
        )->first();
        if (! $config) {
            return 0;
        }

        // Check grace period
        $daysOverdue = $this->due_date->diffInDays(now());
        if ($daysOverdue <= $config->grace_period_days) {
            return 0;
        }

        $baseAmount = $config->is_compound ?
            ($this->total_amount + $this->late_fee_amount) :
            $this->total_amount;

        $fee = $config->fee_type === 'percentage' ?
            ($baseAmount * ($config->fee_amount / 100)) :
            $config->fee_amount;

        // Apply frequency rules
        if ($this->last_late_fee_date) {
            $daysSinceLastFee = $this->last_late_fee_date->diffInDays(now());
            $fee = match ($config->frequency) {
                'one-time' => 0,
                'daily' => $daysSinceLastFee >= 1 ? $fee : 0,
                'weekly' => $daysSinceLastFee >= 7 ? $fee : 0,
                'monthly' => $daysSinceLastFee >= 30 ? $fee : 0,
                default => 0,
            };
        }

        // Check max fee amount
        if ($config->max_fee_amount) {
            $totalFees = $this->late_fee_amount + $fee;
            if ($totalFees > $config->max_fee_amount) {
                $fee = max(
                    0,
                    $config->max_fee_amount - $this->late_fee_amount
                );
            }
        }

        return round(
            $fee,
            2
        );
    }

    public function applyLateFee(): float|int
    {
        $fee = $this->calculateLateFee();
        if ($fee > 0) {
            $this->late_fee_amount = (string) round((float) $this->late_fee_amount + $fee, 2);
            $this->last_late_fee_date = now();
            $this->save();

            // Log the late fee application
            app(AuditLogService::class)->log(
                'late_fee_applied',
                $this,
                ['previous_late_fee' => $this->late_fee_amount - $fee],
                ['new_late_fee' => $this->late_fee_amount]
            );
        }

        return $fee;
    }

    protected function totalWithLateFee(): Attribute
    {
        return Attribute::make(get: fn (): float|int => $this->final_total + $this->late_fee_amount);
    }

    protected function formattedTotalWithLateFee(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format(
                $this->total_with_late_fee,
                2
            ).' '.$this->currency
        );
    }

    protected function remainingLateFee(): Attribute
    {
        return Attribute::make(
            get: function (): null|float|int {
                $config = LateFeeConfiguration::where(
                    'team_id',
                    $this->team_id
                )->first();
                if (! $config || ! $config->max_fee_amount) {
                    return null;
                }

                return max(
                    0,
                    $config->max_fee_amount - $this->late_fee_amount
                );
            }
        );
    }

    public function recurringConfiguration(): HasOne
    {
        return $this->hasOne(RecurringBillingConfiguration::class);
    }

    public function markAsSent(): void
    {
        $this->sent_at = now();
        $this->addToStatusHistory('sent');
        $this->save();

        event(
            new InvoiceStatusChanged(
                $this,
                'sent'
            )
        );
    }

    public function markAsViewed(): void
    {
        $this->viewed_at = now();
        $this->addToStatusHistory('viewed');
        $this->save();

        event(
            new InvoiceStatusChanged(
                $this,
                'viewed'
            )
        );
    }

    public function markAsPaid(): void
    {
        $this->paid_at = now();
        $this->status = 'paid';
        $this->addToStatusHistory('paid');
        $this->save();

        event(
            new InvoiceStatusChanged(
                $this,
                'paid'
            )
        );
    }

    protected function addToStatusHistory($status): void
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'user_id' => auth()->id(),
        ];
        $this->status_history = $history;
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->paid_at) {
                    return 'paid';
                }
                if ($this->viewed_at) {
                    return 'viewed';
                }
                if ($this->sent_at) {
                    return 'sent';
                }

                return $value;
            }
        );
    }

    public function setupRecurringBilling($frequency, $billingDay = null)
    {
        if ($this->is_installment) {
            throw new Exception('Cannot set up recurring billing for an installment invoice');
        }

        $this->update(['is_recurring' => true]);

        return $this->recurringConfiguration()->create(
            [
                'frequency' => $frequency,
                'billing_day' => $billingDay,
                'next_billing_date' => $this->calculateNextBillingDate(
                    $frequency,
                    $billingDay
                ),
                'is_active' => true,
            ]
        );
    }

    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('invoices.pdf', ['invoice' => $this]);
    }

    private function calculateNextBillingDate($frequency, $billingDay = null): CarbonInterface
    {
        $date = now();

        if ($billingDay && $billingDay > $date->day) {
            $date->setDay($billingDay);
        } else {
            $date = match ($frequency) {
                'monthly' => $date->addMonth(),
                'quarterly' => $date->addMonths(3),
                'yearly' => $date->addYear(),
                default => $date->addMonth()
            };

            if ($billingDay) {
                $date->setDay($billingDay);
            }
        }

        return $date;
    }
}
