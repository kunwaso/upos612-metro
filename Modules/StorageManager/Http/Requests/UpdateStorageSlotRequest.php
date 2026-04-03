<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('storage_manager.manage');
    }

    public function rules(): array
    {
        return [
            'location_id'  => ['required', 'integer', 'exists:business_locations,id'],
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'area_id'      => ['nullable', 'integer', 'exists:storage_areas,id'],
            'row'          => ['required', 'string', 'max:50'],
            'position'     => ['required', 'string', 'max:50'],
            'slot_code'    => ['nullable', 'string', 'max:50'],
            'barcode'      => ['nullable', 'string', 'max:120'],
            'slot_type'    => ['nullable', 'string', 'max:30'],
            'status'       => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'pick_sequence' => ['nullable', 'integer', 'min:0'],
            'putaway_sequence' => ['nullable', 'integer', 'min:0'],
            'allows_mixed_sku' => ['nullable', 'boolean'],
            'allows_mixed_lot' => ['nullable', 'boolean'],
            'max_capacity' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allows_mixed_sku' => $this->boolean('allows_mixed_sku'),
            'allows_mixed_lot' => $this->boolean('allows_mixed_lot'),
        ]);
    }
}
