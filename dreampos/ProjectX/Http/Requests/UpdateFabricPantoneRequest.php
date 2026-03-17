<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFabricPantoneRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('product.view');
    }

    /**
     * Normalize explicit null JSON payloads to an empty array.
     */
    protected function prepareForValidation()
    {
        if ($this->isJson() && $this->exists('items') && is_null($this->input('items'))) {
            $this->merge(['items' => []]);
        }
    }

    public function rules()
    {
        return [
            'items' => 'present|array',
            'items.*' => 'nullable|string|max:50',
        ];
    }
}
