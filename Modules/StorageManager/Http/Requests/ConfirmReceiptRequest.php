<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.operate');
    }

    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.executed_qty' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.lot_number' => ['nullable', 'string', 'max:120'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.staging_slot_id' => ['required', 'integer'],
        ];
    }
}
