<?php

namespace Modules\Projectauto\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Projectauto\Workflow\Support\WorkflowGraphValidator;

class UpdateWorkflowDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('projectauto.settings.manage');
    }

    public function rules(): array
    {
        return [
            'graph' => ['required', 'array'],
            'graph.nodes' => ['required', 'array'],
            'graph.edges' => ['required', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var WorkflowGraphValidator $graphValidator */
            $graphValidator = app(WorkflowGraphValidator::class);

            try {
                $graphValidator->validate((array) $this->input('graph', []), false);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }
}
