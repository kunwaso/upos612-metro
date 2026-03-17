<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductOrderHoldStatusRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('product_sales_order.update_status');
    }

    public function rules()
    {
        return [
            'is_on_hold' => ['required', 'boolean'],
        ];
    }
}

