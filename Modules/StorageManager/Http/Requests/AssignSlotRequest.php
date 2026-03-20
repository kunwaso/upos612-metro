<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('storage_manager.manage');
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'slot_id'    => ['required', 'integer', 'exists:storage_slots,id'],
        ];
    }
}
