<?php

namespace Modules\ProjectX\Utils;

use Modules\ProjectX\Entities\ChatConversation;
use Modules\ProjectX\Entities\ChatMessage;
use Modules\ProjectX\Entities\ChatSetting;

class ChatWorkflowUtil
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function prepareSendOrStreamContext(
        int $business_id,
        int $user_id,
        ChatConversation $conversation,
        array $payload
    ): array {
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $provider = (string) ($payload['provider'] ?? '');
        $model = (string) ($payload['model'] ?? '');
        $prompt = (string) ($payload['prompt'] ?? '');
        $warnings = [];

        if (! $this->chatUtil->isModelAllowedForBusiness($business_id, $provider, $model)) {
            return [
                'success' => false,
                'error_type' => 'model_invalid',
                'error_message' => __('projectx::lang.chat_validation_model_invalid'),
            ];
        }

        $piiPolicyResult = $this->chatUtil->applyPiiPolicy($prompt, $settings);
        $warnings = array_merge($warnings, (array) ($piiPolicyResult['warnings'] ?? []));
        if (! empty($piiPolicyResult['blocked'])) {
            return [
                'success' => false,
                'error_type' => 'pii_blocked',
                'error_message' => __('projectx::lang.chat_blocked_sensitive_data'),
            ];
        }

        $fabricContextResult = $this->chatUtil->resolveFabricContext($business_id, $payload, $settings);
        $fabricContext = (string) ($fabricContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($fabricContextResult['warnings'] ?? []));
        $contextFabricId = (int) ($fabricContextResult['fabric_id'] ?? 0);
        $contextGeneratedAt = (string) ($fabricContextResult['context_generated_at'] ?? '');
        $appliedFabricId = $fabricContext !== '' && $contextFabricId > 0 ? $contextFabricId : null;
        $appliedFabricInsight = $appliedFabricId !== null
            && $appliedFabricId > 0
            && (bool) ($payload['fabric_insight'] ?? false);

        $payload = $this->chatUtil->enrichPayloadContextIds($business_id, $payload);

        $quoteContextResult = $this->chatUtil->resolveQuoteContext($business_id, $payload, $settings);
        $quoteContext = (string) ($quoteContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($quoteContextResult['warnings'] ?? []));
        $quoteId = (int) ($quoteContextResult['quote_id'] ?? 0);
        $appliedQuoteId = $quoteContext !== '' && $quoteId > 0 ? $quoteId : null;

        $trimContextResult = $this->chatUtil->resolveTrimContext($business_id, $payload, $settings);
        $trimContext = (string) ($trimContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($trimContextResult['warnings'] ?? []));
        $trimId = (int) ($trimContextResult['trim_id'] ?? 0);
        $appliedTrimId = $trimContext !== '' && $trimId > 0 ? $trimId : null;

        $salesOrderContextResult = $this->chatUtil->resolveSalesOrderContext($business_id, $payload, $settings);
        $salesOrderContext = (string) ($salesOrderContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($salesOrderContextResult['warnings'] ?? []));
        $transactionId = (int) ($salesOrderContextResult['transaction_id'] ?? 0);
        $appliedTransactionId = $salesOrderContext !== '' && $transactionId > 0 ? $transactionId : null;

        $userMessage = $this->chatUtil->appendMessage(
            $conversation,
            ChatMessage::ROLE_USER,
            $prompt,
            $provider,
            $model,
            $user_id,
            $appliedFabricId,
            $appliedFabricInsight
        );

        $credential = $this->chatUtil->resolveCredentialForChat($user_id, $business_id, $provider);
        if (! $credential) {
            $errorMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_ERROR,
                __('projectx::lang.chat_missing_provider_key'),
                $provider,
                $model,
                $user_id,
                $appliedFabricId,
                $appliedFabricInsight
            );

            return [
                'success' => false,
                'error_type' => 'credential_missing',
                'error_message' => __('projectx::lang.chat_missing_provider_key'),
                'warnings' => array_values(array_unique($warnings)),
                'settings' => $settings,
                'provider' => $provider,
                'model' => $model,
                'user_message' => $userMessage,
                'error_message_model' => $errorMessage,
                'applied_fabric_id' => $appliedFabricId,
                'applied_fabric_insight' => $appliedFabricInsight,
            ];
        }

        $messages = $this->chatUtil->buildProviderMessages(
            $conversation,
            (string) ($settings->system_prompt ?? ''),
            $fabricContext !== '' ? $fabricContext : null,
            30,
            (int) $userMessage->id,
            $prompt,
            $appliedFabricId,
            $contextGeneratedAt !== '' ? $contextGeneratedAt : null,
            $user_id,
            $quoteContext !== '' ? $quoteContext : null,
            $appliedQuoteId,
            $trimContext !== '' ? $trimContext : null,
            $appliedTrimId,
            $salesOrderContext !== '' ? $salesOrderContext : null,
            $appliedTransactionId
        );

        return [
            'success' => true,
            'warnings' => array_values(array_unique($warnings)),
            'settings' => $settings,
            'provider' => $provider,
            'model' => $model,
            'messages' => $messages,
            'user_message' => $userMessage,
            'credential' => $credential,
            'applied_fabric_id' => $appliedFabricId,
            'applied_fabric_insight' => $appliedFabricInsight,
        ];
    }

    public function prepareRegenerateContext(
        int $business_id,
        int $user_id,
        ChatConversation $conversation,
        ChatMessage $assistantMessage,
        array $payload
    ): array {
        $settings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $provider = strtolower(trim((string) ($assistantMessage->provider ?: $settings->default_provider ?: config('projectx.chat.default_provider', 'openai'))));
        $model = trim((string) ($assistantMessage->model ?: $settings->default_model ?: config('projectx.chat.default_model', 'gpt-4o-mini')));

        if (! $this->chatUtil->isProviderSupported($provider) || ! $this->chatUtil->isModelAllowedForBusiness($business_id, $provider, $model)) {
            return [
                'success' => false,
                'error_message' => __('projectx::lang.chat_validation_model_invalid'),
            ];
        }

        $sourceUserMessage = $this->chatUtil->getPreviousUserMessage($business_id, $assistantMessage);
        if (! $sourceUserMessage) {
            return [
                'success' => false,
                'error_message' => __('projectx::lang.chat_regenerate_user_prompt_missing'),
            ];
        }

        $piiPolicyResult = $this->chatUtil->applyPiiPolicy((string) $sourceUserMessage->content, $settings);
        if (! empty($piiPolicyResult['blocked'])) {
            return [
                'success' => false,
                'error_message' => __('projectx::lang.chat_blocked_sensitive_data'),
            ];
        }

        $warnings = (array) ($piiPolicyResult['warnings'] ?? []);
        $fabricContextResult = $this->chatUtil->resolveFabricContext($business_id, $payload, $settings);
        $fabricContext = (string) ($fabricContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($fabricContextResult['warnings'] ?? []));
        $contextFabricId = (int) ($fabricContextResult['fabric_id'] ?? 0);
        $contextGeneratedAt = (string) ($fabricContextResult['context_generated_at'] ?? '');
        $appliedFabricId = $fabricContext !== '' && $contextFabricId > 0 ? $contextFabricId : null;
        $appliedFabricInsight = $appliedFabricId !== null
            && $appliedFabricId > 0
            && (bool) ($payload['fabric_insight'] ?? false);

        $payload = $this->chatUtil->enrichPayloadContextIds($business_id, $payload);

        $quoteContextResult = $this->chatUtil->resolveQuoteContext($business_id, $payload, $settings);
        $quoteContext = (string) ($quoteContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($quoteContextResult['warnings'] ?? []));
        $quoteId = (int) ($quoteContextResult['quote_id'] ?? 0);
        $appliedQuoteId = $quoteContext !== '' && $quoteId > 0 ? $quoteId : null;

        $trimContextResult = $this->chatUtil->resolveTrimContext($business_id, $payload, $settings);
        $trimContext = (string) ($trimContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($trimContextResult['warnings'] ?? []));
        $trimId = (int) ($trimContextResult['trim_id'] ?? 0);
        $appliedTrimId = $trimContext !== '' && $trimId > 0 ? $trimId : null;

        $salesOrderContextResult = $this->chatUtil->resolveSalesOrderContext($business_id, $payload, $settings);
        $salesOrderContext = (string) ($salesOrderContextResult['context'] ?? '');
        $warnings = array_merge($warnings, (array) ($salesOrderContextResult['warnings'] ?? []));
        $transactionId = (int) ($salesOrderContextResult['transaction_id'] ?? 0);
        $appliedTransactionId = $salesOrderContext !== '' && $transactionId > 0 ? $transactionId : null;

        $credential = $this->chatUtil->resolveCredentialForChat($user_id, $business_id, $provider);
        if (! $credential) {
            return [
                'success' => false,
                'error_message' => __('projectx::lang.chat_missing_provider_key'),
            ];
        }

        $messages = $this->chatUtil->buildProviderMessages(
            $conversation,
            (string) ($settings->system_prompt ?? ''),
            $fabricContext !== '' ? $fabricContext : null,
            30,
            (int) $sourceUserMessage->id,
            (string) $sourceUserMessage->content,
            $appliedFabricId,
            $contextGeneratedAt !== '' ? $contextGeneratedAt : null,
            $user_id,
            $quoteContext !== '' ? $quoteContext : null,
            $appliedQuoteId,
            $trimContext !== '' ? $trimContext : null,
            $appliedTrimId,
            $salesOrderContext !== '' ? $salesOrderContext : null,
            $appliedTransactionId
        );

        return [
            'success' => true,
            'warnings' => array_values(array_unique($warnings)),
            'settings' => $settings,
            'provider' => $provider,
            'model' => $model,
            'messages' => $messages,
            'credential' => $credential,
            'source_user_message' => $sourceUserMessage,
            'applied_fabric_id' => $appliedFabricId,
            'applied_fabric_insight' => $appliedFabricInsight,
        ];
    }

    public function normalizeAssistantText(string $assistantText, ChatSetting $settings): array
    {
        $normalizedText = trim($assistantText) === ''
            ? __('projectx::lang.chat_provider_empty_response')
            : $assistantText;

        $moderationResult = $this->chatUtil->moderateAssistantText($normalizedText, $settings);

        return [
            'text' => (string) ($moderationResult['text'] ?? $normalizedText),
            'moderated' => ! empty($moderationResult['moderated']),
        ];
    }
}
