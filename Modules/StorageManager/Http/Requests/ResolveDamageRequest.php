<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.approve');
    }

    public function rules(): array
    {
        return [
            'resolution_action' => ['required', 'in:dispose,release'],
            'resolution_notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.release_slot_id' => ['nullable', 'integer'],
        ];
    }
}
