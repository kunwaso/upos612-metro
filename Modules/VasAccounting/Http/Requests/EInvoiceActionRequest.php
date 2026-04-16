<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EInvoiceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->can('vas_accounting.einvoice.manage')
            || auth()->user()->can('vas_accounting.einvoices.manage')
            || auth()->user()->can('vas_accounting.filing.operator');
    }

    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.einvoice_adapters', [])))],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
