<?php

namespace Modules\Aichat\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
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

    public function handle(
        ChatUtil $chatUtil,
        ChatWorkflowUtil $chatWorkflowUtil,
        AIChatUtil $aiChatUtil,
        TelegramApiUtil $telegramApi,
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        ?ChatActionUtil $chatActionUtil = null
    ): void {
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
            $this->handlePrivateMessage($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $quoteWizardUtil, $chatActionUtil, $bot, $chatId, $text);
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
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        ?ChatActionUtil $chatActionUtil,
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
        $user = $this->resolveTelegramUser((int) $bot->business_id, $userId);
        if (! $user) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_user_not_allowed'));

            return;
        }

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

        if ($this->isCommand($text, 'quote')) {
            $this->handleQuoteWizardStart($chatUtil, $telegramApi, $quoteWizardUtil, $bot, $chatId, $userId, $user);

            return;
        }

        if ($this->isCommand($text, 'cancel')) {
            $this->handleQuoteWizardCancel($chatUtil, $telegramApi, $quoteWizardUtil, $bot, $chatId, $userId, $user);

            return;
        }

        if ($text === '') {
            return;
        }

        $activeDraft = $quoteWizardUtil->getLatestActiveDraftForChannel((int) $bot->business_id, $userId, null, $chatId);
        if ($activeDraft) {
            $this->handleQuoteWizardActiveMessage(
                $chatUtil,
                $telegramApi,
                $quoteWizardUtil,
                $bot,
                $chatId,
                $userId,
                $user,
                $text,
                $activeDraft
            );

            return;
        }

        if ($chatActionUtil && $this->handlePendingActionMessage($chatActionUtil, $chatUtil, $telegramApi, $bot, $chatId, $userId, $text)) {
            return;
        }

        $conversation = $chatUtil->getOrCreateTelegramConversation((int) $bot->business_id, $chatId, $userId);
        $this->processAiAndReply($chatUtil, $chatWorkflowUtil, $aiChatUtil, $telegramApi, $bot, $chatId, $userId, $text, $conversation);
    }

    protected function handleQuoteWizardStart(
        ChatUtil $chatUtil,
        TelegramApiUtil $telegramApi,
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        $bot,
        int $chatId,
        int $userId,
        User $user
    ): void {
        $businessId = (int) $bot->business_id;
        if (! $this->isQuoteWizardEnabled()) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.quote_assistant_feature_disabled'));

            return;
        }

        if (! $this->userCan($user, 'aichat.quote_wizard.use')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('messages.unauthorized_action'));

            return;
        }

        $conversation = $chatUtil->getOrCreateTelegramConversation($businessId, $chatId, $userId);
        $draft = $quoteWizardUtil->getOrCreateDraft(null, $userId, $businessId, null, $chatId);
        $result = $quoteWizardUtil->processStep($draft, $conversation, $userId, $businessId, [
            'message' => '',
            'channel' => 'telegram',
        ]);

        $chatUtil->audit($businessId, $userId, 'quote_wizard_step_processed', (string) $conversation->id, null, null, [
            'draft_id' => (string) $result['draft']->id,
            'status' => (string) ($result['state']['status'] ?? ProductQuoteDraft::STATUS_COLLECTING),
            'channel' => 'telegram',
            'command' => '/quote',
        ]);

        $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, (string) $result['assistant_message']->content);
    }

    protected function handleQuoteWizardCancel(
        ChatUtil $chatUtil,
        TelegramApiUtil $telegramApi,
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        $bot,
        int $chatId,
        int $userId,
        User $user
    ): void {
        if (! $this->userCan($user, 'aichat.quote_wizard.use')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('messages.unauthorized_action'));

            return;
        }

        $businessId = (int) $bot->business_id;
        $draft = $quoteWizardUtil->getLatestActiveDraftForChannel($businessId, $userId, null, $chatId);
        if (! $draft) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_quote_wizard_no_active_draft'));

            return;
        }

        $quoteWizardUtil->expireDraft($draft);
        $chatUtil->audit($businessId, $userId, 'quote_wizard_cancelled', null, null, null, [
            'draft_id' => (string) $draft->id,
            'channel' => 'telegram',
        ]);

        $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_quote_wizard_cancelled'));
    }

    protected function handleQuoteWizardActiveMessage(
        ChatUtil $chatUtil,
        TelegramApiUtil $telegramApi,
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        $bot,
        int $chatId,
        int $userId,
        User $user,
        string $text,
        ProductQuoteDraft $activeDraft
    ): void {
        if (! $this->isQuoteWizardEnabled()) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.quote_assistant_feature_disabled'));

            return;
        }

        if (! $this->userCan($user, 'aichat.quote_wizard.use')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('messages.unauthorized_action'));

            return;
        }

        $normalizedText = trim($text);
        if (strtoupper($normalizedText) === 'CONFIRM') {
            $this->handleQuoteWizardConfirm($chatUtil, $telegramApi, $quoteWizardUtil, $bot, $chatId, $userId, $user, $activeDraft);

            return;
        }

        $businessId = (int) $bot->business_id;
        $conversation = $chatUtil->getOrCreateTelegramConversation($businessId, $chatId, $userId);
        $serializedDraft = $quoteWizardUtil->serializeDraft($activeDraft);

        $customerPick = $this->parseTelegramCustomerPickIndex($normalizedText);
        if ($customerPick !== null) {
            $contactOptions = array_values((array) data_get($serializedDraft, 'pick_lists.contacts', []));
            $selectedContact = $contactOptions[$customerPick - 1] ?? null;

            if (! is_array($selectedContact) || empty($selectedContact['id'])) {
                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $this->buildInvalidPickMessage());

                return;
            }

            $result = $quoteWizardUtil->processStep($activeDraft, $conversation, $userId, $businessId, [
                'message' => '',
                'channel' => 'telegram',
                'selected_contact_id' => (int) $selectedContact['id'],
            ]);

            $chatUtil->audit($businessId, $userId, 'quote_wizard_step_processed', (string) $conversation->id, null, null, [
                'draft_id' => (string) $result['draft']->id,
                'status' => (string) ($result['state']['status'] ?? ProductQuoteDraft::STATUS_COLLECTING),
                'channel' => 'telegram',
                'selection' => 'customer',
                'selection_index' => $customerPick,
            ]);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, (string) $result['assistant_message']->content);

            return;
        }

        $productPick = $this->parseTelegramProductPick($normalizedText);
        if ($productPick !== null) {
            $groupIndex = (int) $productPick['group_index'];
            $optionIndex = (int) $productPick['option_index'];
            $productGroups = array_values((array) data_get($serializedDraft, 'pick_lists.products', []));
            $selectedGroup = $productGroups[$groupIndex - 1] ?? null;
            $selectedProduct = is_array($selectedGroup)
                ? ((array) ($selectedGroup['options'] ?? []))[$optionIndex - 1] ?? null
                : null;

            if (! is_array($selectedGroup) || ! is_array($selectedProduct) || empty($selectedProduct['id'])) {
                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $this->buildInvalidPickMessage());

                return;
            }

            $result = $quoteWizardUtil->processStep($activeDraft, $conversation, $userId, $businessId, [
                'message' => '',
                'channel' => 'telegram',
                'selected_product_id' => (int) $selectedProduct['id'],
                'selected_line_uid' => (string) ($selectedGroup['line_uid'] ?? ''),
            ]);

            $chatUtil->audit($businessId, $userId, 'quote_wizard_step_processed', (string) $conversation->id, null, null, [
                'draft_id' => (string) $result['draft']->id,
                'status' => (string) ($result['state']['status'] ?? ProductQuoteDraft::STATUS_COLLECTING),
                'channel' => 'telegram',
                'selection' => 'product',
                'line_index' => $groupIndex,
                'selection_index' => $optionIndex,
            ]);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, (string) $result['assistant_message']->content);

            return;
        }

        $result = $quoteWizardUtil->processStep($activeDraft, $conversation, $userId, $businessId, [
            'message' => $normalizedText,
            'channel' => 'telegram',
        ]);

        $chatUtil->audit($businessId, $userId, 'quote_wizard_step_processed', (string) $conversation->id, null, null, [
            'draft_id' => (string) $result['draft']->id,
            'status' => (string) ($result['state']['status'] ?? ProductQuoteDraft::STATUS_COLLECTING),
            'channel' => 'telegram',
        ]);
        $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, (string) $result['assistant_message']->content);
    }

    protected function handleQuoteWizardConfirm(
        ChatUtil $chatUtil,
        TelegramApiUtil $telegramApi,
        ChatProductQuoteWizardUtil $quoteWizardUtil,
        $bot,
        int $chatId,
        int $userId,
        User $user,
        ProductQuoteDraft $draft
    ): void {
        if (! $this->userCan($user, 'product_quote.create')) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('messages.unauthorized_action'));

            return;
        }

        $businessId = (int) $bot->business_id;
        $conversation = $chatUtil->getOrCreateTelegramConversation($businessId, $chatId, $userId);

        try {
            $confirmResult = $quoteWizardUtil->confirmDraft($draft, $businessId, $userId);
            $assistantText = __('aichat::lang.quote_assistant_success_prompt')
                . "\n"
                . (string) $confirmResult['public_url']
                . "\n"
                . (string) $confirmResult['admin_url'];

            $chatUtil->appendMessage($conversation, ChatMessage::ROLE_ASSISTANT, $assistantText, null, null, $userId);
            $chatUtil->audit($businessId, $userId, 'quote_created_from_chat', (string) $conversation->id, null, null, [
                'draft_id' => (string) data_get($confirmResult, 'draft.id'),
                'quote_id' => (int) data_get($confirmResult, 'quote.id'),
                'channel' => 'telegram',
            ]);

            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $assistantText);
        } catch (\Throwable $exception) {
            $errorMessage = (string) ($exception->getMessage() ?: __('aichat::lang.quote_assistant_validation_failed'));
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $errorMessage);
        }
    }

    protected function handlePendingActionMessage(
        ChatActionUtil $chatActionUtil,
        ChatUtil $chatUtil,
        TelegramApiUtil $telegramApi,
        $bot,
        int $chatId,
        int $userId,
        string $text
    ): bool {
        $normalized = trim($text);
        if ($normalized === '') {
            return false;
        }

        $isListCommand = $this->isPendingActionListCommand($normalized);
        $confirmActionId = $this->parseTelegramActionConfirmId($normalized);
        $cancelActionId = $this->parseTelegramActionCancelId($normalized);
        if (! $isListCommand && $confirmActionId === null && $cancelActionId === null) {
            return false;
        }
        if (! (bool) config('aichat.actions.enabled', false)) {
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.chat_action_disabled'));

            return true;
        }

        $businessId = (int) $bot->business_id;
        $conversation = $chatUtil->getOrCreateTelegramConversation($businessId, $chatId, $userId);
        $conversationId = (string) $conversation->id;

        if ($isListCommand) {
            try {
                $items = $chatActionUtil->listPendingActions($businessId, $userId, $conversationId);
                if (empty($items)) {
                    $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_action_pending_empty'));

                    return true;
                }

                $lines = [__('aichat::lang.telegram_action_pending_header')];
                foreach ($items as $item) {
                    $lines[] = '#' . (int) ($item['id'] ?? 0)
                        . ' '
                        . (string) ($item['module'] ?? '')
                        . '.'
                        . (string) ($item['action'] ?? '')
                        . ' - '
                        . (string) ($item['preview_text'] ?? '');
                }

                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, implode("\n", $lines));
            } catch (\Throwable $exception) {
                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $this->telegramActionFailureMessage());
            }

            return true;
        }

        if ($confirmActionId !== null) {
            try {
                $action = $confirmActionId > 0
                    ? $chatActionUtil->getPendingActionByIdForUser($businessId, $userId, $conversationId, $confirmActionId)
                    : $chatActionUtil->getLatestPendingActionForUser($businessId, $userId, $conversationId);
                if (! $action) {
                    $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_action_pending_empty'));

                    return true;
                }

                $result = $chatActionUtil->confirmAction(
                    $businessId,
                    $userId,
                    $conversationId,
                    (int) $action->id,
                    'telegram',
                    null
                );
                $responseText = __('aichat::lang.telegram_action_confirm_success')
                    . "\n"
                    . '#'
                    . (int) $result->id
                    . ' '
                    . (string) $result->module
                    . '.'
                    . (string) $result->action;

                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $responseText);
            } catch (\Throwable $exception) {
                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $this->telegramActionFailureMessage());
            }

            return true;
        }

        if ($cancelActionId !== null) {
            try {
                $action = $cancelActionId > 0
                    ? $chatActionUtil->getPendingActionByIdForUser($businessId, $userId, $conversationId, $cancelActionId)
                    : $chatActionUtil->getLatestPendingActionForUser($businessId, $userId, $conversationId);
                if (! $action) {
                    $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, __('aichat::lang.telegram_action_pending_empty'));

                    return true;
                }

                $result = $chatActionUtil->cancelAction(
                    $businessId,
                    $userId,
                    $conversationId,
                    (int) $action->id,
                    null
                );
                $responseText = __('aichat::lang.telegram_action_cancel_success')
                    . "\n"
                    . '#'
                    . (int) $result->id
                    . ' '
                    . (string) $result->module
                    . '.'
                    . (string) $result->action;

                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $responseText);
            } catch (\Throwable $exception) {
                $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $this->telegramActionFailureMessage());
            }

            return true;
        }

        return false;
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
            'channel' => 'telegram',
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

            $telegramTextToSend = $chatUtil->normalizeTelegramOutboundText($assistantTextToSend);
            $this->safeSendText($telegramApi, $chatUtil, $bot, $chatId, $telegramTextToSend);
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

    protected function telegramActionFailureMessage(): string
    {
        return __('aichat::lang.telegram_action_failed');
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

    protected function parseTelegramCustomerPickIndex(string $text): ?int
    {
        if (! preg_match('/^C\s+(\d+)$/i', trim($text), $matches)) {
            return null;
        }

        return max(1, (int) ($matches[1] ?? 0));
    }

    protected function isPendingActionListCommand(string $text): bool
    {
        return (bool) preg_match('/^(?:\/actions(?:@[a-zA-Z0-9_]+)?|ACTIONS)$/i', trim($text));
    }

    protected function parseTelegramActionConfirmId(string $text): ?int
    {
        $normalized = trim($text);
        if (! preg_match('/^(?:\/confirm_action(?:@[a-zA-Z0-9_]+)?|CONFIRM\s+ACTION)(?:\s+(\d+))?$/i', $normalized, $matches)) {
            return null;
        }

        if (! isset($matches[1]) || trim((string) $matches[1]) === '') {
            return 0;
        }

        return max(1, (int) $matches[1]);
    }

    protected function parseTelegramActionCancelId(string $text): ?int
    {
        $normalized = trim($text);
        if (! preg_match('/^(?:\/cancel_action(?:@[a-zA-Z0-9_]+)?|CANCEL\s+ACTION)(?:\s+(\d+))?$/i', $normalized, $matches)) {
            return null;
        }

        if (! isset($matches[1]) || trim((string) $matches[1]) === '') {
            return 0;
        }

        return max(1, (int) $matches[1]);
    }

    protected function parseTelegramProductPick(string $text): ?array
    {
        if (! preg_match('/^P\s+(\d+)\s+(\d+)$/i', trim($text), $matches)) {
            return null;
        }

        return [
            'group_index' => max(1, (int) ($matches[1] ?? 0)),
            'option_index' => max(1, (int) ($matches[2] ?? 0)),
        ];
    }

    protected function buildInvalidPickMessage(): string
    {
        return __('aichat::lang.telegram_quote_wizard_invalid_pick')
            . "\n"
            . __('aichat::lang.telegram_quote_wizard_pick_help');
    }

    protected function resolveTelegramUser(int $businessId, int $userId): ?User
    {
        return User::where('business_id', $businessId)
            ->where('id', $userId)
            ->first();
    }

    protected function userCan(?User $user, string $permission): bool
    {
        return $user ? (bool) $user->can($permission) : false;
    }

    protected function isQuoteWizardEnabled(): bool
    {
        return (bool) config('aichat.quote_wizard.enabled', true);
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
