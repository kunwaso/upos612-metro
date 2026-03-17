<?php

namespace App\Utils;

use App\Business;
use App\Currency;
use App\Product;

class ProductCostingUtil
{
    public const COSTING_QUOTE_FIELD_KEYS = ['currency', 'incoterm', 'purchase_uom'];

    /**
     * Build dropdown options for quote costing form fields.
     */
    public function getDropdownOptions(?int $business_id = null): array
    {
        return [
            'currency' => $this->resolveCurrencyOptions($business_id),
            'incoterm' => $this->resolveStringOptions(config('product.quote_costing_options.incoterm', ['FOB', 'CIF'])),
            'purchase_uom' => $this->resolveStringOptions(config('product.quote_costing_options.purchase_uom', ['pcs', 'yds'])),
        ];
    }

    public function getDefaultCurrencyCode(int $business_id): string
    {
        $business = Business::find($business_id, ['id', 'currency_id']);
        $businessCurrencyId = (int) ($business->currency_id ?? 0);
        if ($businessCurrencyId > 0) {
            $currency = Currency::find($businessCurrencyId, ['id', 'code']);
            $code = trim((string) ($currency->code ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

        $options = $this->getDropdownOptions($business_id)['currency'] ?? [];
        if (! empty($options)) {
            return (string) array_key_first($options);
        }

        return 'USD';
    }

    public function buildLinePayload(Product $product, array $input): array
    {
        $fallbackBasePrice = $this->resolveFallbackPrice($product);
        $normalized = $this->normalizeInput((int) $product->business_id, $input, $fallbackBasePrice, $product);
        $breakdown = $this->calculateBreakdown($normalized);

        return [
            'product_snapshot' => $this->buildProductSnapshot($product),
            'costing_input' => $normalized,
            'costing_breakdown' => $breakdown,
            'line_type' => 'product',
        ];
    }

    public function normalizeInput(int $business_id, array $input, float $fallbackBasePrice = 0.0, ?Product $product = null): array
    {
        $options = $this->getDropdownOptions($business_id);

        $qty = (float) ($input['qty'] ?? $input['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new \InvalidArgumentException(__('product.quote_qty_invalid'));
        }

        $purchaseUom = trim((string) optional(optional($product)->unit)->short_name);
        $currency = trim((string) ($input['currency'] ?? ''));
        $incoterm = trim((string) ($input['incoterm'] ?? ''));

        $this->assertValueInConfiguredOptions('currency', $currency, (array) ($options['currency'] ?? []));
        if ($incoterm !== '') {
            $this->assertValueInConfiguredOptions('incoterm', $incoterm, (array) ($options['incoterm'] ?? []));
        }

        $baseMillPrice = $input['base_mill_price'] ?? $input['base_price'] ?? $fallbackBasePrice;
        $baseMillPrice = (float) ($baseMillPrice ?? 0);
        if ($baseMillPrice < 0) {
            throw new \InvalidArgumentException(__('product.quote_base_mill_price_invalid'));
        }

        return [
            'qty' => round($qty, 4),
            'purchase_uom' => $purchaseUom,
            'base_mill_price' => round($baseMillPrice, 4),
            'test_cost' => 0.0,
            'surcharge' => 0.0,
            'finish_uplift_pct' => 0.0,
            'waste_pct' => 0.0,
            'currency' => $currency,
            'incoterm' => $incoterm,
        ];
    }

    public function calculateBreakdown(array $normalizedInput): array
    {
        $baseMillPrice = (float) ($normalizedInput['base_mill_price'] ?? 0);
        $qty = (float) ($normalizedInput['qty'] ?? 0);
        $testCost = 0.0;
        $surcharge = 0.0;
        $finishUpliftAmount = 0.0;
        $wasteAmount = 0.0;
        $unitCost = $baseMillPrice;
        $totalCost = $unitCost * $qty;

        return [
            'base_mill_price' => round($baseMillPrice, 4),
            'test_cost' => round($testCost, 4),
            'surcharge' => round($surcharge, 4),
            'finish_uplift_amount' => round($finishUpliftAmount, 4),
            'waste_amount' => round($wasteAmount, 4),
            'unit_cost' => round($unitCost, 4),
            'qty' => round($qty, 4),
            'total_cost' => round($totalCost, 4),
            'currency' => (string) ($normalizedInput['currency'] ?? ''),
            'incoterm' => (string) ($normalizedInput['incoterm'] ?? ''),
        ];
    }

    public function assertSharedCurrencyAndIncoterm(array $linePayloads): void
    {
        if (empty($linePayloads)) {
            return;
        }

        $firstCurrency = (string) ($linePayloads[0]['costing_input']['currency'] ?? '');
        $firstIncoterm = (string) ($linePayloads[0]['costing_input']['incoterm'] ?? '');

        foreach ($linePayloads as $linePayload) {
            $currency = (string) ($linePayload['costing_input']['currency'] ?? '');
            $incoterm = (string) ($linePayload['costing_input']['incoterm'] ?? '');

            if ($currency !== $firstCurrency || $incoterm !== $firstIncoterm) {
                throw new \InvalidArgumentException(__('product.quote_shared_currency_incoterm_required'));
            }
        }
    }

    protected function buildProductSnapshot(Product $product): array
    {
        return [
            'product_id' => (int) $product->id,
            'sku' => (string) ($product->sku ?? ''),
            'name' => (string) ($product->name ?? ''),
            'type' => (string) ($product->type ?? ''),
            'category_id' => (int) ($product->category_id ?? 0),
            'category' => (string) (optional($product->category)->name ?? ''),
            'unit_id' => (int) ($product->unit_id ?? 0),
            'unit' => (string) (optional($product->unit)->short_name ?? ''),
            'selling_price' => (float) ($product->selling_price ?? 0),
        ];
    }

    protected function assertPercentRange(string $field, float $value): void
    {
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(__('product.quote_percent_invalid', ['field' => $field]));
        }
    }

    protected function assertValueInConfiguredOptions(string $field, string $value, array $options): void
    {
        if ($value === '') {
            throw new \InvalidArgumentException(__('product.quote_dropdown_invalid', ['field' => $field]));
        }

        if (empty($options)) {
            return;
        }

        if (array_key_exists($value, $options) || in_array($value, $options, true)) {
            return;
        }

        throw new \InvalidArgumentException(__('product.quote_dropdown_invalid', ['field' => $field]));
    }

    protected function resolveCurrencyOptions(?int $business_id = null): array
    {
        $currencies = Currency::select('id', 'country', 'currency', 'code')
            ->orderBy('country')
            ->get();

        $options = [];
        foreach ($currencies as $currency) {
            $code = trim((string) ($currency->code ?? ''));
            if ($code === '') {
                continue;
            }

            $country = trim((string) ($currency->country ?? ''));
            $currencyName = trim((string) ($currency->currency ?? ''));
            $label = trim($country . ' - ' . $currencyName);
            $options[$code] = $label !== '' ? $label . ' (' . $code . ')' : $code;
        }

        if (! empty($options)) {
            return $options;
        }

        return ['USD' => 'USD'];
    }

    protected function resolveStringOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $option) {
            $value = trim((string) $option);
            if ($value === '') {
                continue;
            }
            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    protected function resolveFallbackPrice(Product $product): float
    {
        $variationPrice = (float) optional(optional($product->variations)->first())->default_sell_price;
        if ($variationPrice > 0) {
            return $variationPrice;
        }

        return (float) ($product->selling_price ?? 0);
    }
}
