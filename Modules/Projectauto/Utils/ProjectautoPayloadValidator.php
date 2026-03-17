<?php

namespace Modules\Projectauto\Utils;

use Illuminate\Support\Facades\Validator;
use Modules\Projectauto\Entities\ProjectautoPendingTask;

class ProjectautoPayloadValidator
{
    public function validateTaskPayload(string $type, array $payload): array
    {
        $type = trim($type);
        $rules = $this->rulesForType($type);

        return Validator::make($payload, $rules)->validate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForType(string $type): array
    {
        if ($type === ProjectautoPendingTask::TASK_TYPE_ADJUST_STOCK) {
            return [
                'location_id' => ['required', 'integer', 'exists:business_locations,id'],
                'transaction_date' => ['nullable', 'date'],
                'adjustment_type' => ['nullable', 'in:normal,abnormal'],
                'additional_notes' => ['nullable', 'string'],
                'total_amount_recovered' => ['nullable', 'numeric', 'min:0'],
                'ref_no' => ['nullable', 'string', 'max:191'],
                'products' => ['required', 'array', 'min:1'],
                'products.*' => ['required', 'array:product_id,variation_id,quantity,unit_price,lot_no_line_id'],
                'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'products.*.variation_id' => ['required', 'integer', 'exists:variations,id'],
                'products.*.quantity' => ['required', 'numeric', 'gt:0'],
                'products.*.unit_price' => ['required', 'numeric', 'min:0'],
                'products.*.lot_no_line_id' => ['nullable', 'integer'],
            ];
        }

        if ($type === ProjectautoPendingTask::TASK_TYPE_CREATE_INVOICE) {
            return [
                'location_id' => ['required', 'integer', 'exists:business_locations,id'],
                'contact_id' => ['required', 'integer', 'exists:contacts,id'],
                'transaction_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:final,draft'],
                'tax_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
                'discount_type' => ['nullable', 'in:fixed,percentage'],
                'discount_amount' => ['nullable', 'numeric', 'min:0'],
                'invoice_no' => ['nullable', 'string', 'max:191'],
                'sale_note' => ['nullable', 'string'],
                'staff_note' => ['nullable', 'string'],
                'products' => ['required', 'array', 'min:1'],
                'products.*' => ['required', 'array:product_id,variation_id,quantity,unit_price,unit_price_inc_tax,item_tax,tax_id,line_discount_type,line_discount_amount,lot_no_line_id'],
                'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'products.*.variation_id' => ['required', 'integer', 'exists:variations,id'],
                'products.*.quantity' => ['required', 'numeric', 'gt:0'],
                'products.*.unit_price_inc_tax' => ['required', 'numeric', 'min:0'],
                'products.*.unit_price' => ['nullable', 'numeric', 'min:0'],
                'products.*.item_tax' => ['nullable', 'numeric', 'min:0'],
                'products.*.tax_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
                'products.*.line_discount_type' => ['nullable', 'in:fixed,percentage'],
                'products.*.line_discount_amount' => ['nullable', 'numeric', 'min:0'],
                'products.*.lot_no_line_id' => ['nullable', 'integer'],
                'payments' => ['nullable', 'array'],
                'payments.*' => ['required', 'array:amount,method,paid_on,note,account_id'],
                'payments.*.amount' => ['required', 'numeric', 'gt:0'],
                'payments.*.method' => ['required', 'string', 'max:50'],
                'payments.*.paid_on' => ['nullable', 'date'],
                'payments.*.note' => ['nullable', 'string'],
                'payments.*.account_id' => ['nullable', 'integer'],
            ];
        }

        if ($type === ProjectautoPendingTask::TASK_TYPE_ADD_PRODUCT) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'type' => ['nullable', 'in:single,variable,combo'],
                'unit_id' => ['required', 'integer', 'exists:units,id'],
                'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'sub_category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'tax' => ['nullable', 'integer', 'exists:tax_rates,id'],
                'sku' => ['nullable', 'string', 'max:191'],
                'barcode_type' => ['nullable', 'string', 'max:100'],
                'alert_quantity' => ['nullable', 'numeric', 'min:0'],
                'tax_type' => ['nullable', 'in:inclusive,exclusive'],
                'product_description' => ['nullable', 'string'],
                'enable_stock' => ['nullable', 'boolean'],
                'not_for_selling' => ['nullable', 'boolean'],
                'product_locations' => ['nullable', 'array'],
                'product_locations.*' => ['integer', 'exists:business_locations,id'],
                'single_dpp' => ['required', 'numeric', 'min:0'],
                'single_dpp_inc_tax' => ['required', 'numeric', 'min:0'],
                'profit_percent' => ['nullable', 'numeric'],
                'single_dsp' => ['required', 'numeric', 'min:0'],
                'single_dsp_inc_tax' => ['required', 'numeric', 'min:0'],
            ];
        }

        return ['_type' => ['in:__invalid__']];
    }
}
