<?php

namespace Modules\Projectauto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectautoTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! empty($this->user());
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:create_invoice,add_product,adjust_stock'],
            'payload' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
        ];
    }
}
