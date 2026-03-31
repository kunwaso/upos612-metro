<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Services\ExchangeRateService;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    public function test_normalize_currency_code_uppercases_values(): void
    {
        $service = new ExchangeRateService();

        $this->assertSame('USD', $service->normalizeCurrencyCode('usd'));
    }

    public function test_resolve_rate_defaults_to_one_for_same_currency(): void
    {
        $service = new ExchangeRateService();

        $this->assertSame(1.0, $service->resolveRate(7, 'VND', 'VND', '2026-03-30'));
    }

    public function test_resolve_rate_uses_direct_lookup_when_available(): void
    {
        $service = new class extends ExchangeRateService {
            protected function findRate(int $businessId, string $fromCurrency, string $toCurrency, ?string $asOfDate, bool $reverse = false): ?float
            {
                if ($fromCurrency === 'USD' && $toCurrency === 'VND' && $reverse === false) {
                    return 25340.125;
                }

                return null;
            }
        };

        $this->assertSame(25340.125, $service->resolveRate(7, 'USD', 'VND', '2026-03-30'));
    }
}
