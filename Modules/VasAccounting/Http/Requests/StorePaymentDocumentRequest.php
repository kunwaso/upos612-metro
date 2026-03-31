<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.cash_bank.manage');
    }

    public function rules(): array
    {
        return [
            'payment_kind' => ['required', 'string', Rule::in(['cash_receipt', 'cash_payment', 'bank_receipt', 'bank_payment'])],
            'contact_id' => ['required', 'integer'],
            'business_location_id' => ['nullable', 'integer'],
            'cashbook_id' => ['nullable', 'integer'],
            'bank_account_id' => ['nullable', 'integer'],
            'payment_instrument' => ['nullable', 'string', 'max:60'],
            'document_date' => ['required', 'date'],
            'posting_date' => ['required', 'date'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'reference' => ['nullable', 'string', 'max:191'],
            'external_reference' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'settlement_targets' => ['nullable', 'array'],
            'settlement_targets.*.target_voucher_id' => ['required_with:settlement_targets.*.amount', 'integer'],
            'settlement_targets.*.amount' => ['required_with:settlement_targets.*.target_voucher_id', 'numeric', 'min:0.0001'],
            'settlement_targets.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $kind = (string) $this->input('payment_kind');
            if (str_starts_with($kind, 'bank_') && ! $this->filled('bank_account_id')) {
                $validator->errors()->add('bank_account_id', 'Choose a bank account for bank receipts and bank payments.');
            }

            if (str_starts_with($kind, 'cash_') && ! $this->filled('cashbook_id')) {
                $validator->errors()->add('cashbook_id', 'Choose a cashbook for cash receipts and cash payments.');
            }
        });
    }
}
