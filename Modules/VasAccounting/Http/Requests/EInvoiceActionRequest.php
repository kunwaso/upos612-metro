<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EInvoiceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.einvoice.manage');
    }

    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.einvoice_adapters', [])))],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
