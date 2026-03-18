<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessQuoteWizardStepRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.quote_wizard.use');
    }

    public function rules()
    {
        $providers = array_keys((array) config('aichat.chat.providers', []));

        return [
            'message' => ['nullable', 'string', 'max:12000'],
            'draft_id' => ['nullable', 'uuid'],
            'selected_contact_id' => ['nullable', 'integer', 'min:1'],
            'selected_product_id' => ['nullable', 'integer', 'min:1'],
            'selected_line_uid' => ['nullable', 'uuid'],
            'selected_remove_line_uid' => ['nullable', 'uuid'],
            'provider' => ['nullable', 'string', Rule::in($providers)],
            'model' => [
                'nullable',
                'string',
                'max:150',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $provider = (string) $this->input('provider');
                    if ($provider === '') {
                        return;
                    }

                    $models = (array) config("aichat.chat.providers.{$provider}.models", []);
                    if (! isset($models[$value])) {
                        $fail(__('aichat::lang.chat_validation_model_invalid'));
                    }
                },
            ],
        ];
    }
}
