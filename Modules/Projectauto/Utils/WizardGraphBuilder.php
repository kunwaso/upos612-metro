<?php

namespace Modules\Projectauto\Utils;

use Modules\Projectauto\Workflow\Nodes\Logic\IfElseNode;

class WizardGraphBuilder
{
    public function build(array $payload): array
    {
        $triggerNodeId = 'trigger_1';
        $conditionNodeId = 'condition_1';

        $graph = [
            'version' => 1,
            'nodes' => [
                [
                    'id' => $triggerNodeId,
                    'type' => $payload['trigger_type'],
                    'family' => 'trigger',
                    'label' => $payload['trigger_type'],
                    'config' => (array) ($payload['trigger_config'] ?? []),
                ],
            ],
            'edges' => [],
        ];

        $actionStartSource = $triggerNodeId;
        $actionStartPort = 'next';

        if (! empty($payload['condition'])) {
            $graph['nodes'][] = [
                'id' => $conditionNodeId,
                'type' => 'logic.if_else',
                'family' => 'logic',
                'label' => 'If / Else',
                'config' => [
                    'condition' => IfElseNode::compileConditionExpression($payload['condition']),
                    'condition_spec' => $payload['condition'],
                ],
            ];

            $graph['edges'][] = [
                'id' => 'edge_trigger_condition',
                'source' => $triggerNodeId,
                'source_port' => 'next',
                'target' => $conditionNodeId,
                'target_port' => 'input',
            ];

            $actionStartSource = $conditionNodeId;
            $actionStartPort = 'true';
        }

        foreach ((array) ($payload['actions'] ?? []) as $index => $action) {
            $nodeId = 'action_' . ($index + 1);
            $graph['nodes'][] = [
                'id' => $nodeId,
                'type' => $action['type'],
                'family' => 'action',
                'label' => $action['type'],
                'config' => (array) ($action['config'] ?? []),
            ];

            $graph['edges'][] = [
                'id' => 'edge_' . $actionStartSource . '_' . $nodeId,
                'source' => $actionStartSource,
                'source_port' => $actionStartPort,
                'target' => $nodeId,
                'target_port' => 'input',
            ];
        }

        return $graph;
    }
}
