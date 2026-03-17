<?php

namespace Modules\ProjectX\Utils;

class ProjectXNumberFormatUtil
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

