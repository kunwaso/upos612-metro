<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NativeDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string'],
            'action' => ['nullable', 'string', Rule::in(['submit', 'approve', 'reject', 'cancel', 'post', 'reverse'])],
        ];
    }
}
