<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendChatMessageRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.chat.edit');
    }

    public function rules()
    {
        $providers = array_keys((array) config('aichat.chat.providers', []));

        return [
            'prompt' => ['required', 'string', 'max:12000'],
            'provider' => ['required', 'string', Rule::in($providers)],
            'model' => [
                'required',
                'string',
                'max:150',
                function ($attribute, $value, $fail) {
                    $provider = (string) $this->input('provider');
                    $models = (array) config("aichat.chat.providers.{$provider}.models", []);

                    if (! isset($models[$value])) {
                        $fail(__('aichat::lang.chat_validation_model_invalid'));
                    }
                },
            ],
        ];
    }
}
