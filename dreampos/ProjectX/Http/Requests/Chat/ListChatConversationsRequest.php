<?php

namespace Modules\ProjectX\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListChatConversationsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.chat.view');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'fabric_id' => [
                'nullable',
                'integer',
                Rule::exists('projectx_fabrics', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'include_archived' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'include_archived' => $this->boolean('include_archived'),
        ]);
    }
}
