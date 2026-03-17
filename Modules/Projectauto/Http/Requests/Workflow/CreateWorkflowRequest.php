<?php

namespace Modules\Projectauto\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectauto.settings.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
