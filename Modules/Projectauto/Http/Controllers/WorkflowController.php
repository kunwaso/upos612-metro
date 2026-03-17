<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Projectauto\Entities\ProjectautoWorkflow;
use Modules\Projectauto\Http\Requests\Workflow\CreateWorkflowRequest;
use Modules\Projectauto\Workflow\Support\WorkflowDefinitionResolver;

class WorkflowController extends Controller
{
    public function index(Request $request, WorkflowDefinitionResolver $definitionResolver)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $workflows = ProjectautoWorkflow::forBusiness($businessId)
            ->orderByDesc('id')
            ->paginate(20);

        return view('projectauto::workflows.index')->with([
            'workflows' => $workflows,
            'definitions' => $definitionResolver->resolve(),
            'pageConfig' => [
                'api' => [
                    'fromWizard' => route('projectauto.api.workflows.from_wizard'),
                ],
            ],
        ]);
    }

    public function store(CreateWorkflowRequest $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $workflow = ProjectautoWorkflow::create([
            'business_id' => $businessId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => (bool) $request->boolean('is_active', true),
            'draft_graph' => ['version' => 1, 'nodes' => [], 'edges' => []],
            'created_by' => (int) $request->user()->id,
            'updated_by' => (int) $request->user()->id,
        ]);

        return redirect()->route('projectauto.workflows.build', ['id' => $workflow->id])
            ->with('status', ['success' => true, 'msg' => __('projectauto::lang.workflow_created_successfully')]);
    }

    public function build(Request $request, int $id, WorkflowDefinitionResolver $definitionResolver)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $workflow = ProjectautoWorkflow::forBusiness($businessId)->findOrFail($id);

        return view('projectauto::workflows.build')->with([
            'workflow' => $workflow,
            'definitions' => $definitionResolver->resolve(),
            'builderAssets' => $this->resolveBuilderAssets(),
            'pageConfig' => [
                'api' => [
                    'saveDraft' => route('projectauto.api.workflows.update_draft', ['id' => $workflow->id]),
                    'validateDraft' => route('projectauto.api.workflows.validate_draft', ['id' => $workflow->id]),
                    'publish' => route('projectauto.api.workflows.publish', ['id' => $workflow->id]),
                ],
            ],
        ]);
    }

    protected function resolveBuilderAssets(): array
    {
        $manifestPath = public_path('build-projectauto/manifest.json');
        if (! file_exists($manifestPath)) {
            return ['js' => null, 'css' => []];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $entry = $manifest['Resources/assets/workflow-builder/src/main.js'] ?? null;
        if (empty($entry)) {
            return ['js' => null, 'css' => []];
        }

        $css = array_map(function ($item) {
            return asset('build-projectauto/' . ltrim($item, '/'));
        }, $entry['css'] ?? []);

        return [
            'js' => asset('build-projectauto/' . ltrim($entry['file'] ?? '', '/')),
            'css' => $css,
        ];
    }
}
