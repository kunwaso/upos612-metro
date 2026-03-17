<?php

namespace Modules\Projectauto\Workflow\Nodes\Logic;

use Modules\Projectauto\Workflow\Nodes\AbstractWorkflowNode;

class IfElseNode extends AbstractWorkflowNode
{
    public function definition(): array
    {
        return [
            'type' => 'logic.if_else',
            'family' => 'logic',
            'label' => __('projectauto::lang.if_else_condition'),
            'description' => __('projectauto::lang.logic_if_else_description'),
            'config_schema' => [
                [
                    'key' => 'condition_spec',
                    'label' => __('projectauto::lang.condition'),
                    'type' => 'condition_spec',
                    'required' => true,
                ],
            ],
            'ports' => [
                'inputs' => ['input'],
                'outputs' => ['true', 'false'],
            ],
        ];
    }

    public static function compileConditionExpression(array $conditionSpec): string
    {
        $operatorMap = [
            'equals' => '==',
            'not_equals' => '!=',
            'greater_than' => '>',
            'less_than' => '<',
            'contains' => 'contains',
            'in' => 'in',
            'not_in' => 'not in',
        ];

        $operator = $operatorMap[$conditionSpec['operator'] ?? 'equals'] ?? '==';
        $value = $conditionSpec['value'] ?? null;

        if (is_bool($value)) {
            $valueLiteral = $value ? 'true' : 'false';
        } elseif (is_numeric($value)) {
            $valueLiteral = (string) $value;
        } elseif (is_array($value)) {
            $valueLiteral = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        } else {
            $valueLiteral = '"' . addslashes((string) $value) . '"';
        }

        return sprintf('%s %s %s', $conditionSpec['field'] ?? 'value', $operator, $valueLiteral);
    }
}
