<?php

namespace Modules\Projectauto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyAcceptProjectautoTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectauto.tasks.approve');
    }

    public function rules(): array
    {
        return [
            'payload' => ['required'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
