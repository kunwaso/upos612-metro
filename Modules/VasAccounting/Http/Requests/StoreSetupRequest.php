<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.setup.manage');
    }

    public function rules(): array
    {
        return [
            'book_currency' => ['required', 'string', 'max:10'],
            'inventory_method' => ['required', 'string', 'max:50'],
            'is_enabled' => ['nullable', 'boolean'],
            'posting_map' => ['nullable', 'array'],
            'posting_map.*' => ['nullable', 'integer'],
            'einvoice_settings.provider' => ['nullable', 'string', 'max:50'],
            'einvoice_settings.mode' => ['nullable', 'string', 'max:20'],
            'einvoice_settings.issue_on_post' => ['nullable', 'boolean'],
            'depreciation_settings.method' => ['nullable', 'string', 'max:50'],
            'depreciation_settings.run_day_of_month' => ['nullable', 'integer', 'between:1,28'],
            'tax_settings.declaration_currency' => ['nullable', 'string', 'max:10'],
            'feature_flags' => ['nullable', 'array'],
            'feature_flags.*' => ['nullable', 'boolean'],
            'approval_settings.default_manual_voucher_status' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.document_statuses', [])))],
            'approval_settings.require_manual_voucher_approval' => ['nullable', 'boolean'],
            'integration_settings.api_guard' => ['nullable', 'string', 'max:50'],
            'integration_settings.bank_statement_provider' => ['nullable', 'string', 'max:50'],
            'integration_settings.tax_export_provider' => ['nullable', 'string', 'max:50'],
            'integration_settings.payroll_bridge_provider' => ['nullable', 'string', 'max:50'],
        ];
    }
}
