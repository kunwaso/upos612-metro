<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNativeInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.invoices.manage');
    }

    public function rules(): array
    {
        return [
            'invoice_kind' => ['required', 'string', Rule::in(['purchase_invoice', 'purchase_debit_note', 'sales_invoice', 'sales_credit_note'])],
            'contact_id' => ['required', 'integer'],
            'business_location_id' => ['nullable', 'integer'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'invoice_scheme_id' => ['nullable', 'integer'],
            'invoice_layout_id' => ['nullable', 'integer'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'reference' => ['nullable', 'string', 'max:191'],
            'external_reference' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'pay_term_number' => ['nullable', 'integer', 'min:1'],
            'pay_term_type' => ['nullable', 'string', Rule::in(['days', 'months'])],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.account_id' => ['required', 'integer'],
            'line_items.*.description' => ['nullable', 'string', 'max:255'],
            'line_items.*.net_amount' => ['required', 'numeric', 'min:0.0001'],
            'line_items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.tax_code_id' => ['nullable', 'integer'],
            'line_items.*.department_id' => ['nullable', 'integer'],
            'line_items.*.cost_center_id' => ['nullable', 'integer'],
            'line_items.*.project_id' => ['nullable', 'integer'],
            'line_items.*.warehouse_id' => ['nullable', 'integer'],
            'line_items.*.budget_id' => ['nullable', 'integer'],
            'line_items.*.product_id' => ['nullable', 'integer'],
            'immediate_payment' => ['nullable', 'array'],
            'immediate_payment.amount' => ['nullable', 'numeric', 'gt:0'],
            'immediate_payment.payment_kind' => ['nullable', 'string', Rule::in(['cash_payment', 'bank_payment', 'cash_receipt', 'bank_receipt'])],
            'immediate_payment.payment_method' => ['nullable', 'string', 'max:40'],
            'immediate_payment.paid_on' => ['nullable', 'date'],
            'immediate_payment.cashbook_id' => ['nullable', 'integer'],
            'immediate_payment.bank_account_id' => ['nullable', 'integer'],
            'immediate_payment.external_reference' => ['nullable', 'string', 'max:191'],
            'immediate_payment.notes' => ['nullable', 'string'],
            'action' => ['nullable', 'string', Rule::in(['save_draft', 'submit', 'save_and_post'])],
        ];
    }
}
