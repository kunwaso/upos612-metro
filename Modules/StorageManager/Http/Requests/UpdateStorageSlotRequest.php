<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'row'          => ['required', 'string', 'max:50'],
            'position'     => ['required', 'string', 'max:50'],
            'slot_code'    => ['nullable', 'string', 'max:50'],
            'max_capacity' => ['required', 'integer', 'min:0'],
        ];
    }
}
