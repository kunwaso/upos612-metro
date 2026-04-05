<?php

namespace App\Utils;

use App\Transaction;
use Illuminate\Support\Arr;

class PurchaseViewDataBuilder
{
    /**
     * Parse business custom labels from session.
     */
    public function getCustomLabelsFromSession(): array
    {
        $custom_labels = json_decode((string) session('business.custom_labels', '{}'), true);

        return is_array($custom_labels) ? $custom_labels : [];
    }

    /**
     * Build default location + search flags for create screens.
     *
     * @param  mixed  $business_locations
     */
    public function buildLocationConfig($business_locations): array
    {
        $locations = is_object($business_locations) && method_exists($business_locations, 'toArray')
            ? $business_locations->toArray()
            : (array) $business_locations;

        $default_location = count($locations) === 1 ? array_key_first($locations) : null;
        $search_disable = count($locations) !== 1;

        return [
            'default_location' => $default_location,
            'search_disable' => $search_disable,
        ];
    }

    /**
     * Build field configs for custom labels.
     */
    public function buildCustomFieldConfigs(
        array $custom_labels,
        string $section_key,
        string $input_prefix,
        int $max_fields,
        array $values = []
    ): array {
        $section = Arr::get($custom_labels, $section_key, []);
        $section = is_array($section) ? $section : [];

        $fields = [];
        for ($i = 1; $i <= $max_fields; $i++) {
            $label_key = "custom_field_{$i}";
            $label = (string) Arr::get($section, $label_key, '');
            if ($label === '') {
                continue;
            }

            $required = (bool) Arr::get($section, "is_custom_field_{$i}_required", false);
            $input_name = "{$input_prefix}{$i}";

            $fields[] = [
                'input_name' => $input_name,
                'label' => $label,
                'display_label' => $label.':'.($required ? '*' : ''),
                'required' => $required,
                'placeholder' => $label,
                'value' => Arr::get($values, $input_name),
            ];
        }

        return $fields;
    }

    /**
     * Build generic UI flags consumed by purchase and purchase_order screens.
     */
    public function buildUiFlags(): array
    {
        $business = (array) session('business', []);

        return [
            'hide_tax_class' => ! empty($business['enable_inline_tax']) ? '' : 'hide',
            'show_inline_tax' => ! empty($business['enable_inline_tax']),
            'currency_precision' => (int) ($business['currency_precision'] ?? 2),
            'quantity_precision' => (int) ($business['quantity_precision'] ?? 2),
            'show_editing_product_from_purchase' => ! empty($business['enable_editing_product_from_purchase']),
            'show_lot_number' => ! empty($business['enable_lot_number']),
            'show_product_expiry' => ! empty($business['enable_product_expiry']),
            'show_mfg_date' => ($business['expiry_type'] ?? '') === 'add_manufacturing',
        ];
    }

    /**
     * Build row models for newly-added rows (product search, import, PO/PR line imports).
     *
     * @param  iterable<int, mixed>  $variations
     * @param  iterable<int, mixed>  $taxes
     * @param  array<string, mixed>  $options
     */
    public function buildRowsForVariationSelection(
        object $product,
        iterable $variations,
        int $row_count,
        iterable $taxes,
        object $currency_details,
        array $options = []
    ): array {
        $ui = $this->buildUiFlags();
        $rows = [];
        $index = $row_count;

        foreach ($variations as $variation) {
            $rows[] = $this->buildVariationRowModel(
                $product,
                $variation,
                $index,
                $taxes,
                $currency_details,
                $ui,
                $options
            );
            $index++;
        }

        return [
            'row_models' => $rows,
            'next_row_count' => $index,
        ];
    }

    /**
     * Build row models from existing purchase lines (edit screens).
     *
     * @param  iterable<int, mixed>  $purchase_lines
     * @param  iterable<int, mixed>  $taxes
     * @param  array<string, mixed>  $options
     */
    public function buildRowsForPurchaseEdit(
        iterable $purchase_lines,
        Transaction $purchase,
        iterable $taxes,
        object $currency_details,
        array $options = []
    ): array {
        $ui = $this->buildUiFlags();
        $rows = [];
        $index = 0;

        foreach ($purchase_lines as $purchase_line) {
            $rows[] = $this->buildEditRowModel(
                $purchase_line,
                $purchase,
                $index,
                $taxes,
                $currency_details,
                $ui,
                $options
            );
            $index++;
        }

        return [
            'row_models' => $rows,
            'next_row_count' => $index,
        ];
    }

