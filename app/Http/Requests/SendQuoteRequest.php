<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('product_quote.send');
    }

    public function rules()
    {
        return [
            'to_email' => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
        ];
    }
}
