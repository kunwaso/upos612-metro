<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class WipeBusinessMemoryRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.manage_all_memories');
    }

    public function rules()
    {
        return [
            'business' => ['required', 'integer', 'exists:business,id'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'business' => (int) $this->route('business'),
        ]);
    }
}

