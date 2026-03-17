<?php

namespace Modules\Projectauto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProjectautoTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectauto.tasks.approve');
    }

    public function rules(): array
    {
        return [
            'rejection_notes' => ['required', 'string'],
        ];
    }
}
