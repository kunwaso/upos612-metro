<?php

namespace Modules\Projectauto\Workflow\Support;

class NodeConfigValidator
{
    public function validate(array $schema, array $config, string $prefix = 'config'): array
    {
        $errors = [];

        foreach ($schema as $field) {
            $key = $field['key'];
            $path = $prefix . '.' . $key;
            $value = $config[$key] ?? null;
            $required = (bool) ($field['required'] ?? false);
            $type = $field['type'] ?? 'text';

            if ($required && $this->isEmpty($value, $type)) {
                $errors[$path][] = __('validation.required', ['attribute' => $field['label'] ?? $key]);
                continue;
            }

            if ($this->isEmpty($value, $type)) {
                continue;
            }

            $fieldErrors = $this->validateValue($field, $value, $path);
            if (! empty($fieldErrors)) {
                $errors = array_merge_recursive($errors, $fieldErrors);
            }
        }

        return $errors;
    }

    protected function validateValue(array $field, $value, string $path): array
    {
        $type = $field['type'] ?? 'text';
        $errors = [];
        $isFiniteChoiceField = $this->isFiniteChoiceField($field);

        if (in_array($type, ['text', 'textarea', 'date'], true) && ! is_string($value)) {
            $errors[$path][] = __('validation.string', ['attribute' => $field['label'] ?? $field['key']]);
        }

        if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $errors[$path][] = __('validation.integer', ['attribute' => $field['label'] ?? $field['key']]);
        }

        if ($type === 'number' && ! is_numeric($value)) {
            $errors[$path][] = __('validation.numeric', ['attribute' => $field['label'] ?? $field['key']]);
        }

        if ($type === 'boolean' && ! is_bool($value) && ! in_array($value, [0, 1, '0', '1'], true)) {
            $errors[$path][] = __('validation.boolean', ['attribute' => $field['label'] ?? $field['key']]);
        }

        if ($type === 'condition_spec' && ! is_array($value)) {
            $errors[$path][] = __('validation.array', ['attribute' => $field['label'] ?? $field['key']]);
        }

        if ($type === 'select' || $isFiniteChoiceField) {
            $allowed = $this->allowedValues($field);
            if (! in_array($value, $allowed, true)) {
                $errors[$path][] = __('validation.in', ['attribute' => $field['label'] ?? $field['key']]);
            }
        }

        if ($type === 'repeater') {
            if (! is_array($value)) {
                $errors[$path][] = __('validation.array', ['attribute' => $field['label'] ?? $field['key']]);

                return $errors;
            }

            $minItems = (int) ($field['min_items'] ?? 0);
            if ($minItems > 0 && count($value) < $minItems) {
                $errors[$path][] = __('validation.min.array', ['attribute' => $field['label'] ?? $field['key'], 'min' => $minItems]);
            }

            foreach ($value as $index => $row) {
                if (! is_array($row)) {
                    $errors[$path . '.' . $index][] = __('validation.array', ['attribute' => $field['label'] ?? $field['key']]);
                    continue;
                }

                $rowErrors = $this->validate($field['children'] ?? [], $row, $path . '.' . $index);
                if (! empty($rowErrors)) {
                    $errors = array_merge_recursive($errors, $rowErrors);
                }
            }
        }

        return $errors;
    }

    protected function isFiniteChoiceField(array $field): bool
    {
        return array_key_exists('options', $field) || array_key_exists('enum', $field);
    }

    protected function allowedValues(array $field): array
    {
        if (array_key_exists('options', $field)) {
            return collect($field['options'] ?? [])->pluck('value')->all();
        }

        return array_values($field['enum'] ?? []);
    }

    protected function isEmpty($value, string $type): bool
    {
        if ($type === 'boolean') {
            return $value === null;
        }

        if ($type === 'repeater') {
            return $value === null;
        }

        return $value === null || $value === '';
    }
}
