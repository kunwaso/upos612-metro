<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisposeFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.assets.manage');
    }

    public function rules(): array
    {
        return [
            'disposed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
