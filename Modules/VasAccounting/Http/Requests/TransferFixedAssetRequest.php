<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.assets.manage');
    }

    public function rules(): array
    {
        return [
            'business_location_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
