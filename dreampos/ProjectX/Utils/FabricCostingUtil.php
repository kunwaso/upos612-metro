<?php

namespace Modules\ProjectX\Utils;

use App\Business;
use App\Currency;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\QuoteSetting;
use Modules\ProjectX\Entities\Trim;

class FabricCostingUtil
{
    public const COSTING_QUOTE_FIELD_KEYS = ['currency', 'incoterm', 'purchase_uom'];

    protected FabricManagerUtil $fabricManagerUtil;

    public function __construct(FabricManagerUtil $fabricManagerUtil)
    {
        $this->fabricManagerUtil = $fabricManagerUtil;
    }

    public function getDropdownOptions(?int $business_id = null): array
    {
        $business_id = ! empty($business_id) ? (int) $business_id : null;

        return [
            'currency' => $this->resolveCurrencyOptions($business_id),
            'incoterm' => $this->resolveBusinessStringOptions($business_id, 'incoterm_options', 'incoterm'),
            'purchase_uom' => $this->resolveBusinessStringOptions($business_id, 'purchase_uom_options', 'purchase_uom'),
        ];
    }

    public function getDefaultCurrencyCode(int $business_id): string
    {
        $business_id = (int) $business_id;
        $quoteSetting = QuoteSetting::where('business_id', $business_id)->first();
        $defaultCurrencyId = (int) ($quoteSetting->default_currency_id ?? 0);

        if ($defaultCurrencyId > 0) {
            $currency = Currency::find($defaultCurrencyId, ['id', 'code']);
            $code = trim((string) ($currency->code ?? ''));
            if ($code !== '') {
                return $code;
            }
        }

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

        $fallback = config('projectx.quote_costing_options.currency', []);

        return (string) ($fallback[0] ?? '');
    }

    public function buildLinePayload(Fabric $fabric, array $input): array
    {
        $normalized = $this->normalizeInput($fabric, $input);
        $breakdown = $this->calculateBreakdown($normalized);

        return [
            'fabric_snapshot' => $this->buildFabricSnapshot($fabric),
            'costing_input' => $normalized,
            'costing_breakdown' => $breakdown,
            'line_type' => 'fabric',
        ];
    }

    public function buildTrimLinePayload(Trim $trim, array $input): array
    {
        $normalized = $this->normalizeTrimInput($trim, $input);
        $breakdown = $this->calculateBreakdown($normalized);

        return [
            'trim_snapshot' => $this->buildTrimSnapshot($trim),
            'costing_input' => $normalized,
            'costing_breakdown' => $breakdown,
            'line_type' => 'trim',
        ];
    }

    public function normalizeInput(Fabric $fabric, array $input): array
    {
        $options = $this->getDropdownOptions((int) $fabric->business_id);

        $qty = (float) ($input['qty'] ?? $input['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_qty_invalid'));
        }

        $purchaseUom = trim((string) ($input['purchase_uom'] ?? ''));
        $currency = trim((string) ($input['currency'] ?? ''));
        $incoterm = trim((string) ($input['incoterm'] ?? ''));

        $this->assertValueInConfiguredOptions('purchase_uom', $purchaseUom, $options['purchase_uom']);
        $this->assertValueInConfiguredOptions('currency', $currency, $options['currency']);
        $this->assertValueInConfiguredOptions('incoterm', $incoterm, $options['incoterm']);

        $baseMillPrice = $input['base_mill_price'] ?? $input['base_price'] ?? $fabric->price_500_yds;
        $baseMillPrice = (float) ($baseMillPrice ?? 0);
        if ($baseMillPrice < 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_base_mill_price_invalid'));
        }

        $testCost = (float) ($input['test_cost'] ?? 0);
        $surcharge = (float) ($input['surcharge'] ?? 0);
        $finishUpliftPct = (float) ($input['finish_uplift_pct'] ?? 0);
        $wastePct = (float) ($input['waste_pct'] ?? 0);

        if ($testCost < 0 || $surcharge < 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_adders_invalid'));
        }

        $this->assertPercentRange('finish_uplift_pct', $finishUpliftPct);
        $this->assertPercentRange('waste_pct', $wastePct);

