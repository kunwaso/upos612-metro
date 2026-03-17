<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ProjectX\Http\Requests\Chat\DeleteChatMemoryFactRequest;
use Modules\ProjectX\Http\Requests\Chat\SaveChatCredentialRequest;
use Modules\ProjectX\Http\Requests\Chat\StoreChatMemoryFactRequest;
use Modules\ProjectX\Http\Requests\Chat\UpdateChatMemoryFactRequest;
use Modules\ProjectX\Http\Requests\Chat\UpdateChatBusinessSettingsRequest;
use Modules\ProjectX\Entities\ChatMemory;
use Modules\ProjectX\Utils\ChatUtil;

class ChatSettingsController extends Controller
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $credentialStatuses = $this->chatUtil->getCredentialStatuses($business_id, $user_id);
        $businessSettings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $aiChatConfig = $this->chatUtil->buildClientConfig($business_id, $user_id);
        $memoryFacts = $this->chatUtil->listMemoryFactsForBusiness($business_id, $user_id);

        return view('projectx::chat.settings', compact('credentialStatuses', 'businessSettings', 'aiChatConfig', 'memoryFacts'));
    }

    public function storeCredential(SaveChatCredentialRequest $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $validated = $request->validated();
        $scope = (string) $validated['scope'];
        if ($scope === 'business' && ! auth()->user()->can('projectx.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $credential = $this->chatUtil->saveCredential(
                $business_id,
                $user_id,
                $scope,
                (string) $validated['provider'],
                (string) $validated['api_key']
            );

            $this->chatUtil->audit(
                $business_id,
                $user_id,
                'credential_saved',
                null,
                (string) $validated['provider'],
                null,
                ['scope' => $scope, 'credential_id' => $credential->id]
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('projectx::lang.chat_credential_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.chat_credential_saved')]);
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

    public function updateBusiness(UpdateChatBusinessSettingsRequest $request)
    {
        if (! auth()->user()->can('projectx.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $settings = $this->chatUtil->updateBusinessSettings($business_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'business_settings_updated', null, null, null, [
                'settings_id' => $settings->id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('projectx::lang.chat_business_settings_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.chat_business_settings_saved')]);
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

    public function storeMemory(StoreChatMemoryFactRequest $request)
    {
        if (! auth()->user()->can('projectx.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->createMemoryFact($business_id, $user_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'memory_created', null, null, null, [
                'memory_id' => (int) $memoryFact->id,
                'memory_key' => (string) $memoryFact->memory_key,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('projectx::lang.chat_memory_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.chat_memory_saved')]);
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

    public function updateMemory(UpdateChatMemoryFactRequest $request, int $memory)
    {
        if (! auth()->user()->can('projectx.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->getMemoryFactByIdForBusiness($business_id, $memory);
            if (! $this->userCanManageMemoryFact($memoryFact, $user_id)) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            $memoryFact = $this->chatUtil->updateMemoryFact($business_id, $memory, $user_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'memory_updated', null, null, null, [
                'memory_id' => (int) $memoryFact->id,
                'memory_key' => (string) $memoryFact->memory_key,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('projectx::lang.chat_memory_updated'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.chat_memory_updated')]);
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

    public function destroyMemory(DeleteChatMemoryFactRequest $request, int $memory)
    {
        if (! auth()->user()->can('projectx.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->getMemoryFactByIdForBusiness($business_id, $memory);
            if (! $this->userCanManageMemoryFact($memoryFact, $user_id)) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            $memoryKey = (string) $memoryFact->memory_key;

            $this->chatUtil->deleteMemoryFact($business_id, $memory);

            $this->chatUtil->audit($business_id, $user_id, 'memory_deleted', null, null, null, [
                'memory_id' => $memory,
                'memory_key' => $memoryKey,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('projectx::lang.chat_memory_deleted'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.chat_memory_deleted')]);
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

    protected function userCanManageMemoryFact(ChatMemory $memoryFact, int $user_id): bool
    {
        return (int) $memoryFact->user_id === $user_id;
    }
}
