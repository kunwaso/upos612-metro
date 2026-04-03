<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCmsStorefrontRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
