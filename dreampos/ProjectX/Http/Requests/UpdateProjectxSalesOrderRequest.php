<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectxSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectx.sales_order.edit');
    }

    /**
     * Normalize optional fields so root SellPosController receives a stable payload shape.
     */
    protected function prepareForValidation(): void
    {
        $taxRateId = $this->input('tax_rate_id');
        if (is_string($taxRateId)) {
            $taxRateId = trim($taxRateId);
        }

        $this->merge([
            'discount_type' => $this->input('discount_type', 'fixed'),
            'discount_amount' => $this->input('discount_amount', 0),
            'tax_rate_id' => $taxRateId === '' ? null : $taxRateId,
            'shipping_charges' => $this->input('shipping_charges', 0),
            'final_total' => $this->input('final_total', 0),
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'contact_id' => ['required', 'integer'],
            'location_id' => ['required', 'integer'],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'in:days,months'],
            'delivery_date' => ['nullable', 'date'],
            'final_total' => ['required', 'numeric', 'min:0'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.transaction_sell_lines_id' => ['nullable', 'integer'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.variation_id' => ['required', 'integer'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.unit_price_inc_tax' => ['required', 'numeric', 'min:0'],
            'products.*.item_tax' => ['nullable', 'numeric'],
            'products.*.tax_id' => ['nullable'],
            'products.*.line_discount_type' => ['nullable', 'in:fixed,percentage'],
            'products.*.line_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'products.*.product_unit_id' => ['nullable', 'integer'],
            'products.*.base_unit_multiplier' => ['nullable', 'numeric', 'gt:0'],
            'products.*.product_type' => ['nullable', 'string', 'max:255'],
            'products.*.sell_line_note' => ['nullable', 'string'],

            'discount_type' => ['required', 'in:fixed,percentage'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate_id' => ['nullable'],

            'payment' => ['nullable', 'array'],
            'payment.*.payment_id' => ['nullable', 'integer'],
            'payment.*.method' => ['nullable', 'string', 'max:255'],
            'payment.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payment.*.paid_on' => ['nullable', 'string', 'max:255'],
            'payment.*.note' => ['nullable', 'string'],
            'payment.*.account_id' => ['nullable', 'integer'],
            'payment.*.card_number' => ['nullable', 'string', 'max:255'],
            'payment.*.card_holder_name' => ['nullable', 'string', 'max:255'],
            'payment.*.card_transaction_number' => ['nullable', 'string', 'max:255'],
            'payment.*.card_type' => ['nullable', 'string', 'max:255'],
            'payment.*.card_month' => ['nullable', 'string', 'max:255'],
            'payment.*.card_security' => ['nullable', 'string', 'max:255'],
            'payment.*.cheque_number' => ['nullable', 'string', 'max:255'],
            'payment.*.bank_account_number' => ['nullable', 'string', 'max:255'],

            'shipping_details' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'shipping_charges' => ['nullable', 'numeric', 'min:0'],
            'shipping_status' => ['nullable', 'string', 'max:255'],
            'shipping_custom_field_1' => ['nullable', 'string', 'max:255'],
            'shipping_custom_field_2' => ['nullable', 'string', 'max:255'],
            'shipping_custom_field_3' => ['nullable', 'string', 'max:255'],
            'shipping_custom_field_4' => ['nullable', 'string', 'max:255'],
            'shipping_custom_field_5' => ['nullable', 'string', 'max:255'],

            'additional_expense_key_1' => ['nullable', 'string', 'max:255'],
            'additional_expense_key_2' => ['nullable', 'string', 'max:255'],
            'additional_expense_key_3' => ['nullable', 'string', 'max:255'],
            'additional_expense_key_4' => ['nullable', 'string', 'max:255'],
            'additional_expense_value_1' => ['nullable', 'numeric', 'min:0'],
            'additional_expense_value_2' => ['nullable', 'numeric', 'min:0'],
            'additional_expense_value_3' => ['nullable', 'numeric', 'min:0'],
            'additional_expense_value_4' => ['nullable', 'numeric', 'min:0'],

            'sale_note' => ['nullable', 'string'],
        ];
    }
}
