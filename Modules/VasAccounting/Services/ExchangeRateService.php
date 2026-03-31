<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Modules\VasAccounting\Entities\VasExchangeRate;
use RuntimeException;

class ExchangeRateService
{
    public function resolveRate(
        int $businessId,
        string $fromCurrency,
        string $toCurrency = 'VND',
        string|\DateTimeInterface|null $rateDate = null,
        ?float $fallbackRate = null
    ): float {
        $fromCurrency = $this->normalizeCurrencyCode($fromCurrency);
        $toCurrency = $this->normalizeCurrencyCode($toCurrency);

        if ($fromCurrency === '' || $toCurrency === '' || $fromCurrency === $toCurrency) {
            return 1.0;
        }

        $asOfDate = $rateDate ? Carbon::parse($rateDate)->toDateString() : null;

        $directRate = $this->findRate($businessId, $fromCurrency, $toCurrency, $asOfDate);
        if ($directRate) {
            return $directRate;
        }

        $reverseRate = $this->findRate($businessId, $toCurrency, $fromCurrency, $asOfDate, true);
        if ($reverseRate) {
            return $reverseRate;
        }

        if ($fallbackRate !== null && $fallbackRate > 0) {
            return round($fallbackRate, 8);
        }

        throw new RuntimeException("No VAS exchange rate found for [{$fromCurrency}/{$toCurrency}].");
    }

    public function convert(
        int $businessId,
        float $amount,
        string $fromCurrency,
        string $toCurrency = 'VND',
        string|\DateTimeInterface|null $rateDate = null,
        ?float $fallbackRate = null
    ): float {
        return round($amount * $this->resolveRate($businessId, $fromCurrency, $toCurrency, $rateDate, $fallbackRate), 4);
    }

    public function storeRate(array $attributes): VasExchangeRate
    {
        $rate = round((float) ($attributes['rate'] ?? 0), 8);
        if ($rate <= 0) {
            throw new RuntimeException('VAS exchange rate must be greater than zero.');
        }

        return VasExchangeRate::updateOrCreate(
            [
                'business_id' => (int) $attributes['business_id'],
                'rate_date' => Carbon::parse($attributes['rate_date'])->toDateString(),
                'from_currency' => $this->normalizeCurrencyCode((string) $attributes['from_currency']),
                'to_currency' => $this->normalizeCurrencyCode((string) ($attributes['to_currency'] ?? 'VND')),
            ],
            [
                'rate' => $rate,
                'inverse_rate' => round((float) ($attributes['inverse_rate'] ?? (1 / $rate)), 8),
                'source' => (string) ($attributes['source'] ?? 'manual'),
                'is_manual' => (bool) ($attributes['is_manual'] ?? true),
                'meta' => (array) ($attributes['meta'] ?? []),
            ]
        );
    }

    public function normalizeCurrencyCode(string $currencyCode): string
    {
        return strtoupper(trim($currencyCode));
    }

    protected function findRate(int $businessId, string $fromCurrency, string $toCurrency, ?string $asOfDate, bool $reverse = false): ?float
    {
        $query = VasExchangeRate::query()
            ->where('business_id', $businessId)
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency);

        if ($asOfDate) {
            $query->whereDate('rate_date', '<=', $asOfDate);
        }

        $row = $query
            ->orderByDesc('rate_date')
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return null;
        }

        $rate = round((float) ($reverse ? ($row->inverse_rate ?: (1 / max((float) $row->rate, 0.00000001))) : $row->rate), 8);

        return $rate > 0 ? $rate : null;
    }
}
