<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCycleCountShortagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.approve');
    }

    public function rules(): array
    {
        return [
            'approval_notes' => ['nullable', 'string'],
        ];
    }
}
