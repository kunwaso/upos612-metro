<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFabricShareSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('projectx.fabric.create')
            || auth()->user()->can('product.create');
    }

    public function rules()
    {
        return [
            'share_enabled' => 'nullable|boolean',
            'regenerate_share_token' => 'nullable|boolean',
            'share_password' => 'nullable|string|min:6|max:255',
            'clear_share_password' => 'nullable|boolean',
            'share_rate_limit_per_day' => 'nullable|integer|min:1|max:1000000',
            'share_expires_at' => 'nullable|date|after:now',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'share_enabled' => $this->boolean('share_enabled'),
            'regenerate_share_token' => $this->boolean('regenerate_share_token'),
            'clear_share_password' => $this->boolean('clear_share_password'),
        ]);

        if (! is_string($this->input('share_password'))) {
            return;
        }

        $password = trim((string) $this->input('share_password'));
        $this->merge([
            'share_password' => $password === '' ? null : $password,
        ]);
    }
}