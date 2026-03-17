<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Projectauto\Exceptions\IdempotencyConflictException;
use Modules\Projectauto\Http\Requests\CreateProjectautoTaskRequest;
use Modules\Projectauto\Utils\ProjectautoUtil;

class ProjectautoApiTaskController extends Controller
{
    protected ProjectautoUtil $projectautoUtil;

    public function __construct(ProjectautoUtil $projectautoUtil)
    {
        $this->projectautoUtil = $projectautoUtil;
    }

    public function store(CreateProjectautoTaskRequest $request): JsonResponse
    {
        $user = $request->user();
        $businessId = (int) $user->business_id;

        try {
            $task = $this->projectautoUtil->createTaskFromApiRequest(
                $businessId,
                (string) $request->input('type'),
                (array) $request->input('payload', []),
                $request->input('notes'),
                $request->input('idempotency_key'),
                (int) $user->id
            );

            return response()->json([
                'id' => (int) $task->id,
                'status' => (string) $task->status,
                'show_url' => route('projectauto.tasks.show', ['id' => $task->id]),
            ], 201);
        } catch (IdempotencyConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }
}
