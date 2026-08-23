<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuoteService
{
    /**
     * Create a new quote
     */
    public function createQuote(array $data): Quote
    {
        return DB::transaction(
            function () use ($data) {
                $items = $data['items'] ?? [];
                unset($data['items']);

                $quote = Quote::create($data);

                $this->syncItems(
                    $quote,
                    $items
                );
                $this->recalculateTotals($quote);

                return $quote->fresh(
                    [
                        'items',
                        'customer',
                    ]
                );
            }
        );
    }

    /**
     * Update an existing quote
     */
    public function updateQuote(Quote $quote, array $data): Quote
    {
        return DB::transaction(
            function () use ($quote, $data) {
                $items = $data['items'] ?? null;
                unset($data['items']);

                $quote->update($data);

                if ($items !== null) {
                    $this->syncItems(
                        $quote,
                        $items
                    );
                }

                $this->recalculateTotals($quote);

                return $quote->fresh(
                    [
                        'items',
                        'customer',
                    ]
                );
            }
        );
    }

    /**
     * Send a quote to the client
     */
    public function sendQuote(Quote $quote): Quote
    {
        $quote->update(
            [
                'status' => 'sent',
                'sent_at' => now(),
            ]
        );

        return $quote;
    }

    /**
     * Mark a quote as viewed
     */
    public function markViewed(Quote $quote): Quote
    {
        if ($quote->status === 'sent') {
            $quote->update(
                [
                    'status' => 'viewed',
                    'viewed_at' => now(),
                ]
            );
        }

        return $quote;
    }

    /**
     * Accept a quote
     */
    public function acceptQuote(Quote $quote): Quote
    {
        $quote->update(
            [
                'status' => 'accepted',
                'accepted_at' => now(),
            ]
        );

        return $quote;
    }

    /**
     * Decline a quote
     */
    public function declineQuote(Quote $quote): Quote
    {
        $quote->update(
            [
                'status' => 'declined',
                'declined_at' => now(),
            ]
        );

        return $quote;
    }

    /**
     * Convert an accepted quote to an invoice
     */
    public function convertToInvoice(Quote $quote): Invoice
    {
        if (! $quote->canBeConverted()) {
            throw new RuntimeException('Only accepted quotes can be converted to invoices.');
        }

        return DB::transaction(
            function () use ($quote) {
                $invoice = Invoice::create(
                    [
                        'customer_id' => $quote->customer_id,
                        'invoice_number' => 'INV-'.strtoupper(uniqid()),
                        'issue_date' => now()->toDateString(),
                        'due_date' => now()->addDays(30)->toDateString(),
                        'status' => 'pending',
                        'total_amount' => $quote->total,
                        'currency' => $quote->currency,
                    ]
                );

                return $invoice->fresh(['customer']);
            }
        );
    }

    /**
     * Get quote statistics
     */
    public function getStatistics(?int $teamId = null): array
    {
        $query = Quote::query();

        if ($teamId) {
            $query->where(
                'team_id',
                $teamId
            );
        }

        $counts = $query->selectRaw('status, count(*) as count, sum(total) as total_value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'draft' => [
                'count' => $counts->get('draft')->count ?? 0,
                'value' => $counts->get('draft')->total_value ?? 0,
            ],
            'sent' => [
                'count' => $counts->get('sent')->count ?? 0,
                'value' => $counts->get('sent')->total_value ?? 0,
            ],
            'accepted' => [
                'count' => $counts->get('accepted')->count ?? 0,
                'value' => $counts->get('accepted')->total_value ?? 0,
            ],
            'declined' => [
                'count' => $counts->get('declined')->count ?? 0,
                'value' => $counts->get('declined')->total_value ?? 0,
            ],
            'expired' => [
                'count' => $counts->get('expired')->count ?? 0,
                'value' => $counts->get('expired')->total_value ?? 0,
            ],
        ];
    }

    /**
     * Mark overdue quotes as expired
     */
    public function expireOverdueQuotes(): int
    {
        return Quote::query()
            ->whereIn(
                'status',
                [
                    'sent',
                    'viewed',
                ]
            )
            ->whereNotNull('valid_until')
            ->where(
                'valid_until',
                '<',
                now()->toDateString()
            )
            ->update(['status' => 'expired']);
    }

    /**
     * Sync quote items
     */
    protected function syncItems(Quote $quote, array $items): void
    {
        $quote->items()->delete();

        foreach ($items as $index => $itemData) {
            QuoteItem::create(
                [
                    'quote_id' => $quote->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemData['quantity'] * $itemData['unit_price'],
                    'sort_order' => $itemData['sort_order'] ?? $index,
                ]
            );
        }
    }

    /**
     * Recalculate quote totals from items
     */
    protected function recalculateTotals(Quote $quote): void
    {
        $quote->load('items');

        $subtotal = $quote->items->sum('total');
        $taxRate = 0; // Tax calculation can be extended later

        $quote->update(
            [
                'subtotal' => $subtotal,
                'tax_amount' => $subtotal * $taxRate,
                'total' => $subtotal + ($subtotal * $taxRate),
            ]
        );
    }
}