    /**
     * Build row models from PO/PR source lines for create/edit screens.
     *
     * @param  iterable<int, mixed>  $source_lines
     * @param  iterable<int, mixed>  $taxes
     * @param  array<string, mixed>  $options
     */
    public function buildRowsFromSourceLines(
        iterable $source_lines,
        int $row_count,
        iterable $taxes,
        object $currency_details,
        array $options = []
    ): array {
        $rows = [];
        $index = $row_count;

        foreach ($source_lines as $line) {
            if (! empty($line->quantity) && ! empty($line->po_quantity_purchased)) {
                $remaining = (float) $line->quantity - (float) $line->po_quantity_purchased;
                if ($remaining <= 0) {
                    continue;
                }
            }

            $entry_options = $options;
            if (($options['source_type'] ?? '') === 'purchase_order') {
                $entry_options['purchase_order_line'] = $line;
                $entry_options['purchase_order'] = $options['source_transaction'] ?? null;
            }
            if (($options['source_type'] ?? '') === 'purchase_requisition') {
                $entry_options['purchase_requisition_line'] = $line;
                $entry_options['purchase_requisition'] = $options['source_transaction'] ?? null;
            }
            $entry_options['sub_units'] = Arr::get($options, 'sub_units_array.'.$line->id, []);

            $built = $this->buildRowsForVariationSelection(
                $line->product,
                [$line->variations],
                $index,
                $taxes,
                $currency_details,
                $entry_options
            );

            foreach ($built['row_models'] as $row_model) {
                $rows[] = $row_model;
                $index++;
            }
        }

        return [
            'row_models' => $rows,
            'next_row_count' => $index,
        ];
    }

