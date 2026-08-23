<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\TaxExemption;
use App\Models\TaxRate;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxService
{
    protected $taxApiConfig;

    public function __construct()
    {
        $this->taxApiConfig = config('services.tax_api');
    }

    public function calculateTax(Invoice $invoice): int|float
    {
        $customer = $invoice->customer;

        // Check for tax exemption
        if ($this->isExempt($customer)) {
            return 0;
        }

        // Try to get tax rates from cache
        $cacheKey = "tax_rates_{$invoice->team_id}_{$customer->country}_{$customer->state}";
        $taxRates = Cache::remember(
            $cacheKey,
            3600,
            fn () => $this->getTaxRates(
                $invoice,
                $customer
            )
        );

        $totalTax = 0;
        foreach ($invoice->items as $item) {
            $applicableRate = $this->getApplicableRate(
                $taxRates,
                $item
            );
            if ($applicableRate) {
                $itemTax = $this->calculateItemTax(
                    $item,
                    $applicableRate
                );
                $totalTax += $itemTax;
            }
        }

        return round(
            $totalTax,
            2
        );
    }

    protected function getTaxRates($invoice, $customer)
    {
        // First try to get rates from external API if configured
        if ($this->taxApiConfig['enabled']) {
            try {
                return $this->getTaxRatesFromApi($customer);
            } catch (Exception $e) {
                Log::error('Tax API error: '.$e->getMessage());
                // Fallback to database rates if API fails
            }
        }

        // Get rates from database
        return TaxRate::where(
            'team_id',
            $invoice->team_id
        )
            ->where(
                'is_active',
                true
            )
            ->where(
                'country',
                $customer->country
            )
            ->where(
                function ($query) use ($customer): void {
                    $query->whereNull('state')
                        ->orWhere(
                            'state',
                            $customer->state
                        );
                }
            )
            ->get();
    }

    protected function getTaxRatesFromApi($customer)
    {
        $response = Http::withHeaders(
            [
                'Authorization' => 'Bearer '.$this->taxApiConfig['api_key'],
            ]
        )->get(
            $this->taxApiConfig['url'],
            [
                'country' => $customer->country,
                'state' => $customer->state,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
            ]
        );

        if ($response->successful()) {
            return $this->formatApiResponse($response->json());
        }

        throw new Exception('Failed to fetch tax rates from API');
    }

    protected function isExempt($customer)
    {
        return TaxExemption::where(
            'customer_id',
            $customer->id
        )
            ->where(
                'is_active',
                true
            )
            ->where(
                function ($query): void {
                    $query->where(
                        'expiry_date',
                        '>',
                        now()
                    )
                        ->orWhereNull('expiry_date');
                }
            )
            ->exists();
    }

    protected function getApplicableRate($taxRates, $item)
    {
        return $taxRates->first(fn ($rate): bool => $rate->service_type === $item->productService->type);
    }

    protected function calculateItemTax($item, $taxRate): float|int
    {
        $taxableAmount = $item->total_price;

        // Tiered/threshold rates compute the full tax themselves; a flat rate
        // taxes the whole amount. Either way this returns the tax, not a base.
        if ($taxRate->threshold_amount && $taxableAmount > $taxRate->threshold_amount) {
            return $this->applyThresholdRules($taxableAmount, $taxRate);
        }

        return $taxableAmount * ($taxRate->rate / 100);
    }

    protected function applyThresholdRules($amount, $taxRate)
    {
        if ($taxRate->threshold_rate) {
            $excessAmount = $amount - $taxRate->threshold_amount;

            return ($taxRate->threshold_amount * ($taxRate->rate / 100)) +
                ($excessAmount * ($taxRate->threshold_rate / 100));
        }

        return $amount * ($taxRate->rate / 100);
    }

    protected function formatApiResponse($apiData)
    {
        return collect($apiData)->map(
            fn ($rate): TaxRate => new TaxRate(
                [
                    'rate' => $rate['rate'],
                    'service_type' => $rate['type'],
                    'country' => $rate['country'],
                    'state' => $rate['state'] ?? null,
                    'threshold_amount' => $rate['threshold_amount'] ?? null,
                    'threshold_rate' => $rate['threshold_rate'] ?? null,
                ]
            )
        );
    }
}
