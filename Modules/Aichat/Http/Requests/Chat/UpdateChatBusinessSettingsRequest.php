<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatBusinessSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.settings');
    }

    public function rules()
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'default_provider' => ['nullable', 'string', Rule::in(array_keys((array) config('aichat.chat.providers', [])))],
            'default_model' => ['nullable', 'string', 'max:150'],
            'system_prompt' => ['nullable', 'string', 'max:10000'],
            'reasoning_rules' => ['nullable', 'string', 'max:10000'],
            'model_allowlist' => ['nullable'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'pii_policy' => ['nullable', 'string', Rule::in(['off', 'warn', 'block'])],
            'moderation_enabled' => ['nullable', 'boolean'],
            'moderation_terms' => ['nullable', 'string', 'max:20000'],
            'idle_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:720'],
            'suggested_replies' => ['nullable', 'string', 'max:5000'],
            'share_ttl_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'moderation_enabled' => $this->boolean('moderation_enabled'),
        ]);
    }
}
