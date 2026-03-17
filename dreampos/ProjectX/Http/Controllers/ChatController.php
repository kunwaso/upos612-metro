<?php

namespace Modules\ProjectX\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\ProjectX\Entities\ChatConversation;
use Modules\ProjectX\Entities\ChatMessage;
use Modules\ProjectX\Http\Requests\Chat\ApplyChatFabricUpdatesRequest;
use Modules\ProjectX\Http\Requests\Chat\CreateChatConversationRequest;
use Modules\ProjectX\Http\Requests\Chat\DeleteChatConversationRequest;
use Modules\ProjectX\Http\Requests\Chat\ExportChatConversationRequest;
use Modules\ProjectX\Http\Requests\Chat\ListChatConversationsRequest;
use Modules\ProjectX\Http\Requests\Chat\RegenerateChatMessageRequest;
use Modules\ProjectX\Http\Requests\Chat\SaveChatMessageFeedbackRequest;
use Modules\ProjectX\Http\Requests\Chat\SendChatMessageRequest;
use Modules\ProjectX\Http\Requests\Chat\ShareChatConversationRequest;
use Modules\ProjectX\Utils\AIChatUtil;
use Modules\ProjectX\Utils\ChatUtil;
use Modules\ProjectX\Utils\ChatWorkflowUtil;

class ChatController extends Controller
{
    protected ChatUtil $chatUtil;

    protected AIChatUtil $aiChatUtil;

    protected ChatWorkflowUtil $chatWorkflowUtil;

    public function __construct(ChatUtil $chatUtil, AIChatUtil $aiChatUtil, ChatWorkflowUtil $chatWorkflowUtil)
    {
        $this->chatUtil = $chatUtil;
        $this->aiChatUtil = $aiChatUtil;
        $this->chatWorkflowUtil = $chatWorkflowUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            abort(403, __('projectx::lang.chat_disabled'));
        }

        $user_id = (int) auth()->id();
        $aiChatConfig = $this->chatUtil->buildClientConfig($business_id, $user_id);
        $conversations = $this->chatUtil->listConversationsForUser($business_id, $user_id);

