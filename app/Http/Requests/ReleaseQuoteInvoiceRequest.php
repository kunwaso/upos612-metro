<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseQuoteInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('product_quote.release_invoice');
    }

    public function rules()
    {
        return [
            'confirm' => 'nullable|boolean',
        ];
    }
}
