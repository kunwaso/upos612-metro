<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCycleCountSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.count');
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'freeze_mode' => ['required', 'in:soft,hard'],
            'blind_count' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
