<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevertQuoteToDraftRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check()
            && auth()->user()->can('product_quote.edit')
            && auth()->user()->can('product_quote.admin_override');
    }

    public function rules()
    {
        $business_id = (int) $this->session()->get('user.business_id');

        return [
            'quote_id' => [
                'required',
                'integer',
                Rule::exists('product_quotes', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'quote_id' => $this->route('id'),
        ]);
    }
}
