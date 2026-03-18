<?php

namespace App\Utils;

use App\Currency;

class NumberFormatUtil
{
    public function getCurrencyPrecision(?object $business = null): int
    {
        $sessionPrecision = session('business.currency_precision');
        if (is_numeric($sessionPrecision)) {
            return max(0, (int) $sessionPrecision);
        }

        $businessPrecision = data_get($business, 'currency_precision');
        if (is_numeric($businessPrecision)) {
            return max(0, (int) $businessPrecision);
        }

        return 2;
    }

    public function getQuantityPrecision(?object $business = null): int
    {
        $sessionPrecision = session('business.quantity_precision');
        if (is_numeric($sessionPrecision)) {
            return max(0, (int) $sessionPrecision);
        }

        $businessPrecision = data_get($business, 'quantity_precision');
        if (is_numeric($businessPrecision)) {
            return max(0, (int) $businessPrecision);
        }

        return 2;
    }

    public function stepFromPrecision(int $precision): string
    {
        $precision = max(0, $precision);

        if ($precision === 0) {
            return '1';
        }

        return '0.' . str_repeat('0', $precision - 1) . '1';
    }

    public function formatInput($value, int $precision): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, max(0, $precision), '.', '');
    }

    /**
     * Format quantity for quote/public display: thousand grouping + quantity precision.
     * Does not strip significant trailing zeros in the integer part (fixes 13500 → 135 when precision is 0).
     */
    public function formatQuantityDisplay(float $value, ?object $business = null): string
    {
        $precision = $this->getQuantityPrecision($business);
        [$decimalSep, $thousandSep] = $this->resolveQuantityDisplaySeparators($business);

        return $this->formatQuantityGrouped($value, $precision, $decimalSep, $thousandSep);
    }

    /**
     * @return array{0: string, 1: string} decimal separator, thousand separator
     */
    protected function resolveQuantityDisplaySeparators(?object $business): array
    {
        $currencyId = data_get($business, 'currency_id');
        if ($currencyId) {
            $cur = Currency::query()->whereKey($currencyId)->first(['decimal_separator', 'thousand_separator']);
            if ($cur !== null) {
                return [
                    (string) ($cur->decimal_separator ?: '.'),
                    (string) ($cur->thousand_separator ?? ','),
                ];
            }
        }

        if (session()->has('currency')) {
            $c = session('currency', []);

            return [
                (string) ($c['decimal_separator'] ?? '.'),
                (string) ($c['thousand_separator'] ?? ','),
            ];
        }

        return ['.', ','];
    }

    /**
     * Grouped quantity with optional trim of insignificant fractional zeros only.
     */
    public function formatQuantityGrouped(float $value, int $precision, string $decimalSep, string $thousandSep): string
    {
        $precision = max(0, $precision);
        $formatted = number_format($value, $precision, $decimalSep, $thousandSep);
        if ($precision === 0) {
            return $formatted;
        }

        $lastDec = strrpos($formatted, $decimalSep);
        if ($lastDec === false) {
            return $formatted;
        }

        $intPart = substr($formatted, 0, $lastDec);
        $fracPart = substr($formatted, $lastDec + strlen($decimalSep));
        $fracPart = rtrim($fracPart, '0');

        return $fracPart === '' ? $intPart : $intPart.$decimalSep.$fracPart;
    }

    /**
     * Currency amount for quote/public display: thousand grouping + business currency precision.
     */
    public function formatCurrencyAmountDisplay(float $value, ?object $business = null): string
    {
        $precision = $this->getCurrencyPrecision($business);
        [$decimalSep, $thousandSep] = $this->resolveQuantityDisplaySeparators($business);

        return number_format($value, max(0, $precision), $decimalSep, $thousandSep);
    }

    /**
     * Same as formatCurrencyAmountDisplay with optional ISO/code suffix (e.g. "1,234.56 USD").
     */
    public function formatCurrencyCodeDisplay(float $value, ?object $business, string $currencyCode): string
    {
        $formatted = $this->formatCurrencyAmountDisplay($value, $business);
        $code = trim($currencyCode);

        return $code !== '' ? $formatted.' '.$code : $formatted;
    }

    public function buildViewPayload(?object $business = null): array
    {
        $currencyPrecision = $this->getCurrencyPrecision($business);
        $quantityPrecision = $this->getQuantityPrecision($business);

        $currencyStep = $this->stepFromPrecision($currencyPrecision);
        $quantityStep = $this->stepFromPrecision($quantityPrecision);
        $rateStep = $this->stepFromPrecision(4);

        $currencySymbol = session('currency.symbol');
        if (! is_string($currencySymbol) || $currencySymbol === '') {
            $currencySymbol = (string) (data_get($business, 'currency.symbol')
                ?: data_get($business, 'currency_symbol')
                ?: '$');
        }

        return [
            'projectxCurrencyPrecision' => $currencyPrecision,
            'projectxQuantityPrecision' => $quantityPrecision,
            'projectxCurrencyStep' => $currencyStep,
            'projectxQuantityStep' => $quantityStep,
            'projectxRatePrecision' => 4,
            'projectxRateStep' => $rateStep,
            'projectxZeroMin' => '0',
            'projectxPositiveQuantityMin' => $quantityPrecision > 0 ? $quantityStep : '1',
            'projectxCurrencySymbol' => $currencySymbol,
        ];
    }
}