        return [
            'qty' => round($qty, 4),
            'purchase_uom' => $purchaseUom,
            'base_mill_price' => round($baseMillPrice, 4),
            'test_cost' => round($testCost, 4),
            'surcharge' => round($surcharge, 4),
            'finish_uplift_pct' => round($finishUpliftPct, 6),
            'waste_pct' => round($wastePct, 6),
            'currency' => $currency,
            'incoterm' => $incoterm,
        ];
    }

    public function normalizeTrimInput(Trim $trim, array $input): array
    {
        $options = $this->getDropdownOptions((int) $trim->business_id);

        $qty = (float) ($input['qty'] ?? $input['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_qty_invalid'));
        }

        $purchaseUom = trim((string) ($input['purchase_uom'] ?? $trim->unit_of_measure ?? ''));
        $currency = trim((string) ($input['currency'] ?? ''));
        $incoterm = trim((string) ($input['incoterm'] ?? ''));

        $allowedPurchaseUom = array_values(array_unique(array_merge(
            (array) ($options['purchase_uom'] ?? []),
            Trim::UOM_OPTIONS
        )));

        $this->assertValueInConfiguredOptions('purchase_uom', $purchaseUom, $allowedPurchaseUom);
        $this->assertValueInConfiguredOptions('currency', $currency, (array) ($options['currency'] ?? []));
        $this->assertValueInConfiguredOptions('incoterm', $incoterm, (array) ($options['incoterm'] ?? []));

        $baseMillPrice = $input['base_mill_price'] ?? $input['base_price'] ?? $trim->unit_cost;
        $baseMillPrice = (float) ($baseMillPrice ?? 0);
        if ($baseMillPrice < 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_base_mill_price_invalid'));
        }

        $testCost = (float) ($input['test_cost'] ?? 0);
        $surcharge = (float) ($input['surcharge'] ?? 0);
        $finishUpliftPct = (float) ($input['finish_uplift_pct'] ?? 0);
        $wastePct = (float) ($input['waste_pct'] ?? 0);

        if ($testCost < 0 || $surcharge < 0) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_adders_invalid'));
        }

        $this->assertPercentRange('finish_uplift_pct', $finishUpliftPct);
        $this->assertPercentRange('waste_pct', $wastePct);

        return [
            'qty' => round($qty, 4),
            'purchase_uom' => $purchaseUom,
            'base_mill_price' => round($baseMillPrice, 4),
            'test_cost' => round($testCost, 4),
            'surcharge' => round($surcharge, 4),
            'finish_uplift_pct' => round($finishUpliftPct, 6),
            'waste_pct' => round($wastePct, 6),
            'currency' => $currency,
            'incoterm' => $incoterm,
        ];
    }

    public function calculateBreakdown(array $normalizedInput): array
    {
        $baseMillPrice = (float) $normalizedInput['base_mill_price'];
        $qty = (float) $normalizedInput['qty'];
        $testCost = (float) $normalizedInput['test_cost'];
        $surcharge = (float) $normalizedInput['surcharge'];
        $finishUpliftPct = (float) $normalizedInput['finish_uplift_pct'];
        $wastePct = (float) $normalizedInput['waste_pct'];

        $finishUpliftAmount = $baseMillPrice * $finishUpliftPct;
        $wasteAmount = $baseMillPrice * $wastePct;
        $unitCost = $baseMillPrice + $testCost + $surcharge + $finishUpliftAmount + $wasteAmount;
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
            'currency' => $normalizedInput['currency'],
            'incoterm' => $normalizedInput['incoterm'],
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
                throw new \InvalidArgumentException(__('projectx::lang.quote_shared_currency_incoterm_required'));
            }
        }
    }

    protected function buildFabricSnapshot(Fabric $fabric): array
    {
        return [
            'fabric_id' => (int) $fabric->id,
            'fabric_sku' => $fabric->fabric_sku,
            'name' => $fabric->name,
            'composition' => $this->fabricManagerUtil->getComponentSummaryForFabric($fabric),
            'width_cm' => $fabric->width_cm,
            'weight_gsm' => $fabric->weight_gsm,
            'fabric_finish' => $fabric->fabric_finish,
            'mill_article_no' => $fabric->mill_article_no,
            'price_500_yds' => $fabric->price_500_yds,
        ];
    }

    protected function buildTrimSnapshot(Trim $trim): array
    {
        return [
            'trim_id' => (int) $trim->id,
            'name' => $trim->name,
            'part_number' => $trim->part_number,
            'trim_category' => optional($trim->trimCategory)->name,
            'material' => $trim->material,
            'color_value' => $trim->color_value,
            'size_dimension' => $trim->size_dimension,
            'unit_of_measure' => $trim->unit_of_measure,
            'unit_cost' => $trim->unit_cost,
            'currency' => $trim->currency,
        ];
    }

    protected function assertPercentRange(string $field, float $value): void
    {
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(__('projectx::lang.quote_percent_invalid', ['field' => $field]));
        }
    }

    protected function assertValueInConfiguredOptions(string $field, string $value, array $options): void
    {
        if ($value === '') {
            throw new \InvalidArgumentException(__('projectx::lang.quote_dropdown_invalid', ['field' => $field]));
        }

        if (empty($options)) {
            return;
        }

        if (array_key_exists($value, $options) || in_array($value, $options, true)) {
            return;
        }

        throw new \InvalidArgumentException(__('projectx::lang.quote_dropdown_invalid', ['field' => $field]));
    }

    protected function resolveCurrencyOptions(?int $business_id = null): array
    {
        if (empty($business_id)) {
            $configCurrencies = $this->normalizeStringOptions(config('projectx.quote_costing_options.currency', []));

            return array_combine($configCurrencies, $configCurrencies) ?: [];
        }

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
            $label = $label !== '' ? $label . ' (' . $code . ')' : $code;

            $options[$code] = $label;
        }

        return $options;
    }

    protected function resolveBusinessStringOptions(?int $business_id, string $settingsColumn, string $configColumn): array
    {
        if (! empty($business_id)) {
            $quoteSetting = QuoteSetting::where('business_id', (int) $business_id)->first();
            $configuredOptions = $this->normalizeStringOptions((array) ($quoteSetting->{$settingsColumn} ?? []));
            if (! empty($configuredOptions)) {
                return $configuredOptions;
            }
        }

        return $this->normalizeStringOptions(config('projectx.quote_costing_options.' . $configColumn, []));
    }

    /**
     * @param  array<int|string, mixed>  $options
     * @return array<int, string>
     */
    protected function normalizeStringOptions(array $options): array
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
}
