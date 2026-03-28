<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankStatementImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.cash_bank.manage');
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['nullable', 'integer'],
            'provider' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.bank_statement_import_adapters', [])))],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'statement_lines' => ['required', 'string'],
        ];
    }
}
