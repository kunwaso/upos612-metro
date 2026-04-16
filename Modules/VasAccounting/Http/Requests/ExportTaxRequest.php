<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->can('vas_accounting.tax.manage')
            || auth()->user()->can('vas_accounting.filing.operator');
    }

    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.tax_export_adapters', [])))],
            'export_type' => ['required', 'string', Rule::in(['sales_book', 'purchase_book', 'vat_declaration'])],
        ];
    }
}
