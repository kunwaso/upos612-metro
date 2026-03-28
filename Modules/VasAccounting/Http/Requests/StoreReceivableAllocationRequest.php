<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceivableAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.receivables.manage');
    }

    public function rules(): array
    {
        return [
            'invoice_voucher_id' => ['required', 'integer'],
            'payment_voucher_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'allocation_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
