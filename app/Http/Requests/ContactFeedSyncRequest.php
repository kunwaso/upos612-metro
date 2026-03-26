<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFeedSyncRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'provider' => ['nullable', 'string', 'in:google'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
