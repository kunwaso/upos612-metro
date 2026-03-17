<?php

namespace Modules\Projectauto\Workflow\Support;

use Modules\Projectauto\Entities\ProjectautoRule;
use Modules\Projectauto\Entities\ProjectautoWorkflow;

class WorkflowPublisher
{
    protected WorkflowGraphValidator $graphValidator;

    protected WorkflowRuleCompiler $compiler;

    public function __construct(WorkflowGraphValidator $graphValidator, WorkflowRuleCompiler $compiler)
    {
        $this->graphValidator = $graphValidator;
        $this->compiler = $compiler;
    }

    public function publish(ProjectautoWorkflow $workflow, array $graph, int $actorId): ProjectautoWorkflow
    {
        $this->graphValidator->validate($graph, true);

        $compiledRules = $this->compiler->compile($workflow->toArray(), $graph);
        $triggerType = collect($graph['nodes'] ?? [])->firstWhere('family', 'trigger')['type'] ?? null;

        ProjectautoRule::where('workflow_id', $workflow->id)->delete();

        foreach ($compiledRules as $rule) {
            ProjectautoRule::create([
                'workflow_id' => $workflow->id,
                'workflow_node_id' => $rule['workflow_node_id'],
                'workflow_branch' => $rule['workflow_branch'],
                'business_id' => (int) $workflow->business_id,
                'name' => $rule['name'],
                'trigger_type' => $rule['trigger_type'],
                'task_type' => $rule['task_type'],
                'priority' => $rule['priority'],
                'is_active' => (bool) $workflow->is_active,
                'stop_on_match' => false,
                'conditions' => $rule['conditions'],
                'payload_template' => $rule['payload_template'],
                'created_by' => $workflow->created_by,
                'updated_by' => $actorId,
            ]);
        }

        $workflow->trigger_type = $triggerType;
        $workflow->published_graph = $graph;
        $workflow->draft_graph = $graph;
        $workflow->last_validation_errors = null;
        $workflow->published_by = $actorId;
        $workflow->published_at = now();
        $workflow->updated_by = $actorId;
        $workflow->save();

        return $workflow->fresh();
    }
}
