<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'run_type' => ['required', 'string', Rule::in(['bank_statement_import', 'tax_export', 'payroll_bridge', 'einvoice_sync'])],
            'provider' => ['nullable', 'string', 'max:60'],
            'action' => ['required', 'string', 'max:80'],
            'bank_account_id' => ['nullable', 'integer'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'statement_lines' => ['nullable', 'string'],
            'export_type' => ['nullable', 'string', 'max:80'],
            'payroll_group_id' => ['nullable', 'integer'],
            'einvoice_document_id' => ['nullable', 'integer'],
        ];
    }
}
