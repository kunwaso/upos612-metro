<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersistentMemoryNameRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.manage_all_memories');
    }

    public function rules()
    {
        return [
            'business' => ['required', 'integer', 'exists:business,id'],
            'display_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function prepareForValidation()
    {
        $displayName = $this->input('display_name');
        if (is_string($displayName)) {
            $displayName = trim($displayName);
        }

        $this->merge([
            'business' => (int) $this->route('business'),
            'display_name' => $displayName,
        ]);
    }
}
