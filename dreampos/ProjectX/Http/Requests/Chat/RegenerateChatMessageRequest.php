<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateChatMessageRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.edit');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
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
