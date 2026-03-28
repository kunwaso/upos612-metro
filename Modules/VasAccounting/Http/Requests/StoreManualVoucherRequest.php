<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.vouchers.manage');
    }

    public function rules(): array
    {
        return [
            'voucher_type' => ['required', 'string', 'max:50'],
            'module_area' => ['nullable', 'string', 'max:60'],
            'document_type' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', Rule::in(array_keys((array) config('vasaccounting.document_statuses', [])))],
            'posting_date' => ['required', 'date'],
            'document_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.employee_id' => ['nullable', 'integer'],
            'lines.*.department_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.warehouse_id' => ['nullable', 'integer'],
            'lines.*.asset_id' => ['nullable', 'integer'],
            'lines.*.contract_id' => ['nullable', 'integer'],
            'lines.*.budget_id' => ['nullable', 'integer'],
        ];
    }
}