    /**
     * @param  iterable<int, mixed>  $taxes
     * @param  array<string, mixed>  $ui
     * @param  array<string, mixed>  $options
     */
    protected function buildVariationRowModel(
        object $product,
        object $variation,
        int $row_index,
        iterable $taxes,
        object $currency_details,
        array $ui,
        array $options
    ): array {
        $is_purchase_order = (bool) ($options['is_purchase_order'] ?? false);
        $purchase_order_line = $options['purchase_order_line'] ?? null;
        $purchase_order = $options['purchase_order'] ?? null;
        $purchase_requisition_line = $options['purchase_requisition_line'] ?? null;
        $imported_data = is_array($options['imported_data'] ?? null) ? $options['imported_data'] : [];
        $last_purchase_line = $options['last_purchase_line'] ?? null;

        $exchange_rate = ! empty($purchase_order?->exchange_rate) ? (float) $purchase_order->exchange_rate : 1.0;

        $quantity = ! empty($purchase_order_line) ? (float) $purchase_order_line->quantity : 1.0;
        if (! empty($purchase_requisition_line)) {
            $quantity = (float) $purchase_requisition_line->quantity - (float) $purchase_requisition_line->po_quantity_purchased;
        }
        if (array_key_exists('quantity', $imported_data) && $imported_data['quantity'] !== '') {
            $quantity = (float) $imported_data['quantity'];
        }

        $max_quantity = null;
        if (! empty($purchase_order_line)) {
            $max_quantity = (float) $purchase_order_line->quantity - (float) $purchase_order_line->po_quantity_purchased;
        }
        if (! empty($purchase_requisition_line)) {
            $max_quantity = (float) $purchase_requisition_line->quantity - (float) $purchase_requisition_line->po_quantity_purchased;
        }

        $pp_without_discount = ! empty($purchase_order_line)
            ? ((float) $purchase_order_line->pp_without_discount / $exchange_rate)
            : (float) $variation->default_purchase_price;
        if (array_key_exists('unit_cost_before_discount', $imported_data) && $imported_data['unit_cost_before_discount'] !== '') {
            $pp_without_discount = (float) $imported_data['unit_cost_before_discount'];
        }

        $discount_percent = ! empty($purchase_order_line) ? (float) $purchase_order_line->discount_percent : 0.0;
        if (array_key_exists('discount_percent', $imported_data) && $imported_data['discount_percent'] !== '') {
            $discount_percent = (float) $imported_data['discount_percent'];
        }

        $purchase_price = ! empty($purchase_order_line)
            ? ((float) $purchase_order_line->purchase_price / $exchange_rate)
            : (float) $variation->default_purchase_price;

        $tax_id = ! empty($purchase_order_line) ? $purchase_order_line->tax_id : ($product->tax ?? null);
        if (! empty($imported_data['tax_id'])) {
            $tax_id = $imported_data['tax_id'];
        }

        $purchase_price_inc_tax = $ui['hide_tax_class'] === 'hide'
            ? (float) $variation->default_purchase_price
            : (float) $variation->dpp_inc_tax;
        if (! empty($purchase_order_line)) {
            $purchase_price_inc_tax = (float) $purchase_order_line->purchase_price_inc_tax / $exchange_rate;
        }

        $show_profit_margin = $ui['show_editing_product_from_purchase'] && ! $is_purchase_order;
        $show_sell_price_column = ! $is_purchase_order;

        return [
            'row_index' => $row_index,
            'data_purchase_order_id' => $purchase_order_line?->transaction_id,
            'data_purchase_requisition_id' => $purchase_requisition_line?->transaction_id,
            'purchase_order_line_id' => $purchase_order_line?->id,
            'purchase_requisition_line_id' => $purchase_requisition_line?->id,
            'purchase_line_id' => null,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'product_display_name' => $product->name.' ('.$variation->sub_sku.')',
            'variation_display' => $product->type === 'variable'
                ? '('.$variation->product_variation->name.' : '.$variation->name.')'
                : null,
            'stock_display' => $this->buildStockText($product, $variation),

            'quantity' => $quantity,
            'quantity_formatted' => $this->formatQuantity($quantity, (int) $ui['quantity_precision']),
            'quantity_abs_digit' => (int) ($product->unit->allow_decimal ?? 0) === 0,
            'max_quantity' => $max_quantity,
            'max_quantity_message' => ! is_null($max_quantity)
                ? __('lang_v1.max_quantity_quantity_allowed', ['quantity' => $max_quantity])
                : null,
            'base_unit_cost' => (float) $variation->default_purchase_price,
            'base_unit_selling_price' => (float) $variation->sell_price_inc_tax,
            'product_unit_id' => $product->unit->id,
            'unit_short_name' => $product->unit->short_name,
            'sub_units' => $this->normalizeSubUnits($options['sub_units'] ?? []),
            'show_second_unit' => ! empty($product->second_unit),
            'second_unit_name' => $product->second_unit->short_name ?? null,
            'secondary_unit_quantity' => ! empty($purchase_requisition_line?->secondary_unit_quantity)
                ? $this->formatQuantity((float) $purchase_requisition_line->secondary_unit_quantity, (int) $ui['quantity_precision'])
                : null,

            'pp_without_discount' => $pp_without_discount,
            'pp_without_discount_formatted' => $this->formatMoney($pp_without_discount, $currency_details, (int) $ui['currency_precision']),
            'previous_pp_without_discount' => ! empty($last_purchase_line)
                ? $this->formatMoney((float) $last_purchase_line->pp_without_discount, $currency_details, (int) $ui['currency_precision'])
                : null,
            'discount_percent' => $discount_percent,
            'discount_percent_formatted' => $this->formatMoney($discount_percent, $currency_details, (int) $ui['currency_precision']),
            'previous_discount_percent' => ! empty($last_purchase_line)
                ? $this->formatQuantity((float) $last_purchase_line->discount_percent, (int) $ui['quantity_precision'])
                : null,
            'purchase_price' => $purchase_price,
            'purchase_price_formatted' => $this->formatMoney($purchase_price, $currency_details, (int) $ui['currency_precision']),
            'purchase_line_tax_id' => $tax_id,
            'tax_options' => $this->buildTaxOptions($taxes, $tax_id, $ui['hide_tax_class']),
            'item_tax' => 0,
            'item_tax_formatted' => $this->formatMoney(0, $currency_details, (int) $ui['currency_precision']),
            'purchase_price_inc_tax' => $purchase_price_inc_tax,
            'purchase_price_inc_tax_formatted' => $this->formatMoney($purchase_price_inc_tax, $currency_details, (int) $ui['currency_precision']),
            'row_subtotal_before_tax' => 0,
            'row_subtotal_before_tax_formatted' => $this->formatMoney(0, $currency_details, (int) $ui['currency_precision']),
            'row_subtotal_after_tax' => 0,
            'row_subtotal_after_tax_formatted' => $this->formatMoney(0, $currency_details, (int) $ui['currency_precision']),

            'show_inline_tax' => (bool) $ui['show_inline_tax'],
            'hide_tax_class' => $ui['hide_tax_class'],
            'show_profit_margin' => $show_profit_margin,
            'profit_percent' => (float) ($variation->profit_percent ?? 0),
            'profit_percent_formatted' => $this->formatMoney((float) ($variation->profit_percent ?? 0), $currency_details, (int) $ui['currency_precision']),
            'show_sell_price_column' => $show_sell_price_column,
            'show_sell_price_input' => (bool) $ui['show_editing_product_from_purchase'],
            'default_sell_price' => (float) $variation->sell_price_inc_tax,
            'default_sell_price_formatted' => $this->formatMoney((float) $variation->sell_price_inc_tax, $currency_details, (int) $ui['currency_precision']),
            'show_lot_number' => ! $is_purchase_order && (bool) $ui['show_lot_number'],
            'lot_number' => $imported_data['lot_number'] ?? null,
            'show_product_expiry' => ! $is_purchase_order && (bool) $ui['show_product_expiry'],
            'expiry_period' => $product->expiry_period,
            'expiry_period_type' => $product->expiry_period_type ?: 'month',
            'show_mfg_date' => (bool) $ui['show_mfg_date'],
            'mfg_date' => $imported_data['mfg_date'] ?? null,
            'exp_date' => $imported_data['exp_date'] ?? null,
        ];
    }

