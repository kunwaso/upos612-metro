<?php

namespace Modules\Aichat\Utils;

use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ChatSetting;

class ChatWorkflowUtil
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function prepareSendOrStreamContext(int $business_id, int $user_id, ChatConversation $conversation, array $payload): array
    {
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $provider = (string) ($payload['provider'] ?? '');
        $model = (string) ($payload['model'] ?? '');
        $prompt = (string) ($payload['prompt'] ?? '');
        $channel = strtolower(trim((string) ($payload['channel'] ?? 'web')));
        if (! in_array($channel, ['web', 'telegram'], true)) {
            $channel = 'web';
        }

        $capabilityEnvelope = $this->chatUtil->resolveCapabilityEnvelope($business_id, $user_id, $channel);

        if (! $this->chatUtil->isModelAllowedForBusiness($business_id, $provider, $model)) {
            return [
                'success' => false,
                'error_type' => 'model_invalid',
                'error_message' => __('aichat::lang.chat_validation_model_invalid'),
            ];
        }

        $piiPolicyResult = $this->chatUtil->applyPiiPolicy($prompt, $settings);
        if (! empty($piiPolicyResult['blocked'])) {
            return [
                'success' => false,
                'error_type' => 'pii_blocked',
                'error_message' => __('aichat::lang.chat_blocked_sensitive_data'),
            ];
        }

        $sanitizedPrompt = (string) ($piiPolicyResult['text'] ?? $prompt);
        $warnings = (array) ($piiPolicyResult['warnings'] ?? []);

        $userMessage = $this->chatUtil->appendMessage(
            $conversation,
            ChatMessage::ROLE_USER,
            $sanitizedPrompt,
            $provider,
            $model,
            $user_id
        );

        $credential = $this->chatUtil->resolveCredentialForChat($user_id, $business_id, $provider);
        if (! $credential) {
            $errorMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ERROR,
                __('aichat::lang.chat_missing_provider_key'),
                $provider,
                $model,
                $user_id
            );

            return [
                'success' => false,
                'error_type' => 'credential_missing',
                'error_message' => __('aichat::lang.chat_missing_provider_key'),
                'warnings' => array_values(array_unique($warnings)),
                'settings' => $settings,
                'provider' => $provider,
                'model' => $model,
                'user_message' => $userMessage,
                'error_message_model' => $errorMessage,
            ];
        }

        return [
            'success' => true,
            'warnings' => array_values(array_unique($warnings)),
            'settings' => $settings,
            'provider' => $provider,
            'model' => $model,
            'messages' => $this->chatUtil->buildProviderMessages(
                $conversation,
                (string) ($settings->system_prompt ?? ''),
                null,
                30,
                (int) $userMessage->id,
                $sanitizedPrompt,
                null,
                null,
                $user_id,
                null,
                null,
                null,
                null,
                null,
                null,
                $capabilityEnvelope,
                $channel
            ),
            'capability_envelope' => $capabilityEnvelope,
            'user_message' => $userMessage,
            'credential' => $credential,
        ];
    }

    public function prepareRegenerateContext(int $business_id, int $user_id, ChatConversation $conversation, ChatMessage $assistantMessage, array $payload): array
    {
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $provider = strtolower(trim((string) ($assistantMessage->provider ?: $settings->default_provider ?: config('aichat.chat.default_provider', 'openai'))));
        $model = trim((string) ($assistantMessage->model ?: $settings->default_model ?: config('aichat.chat.default_model', 'gpt-4o-mini')));
        $channel = strtolower(trim((string) ($payload['channel'] ?? 'web')));
        if (! in_array($channel, ['web', 'telegram'], true)) {
            $channel = 'web';
        }
        $capabilityEnvelope = $this->chatUtil->resolveCapabilityEnvelope($business_id, $user_id, $channel);

        if (! $this->chatUtil->isModelAllowedForBusiness($business_id, $provider, $model)) {
            return [
                'success' => false,
                'error_message' => __('aichat::lang.chat_validation_model_invalid'),
            ];
        }

        $sourceUserMessage = $this->chatUtil->getPreviousUserMessage($business_id, $assistantMessage);
        if (! $sourceUserMessage) {
            return [
                'success' => false,
                'error_message' => __('aichat::lang.chat_regenerate_user_prompt_missing'),
            ];
        }

        $piiPolicyResult = $this->chatUtil->applyPiiPolicy((string) $sourceUserMessage->content, $settings);
        if (! empty($piiPolicyResult['blocked'])) {
            return [
                'success' => false,
                'error_message' => __('aichat::lang.chat_blocked_sensitive_data'),
            ];
        }

        $credential = $this->chatUtil->resolveCredentialForChat($user_id, $business_id, $provider);
        if (! $credential) {
            return [
                'success' => false,
                'error_message' => __('aichat::lang.chat_missing_provider_key'),
            ];
        }

        return [
            'success' => true,
            'warnings' => array_values(array_unique((array) ($piiPolicyResult['warnings'] ?? []))),
            'settings' => $settings,
            'provider' => $provider,
            'model' => $model,
            'messages' => $this->chatUtil->buildProviderMessages(
                $conversation,
                (string) ($settings->system_prompt ?? ''),
                null,
                30,
                (int) $sourceUserMessage->id,
                (string) $sourceUserMessage->content,
                null,
                null,
                $user_id,
                null,
                null,
                null,
                null,
                null,
                null,
                $capabilityEnvelope,
                $channel
            ),
            'capability_envelope' => $capabilityEnvelope,
            'credential' => $credential,
            'source_user_message' => $sourceUserMessage,
        ];
    }

    public function normalizeAssistantText(string $assistantText, ChatSetting $settings): array
    {
        $normalizedText = trim($assistantText) === ''
            ? __('aichat::lang.chat_provider_empty_response')
            : $assistantText;

        $moderationResult = $this->chatUtil->moderateAssistantText($normalizedText, $settings);

        return [
            'text' => (string) ($moderationResult['text'] ?? $normalizedText),
            'moderated' => ! empty($moderationResult['moderated']),
        ];
    }
}
