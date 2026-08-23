<?php

namespace Tests\Unit\Services;

use App\Models\Currency;
use App\Services\BillingService;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class CurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function seedCurrencies(): void
    {
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'exchange_rate' => '1', 'is_base' => true]);
        Currency::create(['code' => 'EUR', 'name' => 'Euro', 'exchange_rate' => '0.5']);
    }

    public function test_same_currency_returns_amount_unchanged(): void
    {
        $this->seedCurrencies();

        $this->assertSame(10.0, app(CurrencyService::class)->convert(10.0, 'USD', 'USD'));
    }

    public function test_converts_via_stored_rates(): void
    {
        $this->seedCurrencies();

        // amount * (rateTo / rateFrom) = 10 * (0.5 / 1) = 5.00, rounded to EUR precision (2)
        $this->assertSame(5.0, app(CurrencyService::class)->convert(10.0, 'USD', 'EUR'));
    }

    public function test_marking_a_second_currency_base_clears_the_first(): void
    {
        $this->seedCurrencies();

        $eur = Currency::where('code', 'EUR')->first();
        $eur->update(['is_base' => true]);

        $this->assertFalse(Currency::where('code', 'USD')->first()->is_base);
        $this->assertTrue(Currency::where('code', 'EUR')->first()->is_base);
    }

    public function test_billing_service_convert_currency_returns_real_value(): void
    {
        $this->seedCurrencies();

        $this->assertSame(5.0, app(BillingService::class)->convertCurrency(10.0, 'USD', 'EUR'));
    }

    public function test_missing_currency_throws(): void
    {
        $this->seedCurrencies();

        $this->expectException(RuntimeException::class);
        app(CurrencyService::class)->convert(10.0, 'USD', 'GBP');
    }

    public function test_disabled_currency_throws(): void
    {
        $this->seedCurrencies();
        Currency::where('code', 'EUR')->update(['is_enabled' => false]);

        $this->expectException(RuntimeException::class);
        app(CurrencyService::class)->convert(10.0, 'USD', 'EUR');
    }
}
