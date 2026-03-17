<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Projectauto\Entities\ProjectautoWorkflow;
use Modules\Projectauto\Http\Requests\Workflow\CreateWorkflowFromWizardRequest;
use Modules\Projectauto\Http\Requests\Workflow\UpdateWorkflowDraftRequest;
use Modules\Projectauto\Utils\WizardGraphBuilder;
use Modules\Projectauto\Workflow\Support\WorkflowGraphValidator;
use Modules\Projectauto\Workflow\Support\WorkflowPublisher;

class WorkflowApiController extends Controller
{
    public function storeFromWizard(
        CreateWorkflowFromWizardRequest $request,
        WizardGraphBuilder $graphBuilder,
        WorkflowPublisher $publisher
    ) {
        $businessId = (int) $request->session()->get('user.business_id');

        $workflow = DB::transaction(function () use ($request, $graphBuilder, $publisher, $businessId) {
            $workflow = ProjectautoWorkflow::create([
                'business_id' => $businessId,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => (bool) $request->boolean('is_active', true),
                'created_by' => (int) $request->user()->id,
                'updated_by' => (int) $request->user()->id,
            ]);

            $graph = $graphBuilder->build($request->validated());
            $workflow->draft_graph = $graph;
            $workflow->save();

            return $publisher->publish($workflow, $graph, (int) $request->user()->id);
        });

        return response()->json([
            'success' => true,
            'workflow_id' => $workflow->id,
            'redirect_url' => route('projectauto.workflows.build', ['id' => $workflow->id]),
        ]);
    }

    public function updateDraft(
        UpdateWorkflowDraftRequest $request,
        int $id
    ) {
        $workflow = $this->workflowForRequest($request, $id);
        $workflow->draft_graph = $request->input('graph');
        $workflow->last_validation_errors = null;
        $workflow->updated_by = (int) $request->user()->id;
        $workflow->save();

        return response()->json([
            'success' => true,
            'message' => __('projectauto::lang.workflow_draft_saved'),
        ]);
    }

    public function validateDraft(Request $request, int $id, WorkflowGraphValidator $validator)
    {
        $workflow = $this->workflowForRequest($request, $id);
        $graph = (array) $request->input('graph', $workflow->draft_graph ?? []);

        $validator->validate($graph, (bool) $request->boolean('strict', false));

        return response()->json([
            'success' => true,
            'message' => __('projectauto::lang.workflow_draft_valid'),
        ]);
    }

    public function publish(Request $request, int $id, WorkflowPublisher $publisher)
    {
        $workflow = $this->workflowForRequest($request, $id);
        $graph = (array) $request->input('graph', $workflow->draft_graph ?? []);

        if (! empty($graph)) {
            $workflow->draft_graph = $graph;
            $workflow->save();
        }

        $workflow = $publisher->publish($workflow, (array) $workflow->draft_graph, (int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => __('projectauto::lang.workflow_published_successfully'),
            'workflow_id' => $workflow->id,
        ]);
    }

    protected function workflowForRequest(Request $request, int $id): ProjectautoWorkflow
    {
        $businessId = (int) $request->session()->get('user.business_id');

        return ProjectautoWorkflow::forBusiness($businessId)->findOrFail($id);
    }
}
