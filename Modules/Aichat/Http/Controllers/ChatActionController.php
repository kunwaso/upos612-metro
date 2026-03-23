<?php

namespace Modules\Aichat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\Aichat\Http\Requests\Chat\CancelChatActionRequest;
use Modules\Aichat\Http\Requests\Chat\ConfirmChatActionRequest;
use Modules\Aichat\Http\Requests\Chat\PrepareChatActionRequest;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatUtil;

class ChatActionController extends Controller
{
    protected ChatUtil $chatUtil;

    protected ChatActionUtil $chatActionUtil;

    public function __construct(ChatUtil $chatUtil, ChatActionUtil $chatActionUtil)
    {
        $this->chatUtil = $chatUtil;
        $this->chatActionUtil = $chatActionUtil;
    }

    public function prepare(PrepareChatActionRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }
        if (! $this->areActionsEnabled()) {
            return $this->actionsDisabledJsonResponse();
        }

        $validated = (array) $request->validated();

        try {
            $action = $this->chatActionUtil->prepareAction(
                $business_id,
                $user_id,
                $id,
                $validated,
                (string) ($validated['channel'] ?? 'web')
            );

            return response()->json([
                'success' => true,
                'message' => __('aichat::lang.chat_action_prepared'),
                'data' => $this->chatActionUtil->serializePendingAction($action),
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => __('aichat::lang.chat_conversation_not_found'),
            ], 404);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'invalid_action',
                'message' => (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_invalid_payload')),
            ], 422);
        } catch (\RuntimeException $exception) {
            if ($this->isForbiddenMessage((string) $exception->getMessage())) {
                return $this->forbiddenResponse($request, $business_id, $id, null, (string) $exception->getMessage(), $validated);
            }

            return response()->json([
                'success' => false,
                'code' => 'action_error',
                'message' => (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_failed')),
            ], 422);
        }
    }

    public function confirm(ConfirmChatActionRequest $request, string $id, int $actionId)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }
        if (! $this->areActionsEnabled()) {
            return $this->actionsDisabledJsonResponse();
        }

        $validated = (array) $request->validated();

        try {
            $action = $this->chatActionUtil->confirmAction(
                $business_id,
                $user_id,
                $id,
                $actionId,
                'web',
                $validated['confirm_note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => __('aichat::lang.chat_action_executed'),
                'data' => $this->chatActionUtil->serializePendingAction($action),
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => __('aichat::lang.chat_action_not_found'),
            ], 404);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'invalid_action',
                'message' => (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_invalid_payload')),
            ], 422);
        } catch (\RuntimeException $exception) {
            if ($this->isForbiddenMessage((string) $exception->getMessage())) {
                return $this->forbiddenResponse($request, $business_id, $id, $actionId, (string) $exception->getMessage(), $validated);
            }

            return response()->json([
                'success' => false,
                'code' => 'action_error',
                'message' => (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_failed')),
            ], 422);
        }
    }

    public function cancel(CancelChatActionRequest $request, string $id, int $actionId)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }
        if (! $this->areActionsEnabled()) {
            return $this->actionsDisabledJsonResponse();
        }

        $validated = (array) $request->validated();

        try {
            $action = $this->chatActionUtil->cancelAction(
                $business_id,
                $user_id,
                $id,
                $actionId,
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => __('aichat::lang.chat_action_cancelled'),
                'data' => $this->chatActionUtil->serializePendingAction($action),
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => __('aichat::lang.chat_action_not_found'),
            ], 404);
        } catch (\RuntimeException $exception) {
            if ($this->isForbiddenMessage((string) $exception->getMessage())) {
                return $this->forbiddenResponse($request, $business_id, $id, $actionId, (string) $exception->getMessage(), $validated);
            }

            return response()->json([
                'success' => false,
                'code' => 'action_error',
                'message' => (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_failed')),
            ], 422);
        }
    }

    public function pending(Request $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }
        if (! $this->areActionsEnabled()) {
            return $this->actionsDisabledJsonResponse();
        }

        try {
            $capabilities = $this->chatUtil->resolveChatCapabilities($business_id, $user_id);
            $items = $this->chatActionUtil->listPendingActions($business_id, $user_id, $id);

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'catalog' => $this->chatActionUtil->getActionCatalog($capabilities),
                ],
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => __('aichat::lang.chat_conversation_not_found'),
            ], 404);
        }
    }

    protected function chatDisabledJsonResponse()
    {
        return response()->json([
            'success' => false,
            'message' => __('aichat::lang.chat_disabled'),
        ], 403);
    }

    protected function actionsDisabledJsonResponse()
    {
        return response()->json([
            'success' => false,
            'code' => 'feature_disabled',
            'message' => __('aichat::lang.chat_action_disabled'),
        ], 403);
    }

    protected function areActionsEnabled(): bool
    {
        return (bool) config('aichat.actions.enabled', false);
    }

    protected function isForbiddenMessage(string $message): bool
    {
        $message = trim($message);

        return in_array($message, [
            __('aichat::lang.chat_action_forbidden'),
            __('aichat::lang.chat_action_forbidden_own_scope'),
            __('messages.unauthorized_action'),
            __('aichat::lang.unauthorized_action'),
        ], true);
    }

    protected function forbiddenResponse(
        Request $request,
        int $business_id,
        string $conversation_id,
        ?int $action_id,
        string $message,
        ?array $validated = null
    )
    {
        if ($validated === null && method_exists($request, 'validated')) {
            $validated = (array) $request->validated();
        }
        $validated = $validated ?? [];

        $user_id = auth()->id() ? (int) auth()->id() : null;
        if ($business_id > 0) {
            try {
                $this->chatUtil->audit($business_id, $user_id, 'chat_action_denied', $conversation_id, null, null, [
                    'action_id' => $action_id,
                    'module' => (string) ($validated['module'] ?? ''),
                    'action' => (string) ($validated['action'] ?? ''),
                    'reason' => $message ?: __('aichat::lang.chat_action_forbidden'),
                ]);
            } catch (\Throwable $exception) {
                // Avoid blocking user response on audit write failure.
            }
        }

        return response()->json([
            'success' => false,
            'code' => 'forbidden',
            'message' => $message ?: __('aichat::lang.chat_action_forbidden'),
            'error' => [
                'type' => 'forbidden',
                'module' => (string) ($validated['module'] ?? ''),
                'action' => (string) ($validated['action'] ?? ''),
            ],
        ], 403);
    }
}
