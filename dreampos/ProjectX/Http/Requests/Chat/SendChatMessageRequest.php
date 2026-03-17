<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendChatMessageRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');
        $providers = array_keys((array) config('projectx.chat.providers', []));

        return [
            'prompt' => ['required', 'string', 'max:12000'],
            'provider' => ['required', 'string', Rule::in($providers)],
            'model' => [
                'required',
                'string',
                'max:150',
                function ($attribute, $value, $fail) {
                    $provider = (string) $this->input('provider');
                    $models = (array) config("projectx.chat.providers.{$provider}.models", []);
                    if (! isset($models[$value])) {
                        $fail(__('projectx::lang.chat_validation_model_invalid'));
                    }
                },
            ],
            'fabric_insight' => ['nullable', 'boolean'],
            'fabric_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'quote_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_quotes', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'trim_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_trims', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'transaction_id' => [
                'nullable',
                'integer',
                Rule::exists('transactions', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'fabric_insight' => $this->boolean('fabric_insight'),
        ]);
    }
}
