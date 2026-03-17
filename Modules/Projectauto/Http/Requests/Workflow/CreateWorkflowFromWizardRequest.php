<?php

namespace Modules\Projectauto\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Projectauto\Workflow\Support\ConditionSpecificationValidator;
use Modules\Projectauto\Workflow\Support\NodeConfigValidator;
use Modules\Projectauto\Workflow\Support\PredefinedRuleCatalog;

class CreateWorkflowFromWizardRequest extends FormRequest
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
            'trigger_type' => ['required', 'string'],
            'trigger_config' => ['nullable', 'array'],
            'condition' => ['nullable', 'array:field,operator,value'],
            'condition.field' => ['nullable', 'string'],
            'condition.operator' => ['nullable', 'string'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
            'actions.*.config' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'condition_expression' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var PredefinedRuleCatalog $catalog */
            $catalog = app(PredefinedRuleCatalog::class);
            /** @var NodeConfigValidator $configValidator */
            $configValidator = app(NodeConfigValidator::class);
            /** @var ConditionSpecificationValidator $conditionValidator */
            $conditionValidator = app(ConditionSpecificationValidator::class);

            $triggerType = $this->input('trigger_type');
            $triggerDefinition = $catalog->trigger($triggerType);
            if (empty($triggerDefinition)) {
                $validator->errors()->add('trigger_type', __('validation.in', ['attribute' => 'trigger']));

                return;
            }

            foreach ($configValidator->validate($triggerDefinition['config_schema'] ?? [], (array) $this->input('trigger_config', []), 'trigger_config') as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }

            foreach ($conditionValidator->validate($this->input('condition'), $triggerType) as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }

            foreach ((array) $this->input('actions', []) as $index => $action) {
                $definition = $catalog->action($action['type'] ?? '');
                if (empty($definition)) {
                    $validator->errors()->add("actions.$index.type", __('validation.in', ['attribute' => 'action']));
                    continue;
                }

                $errors = $configValidator->validate($definition['config_schema'] ?? [], (array) ($action['config'] ?? []), "actions.$index.config");
                foreach ($errors as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }
}