    /**
     * @param  iterable<int, mixed>  $taxes
     * @param  array<string, mixed>  $ui
     * @param  array<string, mixed>  $options
     */
    protected function buildEditRowModel(
        object $purchase_line,
        Transaction $purchase,
        int $row_index,
        iterable $taxes,
        object $currency_details,
        array $ui,
        array $options
    ): array {
        $is_purchase_order = (bool) ($options['is_purchase_order'] ?? false);
        $common_settings = is_array($options['common_settings'] ?? null) ? $options['common_settings'] : [];
        $exchange_rate = (float) ($purchase->exchange_rate ?: 1);

        $max_quantity = null;
        if (! empty($purchase_line->purchase_order_line_id) && ! empty($common_settings['enable_purchase_order']) && ! empty($purchase_line->purchase_order_line)) {
            $max_quantity = (float) $purchase_line->purchase_order_line->quantity
                - (float) $purchase_line->purchase_order_line->po_quantity_purchased
                + (float) $purchase_line->quantity;
        }

        $sub_units = $this->normalizeSubUnits($purchase_line->sub_units_options ?? [], $purchase_line->sub_unit_id ?? null);

        $purchase_price = (float) $purchase_line->purchase_price / $exchange_rate;
        $purchase_price_inc_tax = (float) $purchase_line->purchase_price_inc_tax / $exchange_rate;
        $row_subtotal_before_tax = (float) $purchase_line->quantity * $purchase_price;
        $row_subtotal_after_tax = (float) $purchase_line->quantity * $purchase_price_inc_tax;
        $item_tax = (float) $purchase_line->item_tax / $exchange_rate;

        $sp = (float) $purchase_line->variations->sell_price_inc_tax;
        if (! empty($purchase_line->sub_unit->base_unit_multiplier)) {
            $sp = $sp * (float) $purchase_line->sub_unit->base_unit_multiplier;
        }
        $pp = (float) $purchase_line->purchase_price_inc_tax;
        $profit_percent = $pp == 0.0 ? 100.0 : (($sp - $pp) * 100 / $pp);

        return [
            'row_index' => $row_index,
            'data_purchase_order_id' => ! empty($purchase_line->purchase_order_line) && ! empty($common_settings['enable_purchase_order'])
                ? $purchase_line->purchase_order_line->transaction_id
                : null,
            'data_purchase_requisition_id' => ! empty($purchase_line->purchase_requisition_line) && ! empty($common_settings['enable_purchase_requisition'])
                ? $purchase_line->purchase_requisition_line->transaction_id
                : null,
            'purchase_order_line_id' => ! empty($purchase_line->purchase_order_line_id) && ! empty($common_settings['enable_purchase_order'])
                ? $purchase_line->purchase_order_line_id
                : null,
            'purchase_requisition_line_id' => ! empty($purchase_line->purchase_requisition_line_id) && ! empty($common_settings['enable_purchase_requisition'])
                ? $purchase_line->purchase_requisition_line_id
                : null,
            'purchase_line_id' => $purchase_line->id,
            'product_id' => $purchase_line->product_id,
            'variation_id' => $purchase_line->variation_id,
            'product_display_name' => $purchase_line->product->name.' ('.$purchase_line->variations->sub_sku.')',
            'variation_display' => $purchase_line->product->type === 'variable'
                ? '('.$purchase_line->variations->product_variation->name.' : '.$purchase_line->variations->name.')'
                : null,
            'stock_display' => null,

            'quantity' => (float) $purchase_line->quantity,
            'quantity_formatted' => $this->formatQuantity((float) $purchase_line->quantity, (int) $ui['quantity_precision']),
            'quantity_abs_digit' => (int) ($purchase_line->product->unit->allow_decimal ?? 0) === 0,
            'max_quantity' => $max_quantity,
            'max_quantity_message' => ! is_null($max_quantity)
                ? __('lang_v1.max_quantity_quantity_allowed', ['quantity' => $max_quantity])
                : null,
            'base_unit_cost' => (float) $purchase_line->variations->default_purchase_price,
            'base_unit_selling_price' => (float) $purchase_line->variations->sell_price_inc_tax,
            'product_unit_id' => $purchase_line->product->unit->id,
            'unit_short_name' => $purchase_line->product->unit->short_name,
            'sub_units' => $sub_units,
            'show_second_unit' => ! empty($purchase_line->product->second_unit),
            'second_unit_name' => $purchase_line->product->second_unit->short_name ?? null,
            'secondary_unit_quantity' => ! is_null($purchase_line->secondary_unit_quantity)
                ? $this->formatQuantity((float) $purchase_line->secondary_unit_quantity, (int) $ui['quantity_precision'])
                : null,

            'pp_without_discount' => (float) $purchase_line->pp_without_discount / $exchange_rate,
            'pp_without_discount_formatted' => $this->formatMoney((float) $purchase_line->pp_without_discount / $exchange_rate, $currency_details, (int) $ui['currency_precision']),
            'previous_pp_without_discount' => null,
            'discount_percent' => (float) $purchase_line->discount_percent,
            'discount_percent_formatted' => $this->formatMoney((float) $purchase_line->discount_percent, $currency_details, (int) $ui['currency_precision']),
            'previous_discount_percent' => null,
            'purchase_price' => $purchase_price,
            'purchase_price_formatted' => $this->formatMoney($purchase_price, $currency_details, (int) $ui['currency_precision']),
            'purchase_line_tax_id' => $purchase_line->tax_id,
            'tax_options' => $this->buildTaxOptions($taxes, $purchase_line->tax_id, $ui['hide_tax_class']),
            'item_tax' => $item_tax,
            'item_tax_formatted' => $this->formatMoney($item_tax, $currency_details, (int) $ui['currency_precision']),
            'purchase_price_inc_tax' => $purchase_price_inc_tax,
            'purchase_price_inc_tax_formatted' => $this->formatMoney($purchase_price_inc_tax, $currency_details, (int) $ui['currency_precision']),
            'row_subtotal_before_tax' => $row_subtotal_before_tax,
            'row_subtotal_before_tax_formatted' => $this->formatMoney($row_subtotal_before_tax, $currency_details, (int) $ui['currency_precision']),
            'row_subtotal_after_tax' => $row_subtotal_after_tax,
            'row_subtotal_after_tax_formatted' => $this->formatMoney($row_subtotal_after_tax, $currency_details, (int) $ui['currency_precision']),

            'show_inline_tax' => (bool) $ui['show_inline_tax'],
            'hide_tax_class' => $ui['hide_tax_class'],
            'show_profit_margin' => $ui['show_editing_product_from_purchase'] && ! $is_purchase_order,
            'profit_percent' => $profit_percent,
            'profit_percent_formatted' => $this->formatMoney($profit_percent, $currency_details, (int) $ui['currency_precision']),
            'show_sell_price_column' => ! $is_purchase_order,
            'show_sell_price_input' => (bool) $ui['show_editing_product_from_purchase'],
            'default_sell_price' => $sp,
            'default_sell_price_formatted' => $this->formatMoney($sp, $currency_details, (int) $ui['currency_precision']),
            'show_lot_number' => ! $is_purchase_order && (bool) $ui['show_lot_number'],
            'lot_number' => $purchase_line->lot_number,
            'show_product_expiry' => ! $is_purchase_order && (bool) $ui['show_product_expiry'],
            'expiry_period' => $purchase_line->product->expiry_period,
            'expiry_period_type' => $purchase_line->product->expiry_period_type ?: 'month',
            'show_mfg_date' => (bool) $ui['show_mfg_date'],
            'mfg_date' => $this->formatDateValue($purchase_line->mfg_date),
            'exp_date' => $this->formatDateValue($purchase_line->exp_date),
        ];
    }

