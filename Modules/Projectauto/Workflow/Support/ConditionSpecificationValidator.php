<?php

namespace Modules\Projectauto\Workflow\Support;

class ConditionSpecificationValidator
{
    protected PredefinedRuleCatalog $catalog;

    public function __construct(PredefinedRuleCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function validate(?array $condition, ?string $triggerType, string $prefix = 'condition'): array
    {
        if (empty($condition)) {
            return [];
        }

        $errors = [];
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (empty($field)) {
            $errors[$prefix . '.field'][] = __('validation.required', ['attribute' => __('projectauto::lang.condition_field')]);
        }

        if (empty($operator)) {
            $errors[$prefix . '.operator'][] = __('validation.required', ['attribute' => __('projectauto::lang.condition_operator')]);
        }

        $fieldDefinition = $field ? $this->catalog->conditionField($field) : null;
        if ($field && empty($fieldDefinition)) {
            $errors[$prefix . '.field'][] = __('validation.in', ['attribute' => __('projectauto::lang.condition_field')]);
        }

        if ($fieldDefinition && $triggerType && ! in_array($triggerType, $fieldDefinition['supported_triggers'] ?? [], true)) {
            $errors[$prefix . '.field'][] = __('projectauto::lang.condition_field_not_supported_for_trigger');
        }

        $operators = $this->catalog->catalog()['operators'];
        if ($operator && ! array_key_exists($operator, $operators)) {
            $errors[$prefix . '.operator'][] = __('validation.in', ['attribute' => __('projectauto::lang.condition_operator')]);
        }

        if ($fieldDefinition && $operator && isset($operators[$operator])) {
            $valueType = $fieldDefinition['value_type'] ?? 'string';
            $supported = $operators[$operator]['value_types'] ?? [];
            if (! in_array($valueType, $supported, true)) {
                $errors[$prefix . '.operator'][] = __('projectauto::lang.condition_operator_not_supported_for_field');
            }

            if (in_array($operator, ['in', 'not_in'], true)) {
                if (! is_array($value) || empty($value)) {
                    $errors[$prefix . '.value'][] = __('projectauto::lang.condition_value_must_be_list');
                } else {
                    foreach (array_values($value) as $index => $item) {
                        foreach ($this->validateScalarType($item, $valueType, $prefix . '.value.' . $index) as $key => $messages) {
                            foreach ($messages as $message) {
                                $errors[$key][] = $message;
                            }
                        }
                    }
                }
            } else {
                if ($value === null || $value === '') {
                    $errors[$prefix . '.value'][] = __('validation.required', ['attribute' => __('projectauto::lang.condition_value')]);
                } else {
                    foreach ($this->validateScalarType($value, $valueType, $prefix . '.value') as $key => $messages) {
                        foreach ($messages as $message) {
                            $errors[$key][] = $message;
                        }
                    }
                }
            }

            if (! empty($fieldDefinition['options']) && ! in_array($operator, ['in', 'not_in'], true) && $value !== null && $value !== '') {
                $allowed = collect($fieldDefinition['options'])->pluck('value')->all();
                if (! in_array($value, $allowed, true)) {
                    $errors[$prefix . '.value'][] = __('validation.in', ['attribute' => __('projectauto::lang.condition_value')]);
                }
            }

            if (! empty($fieldDefinition['options']) && in_array($operator, ['in', 'not_in'], true) && is_array($value)) {
                $allowed = collect($fieldDefinition['options'])->pluck('value')->all();
                foreach (array_values($value) as $index => $item) {
                    if (! in_array($item, $allowed, true)) {
                        $errors[$prefix . '.value.' . $index][] = __('validation.in', ['attribute' => __('projectauto::lang.condition_value')]);
                    }
                }
            }
        }

        return $errors;
    }

    protected function validateScalarType($value, string $valueType, string $path): array
    {
        if ($valueType === 'number' && ! is_numeric($value)) {
            return [$path => [__('validation.numeric', ['attribute' => __('projectauto::lang.condition_value')])]];
        }

        if ($valueType === 'boolean' && ! is_bool($value) && ! in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            return [$path => [__('validation.boolean', ['attribute' => __('projectauto::lang.condition_value')])]];
        }

        if ($valueType === 'string' && ! is_scalar($value)) {
            return [$path => [__('validation.string', ['attribute' => __('projectauto::lang.condition_value')])]];
        }

        return [];
    }
}
