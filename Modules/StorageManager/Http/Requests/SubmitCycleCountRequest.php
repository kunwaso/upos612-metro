<?php

namespace Modules\StorageManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCycleCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('storage_manager.count');
    }

    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.counted_qty' => ['required', 'numeric', 'min:0'],
            'lines.*.reason_code' => ['nullable', 'string', 'max:60'],
        ];
    }
}
