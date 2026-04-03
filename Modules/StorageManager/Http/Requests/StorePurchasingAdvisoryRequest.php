<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasingAdvisoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->can('purchase_requisition.create')
            && (auth()->user()->can('storage_manager.manage') || auth()->user()->can('storage_manager.approve'));
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
