<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:both,customer,supplier'],
        ];
    }
}
