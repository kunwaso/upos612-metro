<?php

namespace Modules\Essentials\Utils;

use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;

class TranscriptTranslationUtil
{
    protected ChatUtil $chatUtil;

    protected AIChatUtil $aiChatUtil;

    protected TranscriptUtil $transcriptUtil;

    public function __construct(ChatUtil $chatUtil, AIChatUtil $aiChatUtil, TranscriptUtil $transcriptUtil)
    {
        $this->chatUtil = $chatUtil;
        $this->aiChatUtil = $aiChatUtil;
        $this->transcriptUtil = $transcriptUtil;
    }

    /**
     * Translate text by using the business default provider/model configured in Aichat.
     *
     * @throws \RuntimeException
     */
    public function translateText(
        int $businessId,
        int $userId,
        string $text,
        string $sourceLanguage,
        string $targetLanguage
    ): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (strtolower($sourceLanguage) === strtolower($targetLanguage)) {
            return $text;
        }

        $modelOptions = $this->chatUtil->buildModelOptions($businessId, $userId);
        $provider = (string) ($modelOptions['default_provider'] ?? '');
        $model = (string) ($modelOptions['default_model'] ?? '');

        if ($provider === '' || $model === '') {
            throw new \RuntimeException(__('essentials::lang.translation_provider_unavailable'));
        }

        $credential = $this->chatUtil->resolveCredentialForChat($userId, $businessId, $provider);
        if (empty($credential)) {
            throw new \RuntimeException(__('essentials::lang.translation_provider_key_missing'));
        }

        $apiKey = $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key);
        $sourceLabel = $this->transcriptUtil->getLanguageLabel($sourceLanguage);
        $targetLabel = $this->transcriptUtil->getLanguageLabel($targetLanguage);

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a professional translation assistant. Translate from '
                    . $sourceLabel . ' to ' . $targetLabel
                    . '. Return only the translated text, preserve meaning and line breaks.',
            ],
            [
                'role' => 'user',
                'content' => $text,
            ],
        ];

        $translated = trim($this->aiChatUtil->generateText($provider, $apiKey, $model, $messages));
        if ($translated === '') {
            throw new \RuntimeException(__('essentials::lang.translation_empty_response'));
        }

        return $translated;
    }
}
