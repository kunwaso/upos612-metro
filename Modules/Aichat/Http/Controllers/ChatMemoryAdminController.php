<?php

namespace Modules\Aichat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Aichat\Http\Requests\Chat\UpdatePersistentMemoryNameRequest;
use Modules\Aichat\Http\Requests\Chat\WipeBusinessMemoryRequest;
use Modules\Aichat\Utils\ChatUtil;

class ChatMemoryAdminController extends Controller
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('aichat.manage_all_memories')) {
            abort(403, __('aichat::lang.unauthorized_action'));
        }

        $perPage = max(5, min(50, (int) $request->input('per_page', 10)));
        $persistentMemories = $this->chatUtil->paginatePersistentMemoriesForAdmin($perPage);

        return view('aichat::chat.memories_admin', compact('persistentMemories', 'perPage'));
    }

    public function updateName(UpdatePersistentMemoryNameRequest $request, int $business)
    {
        $userId = (int) auth()->id();

        try {
            $request->validated();
            $targetBusinessId = (int) $business;

            $persistentMemory = $this->chatUtil->updatePersistentMemoryDisplayName(
                $targetBusinessId,
                $request->input('display_name')
            );

            $this->chatUtil->audit($targetBusinessId, $userId, 'persistent_memory_display_name_updated', null, null, null, [
                'target_business_id' => $targetBusinessId,
                'persistent_memory_id' => (int) $persistentMemory->id,
                'display_name' => $persistentMemory->display_name,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_memory_admin_name_updated'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_memory_admin_name_updated')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function wipe(WipeBusinessMemoryRequest $request, int $business)
    {
        $userId = (int) auth()->id();

        try {
            $request->validated();
            $targetBusinessId = (int) $business;

            $deletedCount = $this->chatUtil->wipeBusinessMemory($targetBusinessId);
            $persistentMemory = $this->chatUtil->getOrCreatePersistentMemory($targetBusinessId);

            $this->chatUtil->audit($targetBusinessId, $userId, 'persistent_memory_wiped', null, null, null, [
                'target_business_id' => $targetBusinessId,
                'persistent_memory_id' => (int) $persistentMemory->id,
                'deleted_fact_count' => $deletedCount,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_memory_admin_wipe_success'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_memory_admin_wipe_success')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }
}
