<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectxOrderHoldStatusRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.sales_order.update_status');
    }

    public function rules()
    {
        return [
            'is_on_hold' => ['required', 'boolean'],
        ];
    }
}

