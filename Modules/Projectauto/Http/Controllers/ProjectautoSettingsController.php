<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Projectauto\Entities\ProjectautoRule;
use Modules\Projectauto\Http\Requests\StoreProjectautoRuleRequest;
use Modules\Projectauto\Http\Requests\UpdateProjectautoRuleRequest;

class ProjectautoSettingsController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $rules = ProjectautoRule::forBusiness($businessId)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('projectauto::settings.index')->with(compact('rules'));
    }

    public function create()
    {
        $rule = new ProjectautoRule();

        return view('projectauto::settings.form')->with(compact('rule'));
    }

    public function store(StoreProjectautoRuleRequest $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        try {
            ProjectautoRule::create([
                'business_id' => $businessId,
                'name' => $request->input('name'),
                'trigger_type' => $request->input('trigger_type'),
                'task_type' => $request->input('task_type'),
                'priority' => (int) $request->input('priority', 100),
                'is_active' => (bool) $request->boolean('is_active', true),
                'stop_on_match' => (bool) $request->boolean('stop_on_match', false),
                'conditions' => $this->decodeJsonInput($request->input('conditions')),
                'payload_template' => $this->decodeJsonInput($request->input('payload_template')),
                'created_by' => (int) $request->user()->id,
                'updated_by' => (int) $request->user()->id,
            ]);

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.rule_created_successfully'),
            ];

            return redirect()->route('projectauto.settings.index')->with('status', $output);
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => $exception->getMessage(),
            ]);
        }
    }

    public function edit(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rule = ProjectautoRule::forBusiness($businessId)->findOrFail($id);

        return view('projectauto::settings.form')->with(compact('rule'));
    }

    public function update(UpdateProjectautoRuleRequest $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rule = ProjectautoRule::forBusiness($businessId)->findOrFail($id);

        try {
            $rule->update([
                'name' => $request->input('name'),
                'trigger_type' => $request->input('trigger_type'),
                'task_type' => $request->input('task_type'),
                'priority' => (int) $request->input('priority', 100),
                'is_active' => (bool) $request->boolean('is_active', true),
                'stop_on_match' => (bool) $request->boolean('stop_on_match', false),
                'conditions' => $this->decodeJsonInput($request->input('conditions')),
                'payload_template' => $this->decodeJsonInput($request->input('payload_template')),
                'updated_by' => (int) $request->user()->id,
            ]);

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.rule_updated_successfully'),
            ];

            return redirect()->route('projectauto.settings.index')->with('status', $output);
        } catch (\Throwable $exception) {
            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => $exception->getMessage(),
            ]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        try {
            $rule = ProjectautoRule::forBusiness($businessId)->findOrFail($id);
            $rule->delete();

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.rule_deleted_successfully'),
            ];
        } catch (\Throwable $exception) {
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->route('projectauto.settings.index')->with('status', $output);
    }

    protected function decodeJsonInput(?string $value)
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(__('projectauto::lang.invalid_json')); 
        }

        return $decoded;
    }
}
