<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Projectauto\Entities\ProjectautoPendingTask;
use Modules\Projectauto\Http\Requests\ModifyAcceptProjectautoTaskRequest;
use Modules\Projectauto\Http\Requests\RejectProjectautoTaskRequest;
use Modules\Projectauto\Utils\ProjectautoUtil;

class ProjectautoTaskController extends Controller
{
    protected ProjectautoUtil $projectautoUtil;

    public function __construct(ProjectautoUtil $projectautoUtil)
    {
        $this->projectautoUtil = $projectautoUtil;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $tasks = ProjectautoPendingTask::forBusiness($businessId)
            ->orderByRaw("FIELD(status, 'pending', 'failed', 'rejected', 'approved')")
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('projectauto::tasks.index')->with(compact('tasks'));
    }

    public function show(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $task = $this->projectautoUtil->getTaskForBusinessOrFail($businessId, $id);

        return view('projectauto::tasks.show')->with(compact('task'));
    }

    public function accept(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $task = $this->projectautoUtil->getTaskForBusinessOrFail($businessId, $id);

        try {
            $this->projectautoUtil->acceptTask($task, $request->user());

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.task_approved_successfully'),
            ];
        } catch (\Throwable $exception) {
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->route('projectauto.tasks.show', ['id' => $id])->with('status', $output);
    }

    public function reject(RejectProjectautoTaskRequest $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $task = $this->projectautoUtil->getTaskForBusinessOrFail($businessId, $id);

        try {
            $this->projectautoUtil->rejectTask($task, (int) $request->user()->id, (string) $request->input('rejection_notes'));

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.task_rejected_successfully'),
            ];
        } catch (\Throwable $exception) {
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->route('projectauto.tasks.show', ['id' => $id])->with('status', $output);
    }

    public function modifyAccept(ModifyAcceptProjectautoTaskRequest $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $task = $this->projectautoUtil->getTaskForBusinessOrFail($businessId, $id);

        $payloadInput = $request->input('payload');
        $payload = is_array($payloadInput) ? $payloadInput : json_decode((string) $payloadInput, true);

        if (! is_array($payload)) {
            return redirect()->route('projectauto.tasks.show', ['id' => $id])->with('status', [
                'success' => false,
                'msg' => __('projectauto::lang.invalid_payload_json'),
            ]);
        }

        try {
            $this->projectautoUtil->acceptTask($task, $request->user(), $payload, $request->input('notes'));

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.task_modified_and_approved_successfully'),
            ];
        } catch (\Throwable $exception) {
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->route('projectauto.tasks.show', ['id' => $id])->with('status', $output);
    }
}
