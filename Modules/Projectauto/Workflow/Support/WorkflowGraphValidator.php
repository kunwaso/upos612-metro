<?php

namespace Modules\Projectauto\Workflow\Support;

use Illuminate\Validation\ValidationException;

class WorkflowGraphValidator
{
    protected PredefinedRuleCatalog $catalog;

    protected NodeConfigValidator $configValidator;

    protected ConditionSpecificationValidator $conditionValidator;

    public function __construct(
        PredefinedRuleCatalog $catalog,
        NodeConfigValidator $configValidator,
        ConditionSpecificationValidator $conditionValidator
    ) {
        $this->catalog = $catalog;
        $this->configValidator = $configValidator;
        $this->conditionValidator = $conditionValidator;
    }

    public function validate(array $graph, bool $strict = false): array
    {
        $errors = [];
        $nodes = collect($graph['nodes'] ?? [])->values();
        $edges = collect($graph['edges'] ?? [])->values();
        $catalog = $this->catalog->catalog();
        $allowedTypes = array_merge(
            array_keys($catalog['triggers']),
            array_keys($catalog['logic']),
            array_keys($catalog['actions'])
        );

        $nodeMap = [];
        $nodeIndexes = [];
        foreach ($nodes as $index => $node) {
            $id = $node['id'] ?? null;
            $type = $node['type'] ?? null;

            if (empty($id)) {
                $errors["nodes.$index.id"][] = __('validation.required', ['attribute' => 'node id']);
                continue;
            }

            if (isset($nodeMap[$id])) {
                $errors["nodes.$index.id"][] = __('projectauto::lang.workflow_duplicate_node_id');
                continue;
            }

            if (! in_array($type, $allowedTypes, true)) {
                $errors["nodes.$index.type"][] = __('projectauto::lang.workflow_invalid_node_type');
                continue;
            }

            $definition = $catalog['triggers'][$type] ?? $catalog['logic'][$type] ?? $catalog['actions'][$type] ?? null;
            $nodeMap[$id] = $node + ['definition' => $definition];
            $nodeIndexes[$id] = $index;

            $configErrors = $this->configValidator->validate($definition['config_schema'] ?? [], (array) ($node['config'] ?? []), "nodes.$index.config");
            if (! empty($configErrors)) {
                $errors = array_merge_recursive($errors, $configErrors);
            }

            if ($type === 'logic.if_else') {
                $conditionErrors = $this->conditionValidator->validate($node['config']['condition_spec'] ?? null, $this->inferTriggerType($nodes->all()), "nodes.$index.config.condition_spec");
                if (! empty($conditionErrors)) {
                    $errors = array_merge_recursive($errors, $conditionErrors);
                }
            }
        }

        $triggerCount = collect($nodeMap)->filter(function ($node) {
            return ($node['definition']['family'] ?? null) === 'trigger';
        })->count();
        if ($triggerCount === 0 && $strict) {
            $errors['graph'][] = __('projectauto::lang.workflow_requires_trigger');
        }

        if ($triggerCount > 1) {
            $errors['graph'][] = __('projectauto::lang.workflow_single_trigger_only');
        }

        if (collect($nodeMap)->filter(function ($node) {
            return ($node['definition']['type'] ?? null) === 'logic.if_else';
        })->count() > 1) {
            $errors['graph'][] = __('projectauto::lang.workflow_single_condition_only');
        }

        $edgeKeys = [];
        $incomingEdges = [];
        $outgoingEdges = [];
        foreach ($edges as $index => $edge) {
            $source = $edge['source'] ?? null;
            $target = $edge['target'] ?? null;
            $sourcePort = $edge['source_port'] ?? 'next';
            $targetPort = $edge['target_port'] ?? 'input';

            if (! isset($nodeMap[$source], $nodeMap[$target])) {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_edge_reference');
                continue;
            }

            if ($source === $target) {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_self_links_not_allowed');
            }

            $edgeKey = implode(':', [$source, $sourcePort, $target, $targetPort]);
            if (isset($edgeKeys[$edgeKey])) {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_duplicate_edge');
            }
            $edgeKeys[$edgeKey] = true;
            $incomingEdges[$target][] = $edge + ['index' => $index];
            $outgoingEdges[$source][] = $edge + ['index' => $index];

            $sourceFamily = $nodeMap[$source]['definition']['family'];
            $targetFamily = $nodeMap[$target]['definition']['family'];

            if ($sourceFamily === 'action') {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_action_cannot_link_out');
            }

            if ($targetFamily === 'trigger') {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_trigger_cannot_have_input');
            }

            if ($sourceFamily === 'trigger' && ! in_array($targetFamily, ['logic', 'action'], true)) {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_trigger_target');
            }

            if ($sourceFamily === 'logic' && $targetFamily !== 'action') {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_condition_target');
            }

            if ($sourceFamily === 'logic' && ! in_array($sourcePort, ['true', 'false'], true)) {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_condition_branch');
            }

            if ($sourceFamily === 'trigger' && $sourcePort !== 'next') {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_trigger_port');
            }

            if ($targetPort !== 'input') {
                $errors["edges.$index"][] = __('projectauto::lang.workflow_invalid_target_port');
            }
        }

        $triggerNodes = collect($nodeMap)->filter(function ($node) {
            return ($node['definition']['family'] ?? null) === 'trigger';
        })->values();
        $conditionNodes = collect($nodeMap)->filter(function ($node) {
            return ($node['definition']['type'] ?? null) === 'logic.if_else';
        })->values();
        $actionNodes = collect($nodeMap)->filter(function ($node) {
            return ($node['definition']['family'] ?? null) === 'action';
        })->values();

        if ($conditionNodes->count() === 1) {
            $conditionNode = $conditionNodes->first();
            $conditionIncoming = $incomingEdges[$conditionNode['id']] ?? [];
            $triggerId = $triggerNodes->first()['id'] ?? null;

            if (count($conditionIncoming) !== 1) {
                $errors['graph'][] = __('projectauto::lang.workflow_condition_requires_trigger_link');
            } else {
                $incomingEdge = $conditionIncoming[0];
                if (($incomingEdge['source'] ?? null) !== $triggerId || ($incomingEdge['source_port'] ?? 'next') !== 'next') {
                    $errors['graph'][] = __('projectauto::lang.workflow_condition_requires_trigger_link');
                }
            }

            foreach (($outgoingEdges[$triggerId] ?? []) as $edge) {
                $targetNode = $nodeMap[$edge['target']] ?? null;
                if (($targetNode['definition']['family'] ?? null) === 'action') {
                    $errors['graph'][] = __('projectauto::lang.workflow_condition_disallows_direct_actions');
                    break;
                }
            }
        }

        foreach ($actionNodes as $actionNode) {
            $incoming = $incomingEdges[$actionNode['id']] ?? [];

            if (count($incoming) > 1) {
                $errors['graph'][] = __('projectauto::lang.workflow_action_requires_single_input');
                break;
            }
        }

        if ($strict && empty($errors)) {
            $actionCount = $actionNodes->count();

            if ($actionCount === 0) {
                $errors['graph'][] = __('projectauto::lang.workflow_requires_action');
            }

            if (! $this->hasValidExecutablePath($nodes->all(), $edges->all())) {
                $errors['graph'][] = __('projectauto::lang.workflow_invalid_topology');
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $graph;
    }

    protected function inferTriggerType(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if (($node['definition']['family'] ?? $node['family'] ?? null) === 'trigger') {
                return $node['type'] ?? null;
            }
        }

        return null;
    }

    protected function hasValidExecutablePath(array $nodes, array $edges): bool
    {
        $triggerIds = collect($nodes)->filter(function ($node) {
            return (($node['definition']['family'] ?? null) ?: ($node['family'] ?? null)) === 'trigger';
        })->pluck('id')->all();

        if (count($triggerIds) !== 1) {
            return false;
        }

        $adjacency = [];
        foreach ($edges as $edge) {
            $adjacency[$edge['source']][] = $edge['target'];
        }

        $stack = $triggerIds;
        $visited = [];
        while (! empty($stack)) {
            $current = array_pop($stack);
            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;
            foreach ($adjacency[$current] ?? [] as $target) {
                $stack[] = $target;
            }
        }

        return collect($nodes)->contains(function ($node) use ($visited) {
            $family = ($node['definition']['family'] ?? null) ?: ($node['family'] ?? null);

            return $family === 'action' && isset($visited[$node['id'] ?? '']);
        });
    }
}