    /**
     * @param  iterable<int, mixed>  $taxes
     * @return array<int, array<string, mixed>>
     */
    protected function buildTaxOptions(iterable $taxes, $selected_tax_id, string $hide_tax_class): array
    {
        $options = [
            [
                'id' => '',
                'name' => __('lang_v1.none'),
                'amount' => 0,
                'selected' => empty($selected_tax_id) || $hide_tax_class === 'hide',
            ],
        ];

        foreach ($taxes as $tax) {
            $options[] = [
                'id' => $tax->id,
                'name' => $tax->name,
                'amount' => (float) $tax->amount,
                'selected' => ! empty($selected_tax_id) && (string) $selected_tax_id === (string) $tax->id && $hide_tax_class !== 'hide',
            ];
        }

        return $options;
    }

    /**
     * @param  mixed  $sub_units
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeSubUnits($sub_units, $selected_sub_unit_id = null): array
    {
        if (! is_array($sub_units)) {
            return [];
        }

        $normalized = [];
        foreach ($sub_units as $id => $data) {
            if (! is_array($data)) {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'name' => $data['name'] ?? $id,
                'multiplier' => (float) ($data['multiplier'] ?? 1),
                'selected' => ! is_null($selected_sub_unit_id) && (string) $selected_sub_unit_id === (string) $id,
            ];
        }

        return $normalized;
    }

    protected function buildStockText(object $product, object $variation): ?string
    {
        if ((int) ($product->enable_stock ?? 0) !== 1) {
            return null;
        }

        $available_qty = 0;
        if (! empty($variation->variation_location_details) && method_exists($variation->variation_location_details, 'first')) {
            $location_detail = $variation->variation_location_details->first();
            $available_qty = ! empty($location_detail?->qty_available) ? (float) $location_detail->qty_available : 0;
        }

        return __('report.current_stock').': '.$this->formatQuantity($available_qty, (int) session('business.quantity_precision', 2)).' '.($product->unit->short_name ?? '');
    }

    protected function formatMoney(float $value, object $currency_details, int $precision): string
    {
        return number_format(
            $value,
            $precision,
            (string) ($currency_details->decimal_separator ?? '.'),
            (string) ($currency_details->thousand_separator ?? ',')
        );
    }

    protected function formatQuantity(float $value, int $precision): string
    {
        if (function_exists('format_quantity_value')) {
            return (string) format_quantity_value($value);
        }

        return number_format($value, $precision, '.', '');
    }

    protected function formatDateValue($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (function_exists('format_date_value')) {
            return format_date_value($value);
        }

        return (string) $value;
    }
}

