<?php

namespace Modules\Projectauto\Workflow\Support;

use Modules\Projectauto\Workflow\Nodes\Logic\IfElseNode;

class WorkflowRuleCompiler
{
    public function compile(array $workflow, array $graph): array
    {
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');
        $edges = collect($graph['edges'] ?? []);
        $triggerNode = $nodes->first(function ($node) {
            return ($node['family'] ?? null) === 'trigger';
        });

        if (empty($triggerNode)) {
            return [];
        }

        $conditionNode = $nodes->first(function ($node) {
            return ($node['type'] ?? null) === 'logic.if_else';
        });

        $compiled = [];

        if (! empty($conditionNode)) {
            foreach (['true', 'false'] as $branch) {
                $actionEdges = $edges->filter(function ($edge) use ($conditionNode, $branch) {
                    return ($edge['source'] ?? null) === ($conditionNode['id'] ?? null)
                        && ($edge['source_port'] ?? 'true') === $branch;
                })->values();

                foreach ($actionEdges as $index => $edge) {
                    $actionNode = $nodes->get($edge['target']);
                    if (empty($actionNode)) {
                        continue;
                    }

                    $compiled[] = $this->compileRule(
                        $workflow,
                        $triggerNode,
                        $actionNode,
                        $branch,
                        $conditionNode['config']['condition_spec'] ?? null,
                        $index
                    );
                }
            }
        } else {
            $actionEdges = $edges->filter(function ($edge) use ($triggerNode) {
                return ($edge['source'] ?? null) === ($triggerNode['id'] ?? null);
            })->values();

            foreach ($actionEdges as $index => $edge) {
                $actionNode = $nodes->get($edge['target']);
                if (empty($actionNode)) {
                    continue;
                }

                $compiled[] = $this->compileRule($workflow, $triggerNode, $actionNode, 'direct', null, $index);
            }
        }

        return array_values(array_filter($compiled));
    }

    protected function compileRule(
        array $workflow,
        array $triggerNode,
        array $actionNode,
        string $branch,
        ?array $conditionSpec,
        int $sequence
    ): array {
        $conditions = null;

        if (! empty($conditionSpec)) {
            $conditionSpec['negate'] = $branch === 'false';
            $conditions = [
                'mode' => 'all',
                'items' => [$conditionSpec],
                'expression' => IfElseNode::compileConditionExpression($conditionSpec),
            ];
        }

        return [
            'name' => sprintf('%s - %s', $workflow['name'], $actionNode['label'] ?? $actionNode['type']),
            'trigger_type' => $triggerNode['type'],
            'task_type' => $actionNode['type'],
            'priority' => 100 + $sequence,
            'conditions' => $conditions,
            'payload_template' => $this->normalizePayloadTemplate((array) ($actionNode['config'] ?? [])),
            'workflow_node_id' => $actionNode['id'] ?? null,
            'workflow_branch' => $branch,
        ];
    }

    protected function normalizePayloadTemplate(array $config): array
    {
        if (array_key_exists('product_locations', $config) && is_array($config['product_locations'])) {
            $config['product_locations'] = array_values(array_filter(array_map(function ($row) {
                return $row['value'] ?? null;
            }, $config['product_locations'])));
        }

        return $config;
    }
}
