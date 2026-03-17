<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserChatProfileRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.settings');
    }

    public function rules()
    {
        return [
            'display_name' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'timezone', 'max:64'],
            'concerns_topics' => ['nullable', 'string', 'max:5000'],
            'preferences' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'display_name' => $this->normalizeNullableString('display_name'),
            'timezone' => $this->normalizeNullableString('timezone'),
            'concerns_topics' => $this->normalizeNullableString('concerns_topics'),
            'preferences' => $this->normalizeNullableString('preferences'),
        ]);
    }

    protected function normalizeNullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}

