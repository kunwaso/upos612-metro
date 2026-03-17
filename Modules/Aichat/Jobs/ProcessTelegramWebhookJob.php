<?php

namespace Modules\Aichat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Aichat\Utils\ChatWorkflowUtil;
use Modules\Aichat\Utils\TelegramApiUtil;

class ProcessTelegramWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected string $webhookKey;

    protected array $update;

    public function __construct(string $webhookKey, array $update)
    {
        $this->webhookKey = $webhookKey;
        $this->update = $update;
    }

    public function handle(ChatUtil $chatUtil, ChatWorkflowUtil $chatWorkflowUtil, AIChatUtil $aiChatUtil, TelegramApiUtil $telegramApi): void
    {
        $bot = $chatUtil->findTelegramBotByWebhookKey($this->webhookKey);
        if (! $bot) {
            return;
        }

        $message = data_get($this->update, 'message');
        if (! is_array($message)) {
            return;
        }

        $chatId = (int) data_get($message, 'chat.id', 0);
        $chatType = (string) data_get($message, 'chat.type', '');
        $text = trim((string) data_get($message, 'text', ''));
        if ($chatId === 0) {
            return;
        }

        $businessId = (int) $bot->business_id;
        if (! $chatUtil->isChatEnabled($businessId)) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.chat_disabled'));

            return;
        }

        if ($this->isRateLimited($businessId, $chatId)) {
            return;
        }

        if ($chatType === 'group' || $chatType === 'supergroup') {
            $this->handleGroupMessage($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $bot, $chatId, $text);

            return;
        }

        if ($chatType === 'private') {
            $this->handlePrivateMessage($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $bot, $chatId, $text);
        }
    }

    protected function handleGroupMessage(
        ChatUtil $chatUtil,
        ChatWorkflowUtil $chatWorkflowUtil,
        AIChatUtil $aiChatUtil,
        TelegramApiUtil $telegramApi,
        $bot,
        int $chatId,
        string $text
    ): void {
        if ($this->isCommand($text, 'register')) {
            $this->safeSendText(
                $telegramApi,
                $chatUtil,
                $bot,
                $chatId,
                __('aichat::lang.telegram_register_group_reply', ['chat_id' => $chatId])
            );

            return;
        }

        if (! $chatUtil->isGroupAllowedForTelegram((int) $bot->business_id, $chatId)) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_group_not_allowed'));

            return;
        }

        $ownerUserId = (int) $bot->linked_user_id;

        if ($this->isCommand($text, 'help')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_help_reply'));

            return;
        }

        if ($this->isCommand($text, 'new')) {
            $chatUtil->resetTelegramConversation((int) $bot->business_id, $chatId, $ownerUserId);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_new_chat_started'));

            return;
        }

        if ($text === '') {
            return;
        }

        $conversation = $chatUtil->getOrCreateTelegramGroupConversation((int) $bot->business_id, $chatId, $ownerUserId);
        $this->processAiAndReply($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $bot, $chatId, $ownerUserId, $text, $conversation);
    }

    protected function handlePrivateMessage(
        ChatUtil $chatUtil,
        ChatWorkflowUtil $chatWorkflowUtil,
        AIChatUtil $aiChatUtil,
        TelegramApiUtil $telegramApi,
        $bot,
        int $chatId,
        string $text
    ): void {
        $startCode = $this->extractStartCode($text);
        if ($startCode !== null) {
            $linked = $chatUtil->consumeTelegramLinkCode((int) $bot->business_id, $startCode, $chatId);
            $this->safeSendText(
                $telegramApi,
                $chatUtil,
                $bot,
                $chatId,
                $linked ? __('aichat::lang.telegram_link_success') : __('aichat::lang.telegram_link_invalid')
            );

            return;
        }

        $telegramChat = $chatUtil->getTelegramChatForBusiness((int) $bot->business_id, $chatId);
        if (! $telegramChat) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_link_required'));

            return;
        }

        $userId = (int) $telegramChat->user_id;
        if (! $chatUtil->isUserAllowedForTelegram((int) $bot->business_id, $userId)) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_user_not_allowed'));

            return;
        }

        if ($this->isCommand($text, 'help')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_help_reply'));

            return;
        }

        if ($this->isCommand($text, 'new')) {
            $chatUtil->resetTelegramConversation((int) $bot->business_id, $chatId, $userId);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_new_chat_started'));

            return;
        }

        if ($text === '') {
            return;
        }

        $conversation = $chatUtil->getOrCreateTelegramConversation((int) $bot->business_id, $chatId, $userId);
        $this->processAiAndReply($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $bot, $chatId, $userId, $text, $conversation);
    }

    protected function processAiAndReply(
        ChatUtil $chatUtil,
        ChatWorkflowUtil $chatWorkflowUtil,
        AIChatUtil $aiChatUtil,
        TelegramApiUtil $telegramApi,
        $bot,
        int $chatId,
        int $userId,
        string $text,
        $conversation
    ): void {
        $modelOptions = $chatUtil->buildModelOptions((int) $bot->business_id, $userId);
        $payload = [
            'prompt' => $text,
            'provider' => (string) ($modelOptions['default_provider'] ?? config('aichat.chat.default_provider', 'openai')),
            'model' => (string) ($modelOptions['default_model'] ?? config('aichat.chat.default_model', 'gpt-4o-mini')),
        ];

        $workflowContext = $chatWorkflowUtil->prepareSendOrStreamContext((int) $bot->business_id, $userId, $conversation, $payload);
        if (! ($workflowContext['success'] ?? false)) {
            $this->safeSendText(
                $telegramApi,
                $chatUtil,
                $bot,
                $chatId,
                (string) ($workflowContext['error_message'] ?? __('aichat::lang.chat_provider_error'))
            );

            return;
        }

        try {
            $telegramApi->sendChatAction($chatUtil->getDecryptedBotToken($bot), $chatId, 'typing');
        } catch (\Throwable $exception) {
            // Ignore chat action errors.
        }

        $provider = (string) $workflowContext['provider'];
        $model = (string) $workflowContext['model'];
        $settings = $workflowContext['settings'];
        $credential = $workflowContext['credential'];
        $messages = (array) $workflowContext['messages'];

        try {
            $assistantText = $aiChatUtil->generateText(
                $provider,
                $chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
                $model,
                $messages
            );

            $normalizedResponse = $chatWorkflowUtil->normalizeAssistantText($assistantText, $settings);
            $assistantTextToSend = (string) ($normalizedResponse['text'] ?? $assistantText);

            $chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ASSISTANT,
                $assistantTextToSend,
                $provider,
                $model,
                $userId
            );

            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $assistantTextToSend);
        } catch (\Throwable $exception) {
            $errorText = (string) ($exception->getMessage() ?: __('aichat::lang.chat_provider_error'));
            $chatUtil->appendMessage($conversation, ChatMessage::ROLE_ERROR, $errorText, $provider, $model, $userId);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_error_generic'));
        }
    }

    protected function safeSendText(TelegramApiUtil $telegramApi, ChatUtil $chatUtil, $bot, int $chatId, string $message): void
    {
        try {
            $telegramApi->sendMessage($chatUtil->getDecryptedBotToken($bot), $chatId, $message);
        } catch (\Throwable $exception) {
            // Ignore send errors.
        }
    }

    protected function extractStartCode(string $text): ?string
    {
        if (! preg_match('/^\/start(?:@[a-zA-Z0-9_]+)?\s+(.+)$/', trim($text), $matches)) {
            return null;
        }

        return strtoupper(trim((string) ($matches[1] ?? '')));
    }

    protected function isCommand(string $text, string $command): bool
    {
        return (bool) preg_match('/^\/' . preg_quote($command, '/') . '(?:@[a-zA-Z0-9_]+)?(?:\s+.*)?$/', trim($text));
    }

    protected function isRateLimited(int $businessId, int $chatId): bool
    {
        $chatKey = 'aichat:telegram:chat:' . $businessId . ':' . $chatId;
        $businessKey = 'aichat:telegram:business:' . $businessId;
        $chatLimit = (int) config('aichat.telegram.chat_rate_limit_per_minute', 20);
        $businessLimit = (int) config('aichat.telegram.business_rate_limit_per_minute', 100);

        if (RateLimiter::tooManyAttempts($chatKey, max(1, $chatLimit))
            || RateLimiter::tooManyAttempts($businessKey, max(1, $businessLimit))) {
            return true;
        }

        RateLimiter::hit($chatKey, 60);
        RateLimiter::hit($businessKey, 60);

        return false;
    }
}