        $activeConversation = null;
        $conversationId = (string) $request->query('conversation', '');
        if ($conversationId !== '') {
            try {
                $activeConversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $conversationId);
            } catch (\Throwable $exception) {
                $activeConversation = null;
            }
        }
        if (! $activeConversation) {
            $activeConversation = $conversations->first() ?: $this->chatUtil->getOrCreateConversation($business_id, $user_id);
        }

        $messages = collect();
        if ($activeConversation) {
            $messageModels = ChatMessage::forBusiness($business_id)
                ->where('conversation_id', $activeConversation->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $latestAssistantMessageId = $this->chatUtil->getLatestAssistantMessageId($business_id, (string) $activeConversation->id);
            $feedbackMap = $this->chatUtil->getFeedbackMapForUser(
                $business_id,
                $user_id,
                $messageModels->pluck('id')->all()
            );

            $messages = $messageModels->map(function (ChatMessage $message) use ($feedbackMap, $latestAssistantMessageId) {
                return $this->chatUtil->serializeMessage(
                    $message,
                    $feedbackMap[(int) $message->id] ?? null,
                    $latestAssistantMessageId
                );
            })->values();
        }

        return view('projectx::chat.index', compact('aiChatConfig', 'conversations', 'activeConversation', 'messages'));
    }

    public function config(Request $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $config = $this->chatUtil->buildClientConfig($business_id, $user_id);

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    public function conversations(ListChatConversationsRequest $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $validated = $request->validated();
        $fabricId = isset($validated['fabric_id']) ? (int) $validated['fabric_id'] : null;
        $includeArchived = array_key_exists('include_archived', $validated)
            ? (bool) $validated['include_archived']
            : false;

        $items = $this->chatUtil->listConversationsForUser($business_id, $user_id, $includeArchived, $fabricId)
            ->map(function (ChatConversation $conversation) {
                return $this->chatUtil->serializeConversation($conversation);
            })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function showConversation(string $id, Request $request)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $messageModels = ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $latestAssistantMessageId = $this->chatUtil->getLatestAssistantMessageId($business_id, (string) $conversation->id);
        $feedbackMap = $this->chatUtil->getFeedbackMapForUser(
            $business_id,
            $user_id,
            $messageModels->pluck('id')->all()
        );

        $messages = $messageModels->map(function (ChatMessage $message) use ($feedbackMap, $latestAssistantMessageId) {
            return $this->chatUtil->serializeMessage(
                $message,
                $feedbackMap[(int) $message->id] ?? null,
                $latestAssistantMessageId
            );
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $this->chatUtil->serializeConversation($conversation),
                'messages' => $messages,
            ],
        ]);
    }

    public function storeConversation(CreateChatConversationRequest $request)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $validated = $request->validated();
        $conversation = $this->chatUtil->createConversation(
            $business_id,
            $user_id,
            trim((string) ($validated['title'] ?? '')) ?: null,
            isset($validated['fabric_id']) ? (int) $validated['fabric_id'] : null
        );

        $this->chatUtil->audit($business_id, $user_id, 'conversation_created', $conversation->id, null, null, [
            'fabric_id' => $conversation->fabric_id ? (int) $conversation->fabric_id : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->chatUtil->serializeConversation($conversation),
        ]);
    }

    public function destroyConversation(DeleteChatConversationRequest $request, string $id)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();

        try {
            $this->chatUtil->deleteConversationForUser($business_id, $user_id, $id);
            $this->chatUtil->audit($business_id, $user_id, 'conversation_deleted', $id);

            return $this->respondSuccess(__('projectx::lang.chat_delete_success'));
        } catch (ModelNotFoundException $exception) {
            return $this->respondWithError(__('projectx::lang.chat_conversation_not_found'));
        } catch (\Throwable $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function send(SendChatMessageRequest $request, string $id)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $payload = $request->validated();
        $workflowContext = $this->chatWorkflowUtil->prepareSendOrStreamContext(
            $business_id,
            $user_id,
            $conversation,
            $payload
        );

        if (! ($workflowContext['success'] ?? false)) {
            $errorType = (string) ($workflowContext['error_type'] ?? '');
            $errorMessage = (string) ($workflowContext['error_message'] ?? __('projectx::lang.chat_provider_error'));

            if ($errorType === 'credential_missing') {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_message' => $this->chatUtil->serializeMessage($workflowContext['error_message_model']),
                    'warnings' => (array) ($workflowContext['warnings'] ?? []),
                ], 422);
            }

            return $this->respondWithError($errorMessage);
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $settings = $workflowContext['settings'];
        $warnings = (array) $workflowContext['warnings'];
        $userMessage = $workflowContext['user_message'];
        $credential = $workflowContext['credential'];
        $messages = (array) $workflowContext['messages'];
        $appliedFabricId = $workflowContext['applied_fabric_id'];
        $appliedFabricInsight = (bool) $workflowContext['applied_fabric_insight'];

        try {
            $assistantText = $this->aiChatUtil->generateText(
                $provider,
                $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
                $model,
                $messages
            );

            $normalizedResponse = $this->chatWorkflowUtil->normalizeAssistantText($assistantText, $settings);
            $assistantText = (string) $normalizedResponse['text'];
            if (! empty($normalizedResponse['moderated'])) {
                $warnings[] = __('projectx::lang.chat_warning_moderation_applied');
            }

            $assistantMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ASSISTANT,
                $assistantText,
                $provider,
                $model,
                $user_id,
                $appliedFabricId,
                $appliedFabricInsight
            );

            $this->chatUtil->audit($business_id, $user_id, 'message_send_success', $conversation->id, $provider, $model, [
                'fabric_insight' => (bool) ($payload['fabric_insight'] ?? false),
                'fabric_id' => (int) ($payload['fabric_id'] ?? 0),
                'applied_fabric_insight' => $appliedFabricInsight,
                'applied_fabric_id' => $appliedFabricId ?: 0,
                'warning_count' => count($warnings),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('lang_v1.success'),
                'data' => [
                    'conversation' => $this->chatUtil->serializeConversation($conversation->fresh()),
                    'user_message' => $this->chatUtil->serializeMessage($userMessage, null, $assistantMessage->id),
                    'assistant_message' => $this->chatUtil->serializeMessage($assistantMessage, null, $assistantMessage->id),
                    'warnings' => array_values(array_unique($warnings)),
                    'suggested_replies' => $this->chatUtil->getSuggestedReplies($settings),
                ],
            ]);
        } catch (\Throwable $exception) {
            $errorText = $exception->getMessage() ?: __('projectx::lang.chat_provider_error');
            $errorMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ERROR,
                $errorText,
                $provider,
                $model,
                $user_id,
                $appliedFabricId,
                $appliedFabricInsight
            );

            $this->chatUtil->audit($business_id, $user_id, 'message_send_error', $conversation->id, $provider, $model, [
                'error' => $errorText,
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorText,
                'error_message' => $this->chatUtil->serializeMessage($errorMessage),
                'warnings' => $warnings,
            ], 422);
        }
    }

    public function stream(SendChatMessageRequest $request, string $id)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $payload = $request->validated();
        $workflowContext = $this->chatWorkflowUtil->prepareSendOrStreamContext(
            $business_id,
            $user_id,
            $conversation,
            $payload
        );

        if (! ($workflowContext['success'] ?? false)) {
            $errorType = (string) ($workflowContext['error_type'] ?? '');
            $errorMessage = (string) ($workflowContext['error_message'] ?? __('projectx::lang.chat_provider_error'));

            if ($errorType === 'credential_missing') {
                return response()->stream(function () use ($workflowContext, $errorMessage) {
                    echo $this->sseEvent('start', [
                        'user_message' => $this->chatUtil->serializeMessage($workflowContext['user_message']),
                    ]);

                    foreach ((array) ($workflowContext['warnings'] ?? []) as $warning) {
                        echo $this->sseEvent('warning', ['message' => $warning]);
                    }

                    echo $this->sseEvent('error', [
                        'message' => $errorMessage,
                        'error_message' => $this->chatUtil->serializeMessage($workflowContext['error_message_model']),
                    ]);
                }, 200, $this->sseHeaders());
            }

            return response()->stream(function () use ($errorMessage) {
                echo $this->sseEvent('error', ['message' => $errorMessage]);
            }, 200, $this->sseHeaders());
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $settings = $workflowContext['settings'];
        $warnings = (array) $workflowContext['warnings'];
        $messages = (array) $workflowContext['messages'];
        $credential = $workflowContext['credential'];
        $userMessage = $workflowContext['user_message'];
        $appliedFabricId = $workflowContext['applied_fabric_id'];
        $appliedFabricInsight = (bool) $workflowContext['applied_fabric_insight'];

        return response()->stream(function () use (
            $business_id,
            $user_id,
            $settings,
            $conversation,
            $provider,
            $model,
            $warnings,
            $credential,
            $userMessage,
            $payload,
            $appliedFabricId,
            $appliedFabricInsight,
            $messages
        ) {
            echo $this->sseEvent('start', ['user_message' => $this->chatUtil->serializeMessage($userMessage)]);
            foreach ($warnings as $warning) {
                echo $this->sseEvent('warning', ['message' => $warning]);
            }

            $buffer = '';
            try {
                foreach ($this->aiChatUtil->streamText(
                    $provider,
                    $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
                    $model,
                    $messages
                ) as $chunk) {
                    $chunk = (string) $chunk;
                    if ($chunk === '') {
                        continue;
                    }

                    $buffer .= $chunk;
                    echo $this->sseEvent('chunk', ['text' => $chunk]);
                    @ob_flush();
                    @flush();
                }

                $normalizedResponse = $this->chatWorkflowUtil->normalizeAssistantText($buffer, $settings);
                $assistantText = (string) $normalizedResponse['text'];
                if (! empty($normalizedResponse['moderated'])) {
                    echo $this->sseEvent('warning', ['message' => __('projectx::lang.chat_warning_moderation_applied')]);
                }

                $assistantMessage = $this->chatUtil->appendMessage(
                    $conversation,
                    ChatMessage::ROLE_ASSISTANT,
                    $assistantText,
                    $provider,
                    $model,
                    $user_id,
                    $appliedFabricId,
                    $appliedFabricInsight
                );

                $this->chatUtil->audit($business_id, $user_id, 'message_stream_success', $conversation->id, $provider, $model, [
                    'response_chars' => mb_strlen($assistantText),
                    'fabric_insight' => (bool) ($payload['fabric_insight'] ?? false),
                    'fabric_id' => (int) ($payload['fabric_id'] ?? 0),
                    'applied_fabric_insight' => $appliedFabricInsight,
                    'applied_fabric_id' => $appliedFabricId ?: 0,
                ]);

                echo $this->sseEvent('done', [
                    'assistant_message' => $this->chatUtil->serializeMessage($assistantMessage, null, $assistantMessage->id),
                    'suggested_replies' => $this->chatUtil->getSuggestedReplies($settings),
                ]);
            } catch (\Throwable $exception) {
                $errorText = $exception->getMessage() ?: __('projectx::lang.chat_provider_error');
                $errorMessage = $this->chatUtil->appendMessage(
                    $conversation,
                    ChatMessage::ROLE_ERROR,
                    $errorText,
                    $provider,
                    $model,
                    $user_id,
                    $appliedFabricId,
                    $appliedFabricInsight
                );

                $this->chatUtil->audit($business_id, $user_id, 'message_stream_error', $conversation->id, $provider, $model, [
                    'error' => $errorText,
                ]);

                echo $this->sseEvent('error', [
                    'message' => $errorText,
                    'error_message' => $this->chatUtil->serializeMessage($errorMessage),
                ]);
            }
        }, 200, $this->sseHeaders());
    }

    public function feedback(SaveChatMessageFeedbackRequest $request, string $message)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $validated = $request->validated();

        $chatMessage = $this->chatUtil->getMessageByIdForUser($business_id, $user_id, (int) $message);
        if ($chatMessage->role !== ChatMessage::ROLE_ASSISTANT) {
            return response()->json([
                'success' => false,
                'message' => __('projectx::lang.chat_feedback_assistant_only'),
            ], 422);
        }

        $saved = $this->chatUtil->saveMessageFeedback(
            $business_id,
            $user_id,
            $chatMessage,
            (string) $validated['feedback'],
            $validated['note'] ?? null
        );

        $this->chatUtil->audit($business_id, $user_id, 'message_feedback_saved', (string) $chatMessage->conversation_id, $chatMessage->provider, $chatMessage->model, [
            'message_id' => (int) $chatMessage->id,
            'feedback' => (string) $saved->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('projectx::lang.chat_feedback_saved'),
            'data' => [
                'message_id' => (int) $chatMessage->id,
                'feedback_value' => (string) $saved->feedback,
            ],
        ]);
    }

    public function regenerate(RegenerateChatMessageRequest $request, string $message)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $payload = $request->validated();

        $assistantMessage = $this->chatUtil->getMessageByIdForUser($business_id, $user_id, (int) $message);
        if ($assistantMessage->role !== ChatMessage::ROLE_ASSISTANT) {
            return $this->sseErrorResponse(__('projectx::lang.chat_feedback_assistant_only'));
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, (string) $assistantMessage->conversation_id);
        $latestAssistantMessageId = $this->chatUtil->getLatestAssistantMessageId($business_id, (string) $conversation->id);
        if ($latestAssistantMessageId === null || (int) $assistantMessage->id !== $latestAssistantMessageId) {
            return $this->sseErrorResponse(__('projectx::lang.chat_regenerate_latest_only'));
        }
        $workflowContext = $this->chatWorkflowUtil->prepareRegenerateContext(
            $business_id,
            $user_id,
            $conversation,
            $assistantMessage,
            $payload
        );

        if (! ($workflowContext['success'] ?? false)) {
            return $this->sseErrorResponse((string) ($workflowContext['error_message'] ?? __('projectx::lang.chat_provider_error')));
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $warnings = (array) $workflowContext['warnings'];
        $messages = (array) $workflowContext['messages'];
        $credential = $workflowContext['credential'];
        $appliedFabricId = $workflowContext['applied_fabric_id'];
        $appliedFabricInsight = (bool) $workflowContext['applied_fabric_insight'];
        $settings = $workflowContext['settings'];

        return response()->stream(function () use (
            $assistantMessage,
            $conversation,
            $business_id,
            $user_id,
            $settings,
            $provider,
            $model,
            $credential,
            $warnings,
            $payload,
            $appliedFabricId,
            $appliedFabricInsight,
            $messages
        ) {
            echo $this->sseEvent('start', ['message_id' => (int) $assistantMessage->id]);
            foreach ($warnings as $warning) {
                echo $this->sseEvent('warning', ['message' => $warning]);
            }

            $buffer = '';
            try {
                foreach ($this->aiChatUtil->streamText(
                    $provider,
                    $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
                    $model,
                    $messages
                ) as $chunk) {
                    $chunk = (string) $chunk;
                    if ($chunk === '') {
                        continue;
                    }

                    $buffer .= $chunk;
                    echo $this->sseEvent('chunk', ['text' => $chunk]);
                    @ob_flush();
                    @flush();
                }

                $normalizedResponse = $this->chatWorkflowUtil->normalizeAssistantText($buffer, $settings);
                $assistantText = (string) $normalizedResponse['text'];
                if (! empty($normalizedResponse['moderated'])) {
                    echo $this->sseEvent('warning', ['message' => __('projectx::lang.chat_warning_moderation_applied')]);
                }

                $updatedAssistantMessage = $this->chatUtil->replaceAssistantMessageContent(
                    $assistantMessage,
                    $assistantText,
                    $provider,
                    $model,
                    $appliedFabricId,
                    $appliedFabricInsight
                );

                $this->chatUtil->audit($business_id, $user_id, 'message_regenerated', (string) $conversation->id, $provider, $model, [
                    'message_id' => (int) $updatedAssistantMessage->id,
                    'response_chars' => mb_strlen($assistantText),
                    'fabric_insight' => (bool) ($payload['fabric_insight'] ?? false),
                    'fabric_id' => (int) ($payload['fabric_id'] ?? 0),
                    'applied_fabric_insight' => $appliedFabricInsight,
                    'applied_fabric_id' => $appliedFabricId ?: 0,
                ]);

                echo $this->sseEvent('done', [
                    'assistant_message' => $this->serializeMessageForUser($business_id, $user_id, $updatedAssistantMessage, (int) $updatedAssistantMessage->id),
                    'suggested_replies' => $this->chatUtil->getSuggestedReplies($settings),
                ]);
            } catch (\Throwable $exception) {
                $errorText = $exception->getMessage() ?: __('projectx::lang.chat_provider_error');
                $this->chatUtil->audit($business_id, $user_id, 'message_regenerate_error', (string) $conversation->id, $provider, $model, [
                    'message_id' => (int) $assistantMessage->id,
                    'error' => $errorText,
                ]);
                echo $this->sseEvent('error', ['message' => $errorText]);
            }
        }, 200, $this->sseHeaders());
    }

    public function applyFabricUpdates(ApplyChatFabricUpdatesRequest $request, int $fabric_id, int $message)
    {
        if (! auth()->user()->can('projectx.chat.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        if (! auth()->user()->can('projectx.fabric.create') && ! auth()->user()->can('product.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $validated = $request->validated();
        $updates = (array) ($validated['updates'] ?? []);

        try {
            DB::beginTransaction();

            $fabric = $this->chatUtil->applyFabricUpdatesFromAssistantMessage(
                $business_id,
                $user_id,
                $fabric_id,
                $message,
                $updates,
                $user_id
            );

            DB::commit();

            $this->chatUtil->audit($business_id, $user_id, 'fabric_updates_applied', null, null, null, [
                'fabric_id' => $fabric->id,
                'message_id' => $message,
                'fields' => array_keys($updates),
            ]);

            $appliedSummary = $this->buildAppliedFabricChangesSummary($updates);

            return $this->respondSuccess(__('projectx::lang.chat_apply_success'), [
                'data' => [
                    'fabric_id' => (int) $fabric->id,
                    'updated_fields' => array_values(array_keys($updates)),
                    'applied_changes' => $updates,
                    'applied_summary' => $appliedSummary,
                ],
            ]);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();

            return $this->respondWithError(__('projectx::lang.chat_apply_not_allowed'));
        } catch (\InvalidArgumentException $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'msg' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            DB::rollBack();
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function share(ShareChatConversationRequest $request, string $id)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $user_id = (int) auth()->id();
        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $ttl = (int) ($request->validated()['ttl_hours'] ?? $settings->share_ttl_hours);
        $shareUrl = $this->chatUtil->createShareUrl($conversation, $ttl);

        $this->chatUtil->audit($business_id, $user_id, 'conversation_share_link_created', $conversation->id, null, null, [
            'ttl_hours' => $ttl,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['url' => $shareUrl],
        ]);
    }

    public function export(ExportChatConversationRequest $request, string $id)
    {
        if (! auth()->user()->can('projectx.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            abort(403, __('projectx::lang.chat_disabled'));
        }

        $user_id = (int) auth()->id();
        $format = (string) ($request->input('format') ?: 'markdown');

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $messages = ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->chatUtil->audit($business_id, $user_id, 'conversation_export', $conversation->id, null, null, [
            'format' => $format,
        ]);

        if ($format === 'pdf') {
            $html = view('projectx::chat.export_pdf', compact('conversation', 'messages'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);
            $fileName = 'chat-conversation-' . $conversation->id . '.pdf';

            return response($mpdf->Output($fileName, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        }

        $markdown = $this->chatUtil->exportConversationAsMarkdown($conversation);
        $fileName = 'chat-conversation-' . $conversation->id . '.md';

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function sharedShow(Request $request, string $conversation)
    {
        $chatConversation = ChatConversation::findOrFail($conversation);
        $messages = ChatMessage::forBusiness((int) $chatConversation->business_id)
            ->where('conversation_id', $chatConversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('projectx::chat.shared', compact('chatConversation', 'messages'));
    }

    protected function serializeMessageForUser(int $business_id, int $user_id, ChatMessage $message, ?int $latestAssistantMessageId = null): array
    {
        $feedbackMap = $this->chatUtil->getFeedbackMapForUser($business_id, $user_id, [(int) $message->id]);

        return $this->chatUtil->serializeMessage(
            $message,
            $feedbackMap[(int) $message->id] ?? null,
            $latestAssistantMessageId
        );
    }

    protected function buildAppliedFabricChangesSummary(array $updates): string
    {
        $writableFieldTypes = (array) config('projectx.chat.fabric_updates.writable_field_types', []);
        $allowedFields = array_keys($writableFieldTypes);

        $parts = [];
        foreach ($updates as $field => $value) {
            if (! in_array($field, $allowedFields, true)) {
                continue;
            }

            $label = $this->resolveFabricUpdateFieldLabel($field);
            $displayValue = $this->formatFabricUpdateValueForSummary($value, (string) ($writableFieldTypes[$field] ?? 'string'));
            $parts[] = $label . ' = ' . $displayValue;
        }

        $details = implode(', ', $parts);
        if ($details === '') {
            return __('projectx::lang.chat_apply_success');
        }

        return __('projectx::lang.chat_apply_success_detail', ['details' => $details]);
    }

    protected function resolveFabricUpdateFieldLabel(string $field): string
    {
        $translationAliases = [
            'currency' => 'currency_label',
            'fabric_sku' => 'fabric_sku_label',
            'payment_terms' => 'payment_terms_label',
        ];
        $translationKey = $translationAliases[$field] ?? $field;
        $translated = __('projectx::lang.' . $translationKey);

        if ($translated !== 'projectx::lang.' . $translationKey) {
            return $translated;
        }

        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * @param  mixed  $value
     */
    protected function formatFabricUpdateValueForSummary($value, string $type): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_string($value) && trim($value) === '') {
            return '-';
        }

        if ($type === 'decimal') {
            return is_numeric($value)
                ? rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.')
                : (string) $value;
        }

        if ($type === 'integer') {
            if (is_numeric($value) && (float) ((int) $value) === (float) $value) {
                return (string) ((int) $value);
            }

            return (string) $value;
        }

        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        if ($type === 'date') {
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    protected function sseErrorResponse(string $message)
    {
        return response()->stream(function () use ($message) {
            echo $this->sseEvent('error', ['message' => $message]);
        }, 200, $this->sseHeaders());
    }

    protected function sseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ];
    }

    protected function sseEvent(string $event, array $payload): string
    {
        return 'event: ' . $event . "\n" . 'data: ' . json_encode($payload) . "\n\n";
    }

    protected function chatDisabledJsonResponse()
    {
        return response()->json([
            'success' => false,
            'message' => __('projectx::lang.chat_disabled'),
        ], 403);
    }
}
