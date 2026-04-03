<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.operate');
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer'],
            'source_slot_id' => ['required', 'integer'],
            'quarantine_slot_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'variation_id' => ['nullable', 'integer'],
            'inventory_status' => ['nullable', 'string', 'max:30'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'lot_number' => ['nullable', 'string', 'max:120'],
            'expiry_date' => ['nullable', 'date'],
            'reason_code' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
