<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'request_id' => ['nullable', 'string', 'max:120'],
            'meta' => ['nullable', 'array'],
            'event_type' => ['nullable', 'string', 'max:40'],
        ];
    }
}
