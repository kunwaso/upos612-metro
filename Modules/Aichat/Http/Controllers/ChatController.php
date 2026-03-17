<?php

namespace Modules\Aichat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Http\Requests\Chat\CreateChatConversationRequest;
use Modules\Aichat\Http\Requests\Chat\DeleteChatConversationRequest;
use Modules\Aichat\Http\Requests\Chat\ExportChatConversationRequest;
use Modules\Aichat\Http\Requests\Chat\ListChatConversationsRequest;
use Modules\Aichat\Http\Requests\Chat\RegenerateChatMessageRequest;
use Modules\Aichat\Http\Requests\Chat\SaveChatMessageFeedbackRequest;
use Modules\Aichat\Http\Requests\Chat\SendChatMessageRequest;
use Modules\Aichat\Http\Requests\Chat\ShareChatConversationRequest;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Aichat\Utils\ChatWorkflowUtil;

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
        if (! auth()->user()->can('aichat.chat.view')) {
            abort(403, __('aichat::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            abort(403, __('aichat::lang.chat_disabled'));
        }

        $aiChatConfig = $this->chatUtil->buildClientConfig($business_id, (int) auth()->id());
        $initialConversationId = (string) $request->query('conversation', '');

        return view('aichat::chat.index', compact('aiChatConfig', 'initialConversationId'));
    }

    public function config(Request $request)
    {
        if (! auth()->user()->can('aichat.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        return response()->json([
            'success' => true,
            'data' => $this->chatUtil->buildClientConfig($business_id, (int) auth()->id()),
        ]);
    }

    public function conversations(ListChatConversationsRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $items = $this->chatUtil->listConversationsForUser(
            $business_id,
            (int) auth()->id(),
            (bool) ($request->validated()['include_archived'] ?? false)
        )->map(function (ChatConversation $conversation) {
            return $this->chatUtil->serializeConversation($conversation);
        })->values();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function showConversation(string $id, Request $request)
    {
        if (! auth()->user()->can('aichat.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);

        return response()->json([
            'success' => true,
            'data' => $this->buildConversationPayload($business_id, $user_id, $conversation),
        ]);
    }

    public function storeConversation(CreateChatConversationRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $conversation = $this->chatUtil->createConversation(
            $business_id,
            $user_id,
            trim((string) ($request->validated()['title'] ?? '')) ?: null
        );

        $this->chatUtil->audit($business_id, $user_id, 'conversation_created', $conversation->id);

        return response()->json([
            'success' => true,
            'data' => $this->chatUtil->serializeConversation($conversation),
        ]);
    }

    public function destroyConversation(DeleteChatConversationRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        try {
            $this->chatUtil->deleteConversationForUser($business_id, $user_id, $id);
            $this->chatUtil->audit($business_id, $user_id, 'conversation_deleted', $id);

            return $this->respondSuccess(__('aichat::lang.chat_delete_success'));
        } catch (ModelNotFoundException $exception) {
            return $this->respondWithError(__('aichat::lang.chat_conversation_not_found'));
        } catch (\Throwable $exception) {
            return $this->respondWentWrong($exception);
        }
    }

    public function send(SendChatMessageRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $workflowContext = $this->chatWorkflowUtil->prepareSendOrStreamContext($business_id, $user_id, $conversation, $request->validated());

        if (! ($workflowContext['success'] ?? false)) {
            if (($workflowContext['error_type'] ?? '') === 'credential_missing') {
                return response()->json([
                    'success' => false,
                    'message' => $workflowContext['error_message'],
                    'user_message' => $this->chatUtil->serializeMessage($workflowContext['user_message']),
                    'error_message' => $this->chatUtil->serializeMessage($workflowContext['error_message_model']),
                    'warnings' => (array) ($workflowContext['warnings'] ?? []),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => $workflowContext['error_message'] ?? __('aichat::lang.chat_provider_error'),
            ], 422);
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $settings = $workflowContext['settings'];
        $warnings = (array) $workflowContext['warnings'];
        $userMessage = $workflowContext['user_message'];
        $credential = $workflowContext['credential'];
        $messages = (array) $workflowContext['messages'];

        try {
            $assistantText = $this->aiChatUtil->generateText(
                $provider,
                $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
                $model,
                $messages
            );

            $normalizedResponse = $this->chatWorkflowUtil->normalizeAssistantText($assistantText, $settings);
            if (! empty($normalizedResponse['moderated'])) {
                $warnings[] = __('aichat::lang.chat_warning_moderation_applied');
            }

            $assistantMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ASSISTANT,
                (string) $normalizedResponse['text'],
                $provider,
                $model,
                $user_id
            );

            $this->chatUtil->audit($business_id, $user_id, 'message_send_success', $conversation->id, $provider, $model, [
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
            $errorText = $exception->getMessage() ?: __('aichat::lang.chat_provider_error');
            $errorMessage = $this->chatUtil->appendMessage($conversation, ChatMessage::ROLE_ERROR, $errorText, $provider, $model, $user_id);
            $this->chatUtil->audit($business_id, $user_id, 'message_send_error', $conversation->id, $provider, $model, ['error' => $errorText]);

            return response()->json([
                'success' => false,
                'message' => $errorText,
                'user_message' => $this->chatUtil->serializeMessage($userMessage),
                'error_message' => $this->chatUtil->serializeMessage($errorMessage),
                'warnings' => $warnings,
            ], 422);
        }
    }

    public function stream(SendChatMessageRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $workflowContext = $this->chatWorkflowUtil->prepareSendOrStreamContext($business_id, $user_id, $conversation, $request->validated());

        if (! ($workflowContext['success'] ?? false)) {
            return $this->sseErrorResponse((string) ($workflowContext['error_message'] ?? __('aichat::lang.chat_provider_error')));
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $settings = $workflowContext['settings'];
        $warnings = (array) $workflowContext['warnings'];
        $messages = (array) $workflowContext['messages'];
        $credential = $workflowContext['credential'];
        $userMessage = $workflowContext['user_message'];

        return response()->stream(function () use ($business_id, $user_id, $conversation, $provider, $model, $settings, $warnings, $messages, $credential, $userMessage) {
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
                if (! empty($normalizedResponse['moderated'])) {
                    echo $this->sseEvent('warning', ['message' => __('aichat::lang.chat_warning_moderation_applied')]);
                }

                $assistantMessage = $this->chatUtil->appendMessage(
                    $conversation,
                    ChatMessage::ROLE_ASSISTANT,
                    (string) $normalizedResponse['text'],
                    $provider,
                    $model,
                    $user_id
                );

                $this->chatUtil->audit($business_id, $user_id, 'message_stream_success', $conversation->id, $provider, $model, [
                    'response_chars' => mb_strlen((string) $normalizedResponse['text']),
                ]);

                echo $this->sseEvent('done', [
                    'assistant_message' => $this->chatUtil->serializeMessage($assistantMessage, null, $assistantMessage->id),
                    'suggested_replies' => $this->chatUtil->getSuggestedReplies($settings),
                ]);
            } catch (\Throwable $exception) {
                $errorText = $exception->getMessage() ?: __('aichat::lang.chat_provider_error');
                $errorMessage = $this->chatUtil->appendMessage($conversation, ChatMessage::ROLE_ERROR, $errorText, $provider, $model, $user_id);
                $this->chatUtil->audit($business_id, $user_id, 'message_stream_error', $conversation->id, $provider, $model, ['error' => $errorText]);
                echo $this->sseEvent('error', [
                    'message' => $errorText,
                    'error_message' => $this->chatUtil->serializeMessage($errorMessage),
                ]);
            }
        }, 200, $this->sseHeaders());
    }

    public function feedback(SaveChatMessageFeedbackRequest $request, string $message)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $chatMessage = $this->chatUtil->getMessageByIdForUser($business_id, $user_id, (int) $message);
        if ($chatMessage->role !== ChatMessage::ROLE_ASSISTANT) {
            return response()->json(['success' => false, 'message' => __('aichat::lang.chat_feedback_assistant_only')], 422);
        }

        $saved = $this->chatUtil->saveMessageFeedback($business_id, $user_id, $chatMessage, (string) $request->validated()['feedback'], $request->validated()['note'] ?? null);
        $this->chatUtil->audit($business_id, $user_id, 'message_feedback_saved', (string) $chatMessage->conversation_id, $chatMessage->provider, $chatMessage->model, [
            'message_id' => (int) $chatMessage->id,
            'feedback' => (string) $saved->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('aichat::lang.chat_feedback_saved'),
            'data' => [
                'message_id' => (int) $chatMessage->id,
                'feedback_value' => (string) $saved->feedback,
            ],
        ]);
    }

    public function regenerate(RegenerateChatMessageRequest $request, string $message)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $assistantMessage = $this->chatUtil->getMessageByIdForUser($business_id, $user_id, (int) $message);
        if ($assistantMessage->role !== ChatMessage::ROLE_ASSISTANT) {
            return $this->sseErrorResponse(__('aichat::lang.chat_feedback_assistant_only'));
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, (string) $assistantMessage->conversation_id);
        $latestAssistantMessageId = $this->chatUtil->getLatestAssistantMessageId($business_id, (string) $conversation->id);
        if ($latestAssistantMessageId === null || (int) $assistantMessage->id !== $latestAssistantMessageId) {
            return $this->sseErrorResponse(__('aichat::lang.chat_regenerate_latest_only'));
        }

        $workflowContext = $this->chatWorkflowUtil->prepareRegenerateContext($business_id, $user_id, $conversation, $assistantMessage, $request->validated());
        if (! ($workflowContext['success'] ?? false)) {
            return $this->sseErrorResponse((string) ($workflowContext['error_message'] ?? __('aichat::lang.chat_provider_error')));
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $warnings = (array) $workflowContext['warnings'];
        $messages = (array) $workflowContext['messages'];
        $credential = $workflowContext['credential'];
        $settings = $workflowContext['settings'];

        return response()->stream(function () use ($assistantMessage, $conversation, $business_id, $user_id, $provider, $model, $warnings, $messages, $credential, $settings) {
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
                if (! empty($normalizedResponse['moderated'])) {
                    echo $this->sseEvent('warning', ['message' => __('aichat::lang.chat_warning_moderation_applied')]);
                }

                $updatedAssistantMessage = $this->chatUtil->replaceAssistantMessageContent(
                    $assistantMessage,
                    (string) $normalizedResponse['text'],
                    $provider,
                    $model
                );

                $this->chatUtil->audit($business_id, $user_id, 'message_regenerated', (string) $conversation->id, $provider, $model, [
                    'message_id' => (int) $updatedAssistantMessage->id,
                    'response_chars' => mb_strlen((string) $normalizedResponse['text']),
                ]);

                echo $this->sseEvent('done', [
                    'assistant_message' => $this->serializeMessageForUser($business_id, $user_id, $updatedAssistantMessage, (int) $updatedAssistantMessage->id),
                    'suggested_replies' => $this->chatUtil->getSuggestedReplies($settings),
                ]);
            } catch (\Throwable $exception) {
                $errorText = $exception->getMessage() ?: __('aichat::lang.chat_provider_error');
                $this->chatUtil->audit($business_id, $user_id, 'message_regenerate_error', (string) $conversation->id, $provider, $model, [
                    'message_id' => (int) $assistantMessage->id,
                    'error' => $errorText,
                ]);
                echo $this->sseEvent('error', ['message' => $errorText]);
            }
        }, 200, $this->sseHeaders());
    }

    public function share(ShareChatConversationRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return $this->chatDisabledJsonResponse();
        }

        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $ttl = (int) ($request->validated()['ttl_hours'] ?? $settings->share_ttl_hours);

        $this->chatUtil->audit($business_id, $user_id, 'conversation_share_link_created', $conversation->id, null, null, ['ttl_hours' => $ttl]);

        return response()->json([
            'success' => true,
            'data' => ['url' => $this->chatUtil->createShareUrl($conversation, $ttl)],
        ]);
    }

    public function export(ExportChatConversationRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            abort(403, __('aichat::lang.chat_disabled'));
        }

        $format = (string) ($request->input('format') ?: 'markdown');
        $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
        $messages = ChatMessage::forBusiness($business_id)->where('conversation_id', $conversation->id)->orderBy('created_at')->orderBy('id')->get();

        $this->chatUtil->audit($business_id, $user_id, 'conversation_export', $conversation->id, null, null, ['format' => $format]);

        if ($format === 'pdf') {
            $html = view('aichat::chat.export_pdf', compact('conversation', 'messages'))->render();
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
        $chatConversation = ChatConversation::query()->findOrFail($conversation);
        $messages = ChatMessage::forBusiness((int) $chatConversation->business_id)
            ->where('conversation_id', $chatConversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('aichat::chat.shared', compact('chatConversation', 'messages'));
    }

    protected function buildConversationPayload(int $business_id, int $user_id, ChatConversation $conversation): array
    {
        $messageModels = ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $latestAssistantMessageId = $this->chatUtil->getLatestAssistantMessageId($business_id, (string) $conversation->id);
        $feedbackMap = $this->chatUtil->getFeedbackMapForUser($business_id, $user_id, $messageModels->pluck('id')->all());
        $messages = $messageModels->map(function (ChatMessage $message) use ($feedbackMap, $latestAssistantMessageId) {
            return $this->chatUtil->serializeMessage($message, $feedbackMap[(int) $message->id] ?? null, $latestAssistantMessageId);
        })->values();

        return [
            'conversation' => $this->chatUtil->serializeConversation($conversation),
            'messages' => $messages,
        ];
    }

    protected function serializeMessageForUser(int $business_id, int $user_id, ChatMessage $message, ?int $latestAssistantMessageId = null): array
    {
        $feedbackMap = $this->chatUtil->getFeedbackMapForUser($business_id, $user_id, [(int) $message->id]);

        return $this->chatUtil->serializeMessage($message, $feedbackMap[(int) $message->id] ?? null, $latestAssistantMessageId);
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
            'message' => __('aichat::lang.chat_disabled'),
        ], 403);
    }
}
