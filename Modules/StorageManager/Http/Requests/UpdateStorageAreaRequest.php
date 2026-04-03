<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('storage_manager.manage');
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:business_locations,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'area_type' => ['required', 'string', Rule::in((array) config('storagemanager.area_types', []))],
            'barcode' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
