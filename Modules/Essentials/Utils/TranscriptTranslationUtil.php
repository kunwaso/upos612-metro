<?php

namespace Modules\Essentials\Utils;

use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;

class TranscriptTranslationUtil
{
    protected const LIGHTWEIGHT_MODEL_HINTS = [
        'flash',
        'mini',
        'lite',
        'instant',
        'free',
    ];

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
        $candidates = $this->buildTranslationCandidates($modelOptions);
        if (empty($candidates)) {
            throw new \RuntimeException(__('essentials::lang.translation_provider_unavailable'));
        }
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

        $lastException = null;
        $missingCredentialCount = 0;

        foreach ($candidates as $candidate) {
            $provider = (string) ($candidate['provider'] ?? '');
            $model = (string) ($candidate['model'] ?? '');
            if ($provider === '' || $model === '') {
                continue;
            }

            $credential = $this->chatUtil->resolveCredentialForChat($userId, $businessId, $provider);
            if (empty($credential)) {
                $missingCredentialCount++;
                continue;
            }

            try {
                $apiKey = $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key);
                $translated = trim($this->aiChatUtil->generateText($provider, $apiKey, $model, $messages));
                if ($translated === '') {
                    throw new \RuntimeException(__('essentials::lang.translation_empty_response'));
                }

                return $translated;
            } catch (\RuntimeException $exception) {
                $lastException = $exception;
                if (! $this->shouldTryNextCandidate($exception->getMessage())) {
                    throw $exception;
                }
            }
        }

        if ($lastException instanceof \RuntimeException) {
            throw $lastException;
        }

        if ($missingCredentialCount > 0) {
            throw new \RuntimeException(__('essentials::lang.translation_provider_key_missing'));
        }

        throw new \RuntimeException(__('essentials::lang.translation_provider_unavailable'));
    }

    protected function buildTranslationCandidates(array $modelOptions): array
    {
        $defaultProvider = strtolower((string) ($modelOptions['default_provider'] ?? ''));
        $defaultModel = (string) ($modelOptions['default_model'] ?? '');
        $options = collect((array) ($modelOptions['model_options'] ?? []))
            ->map(function ($option) {
                return [
                    'provider' => strtolower((string) ($option['provider'] ?? '')),
                    'model' => (string) ($option['model_id'] ?? ''),
                ];
            })
            ->filter(function ($option) {
                return $option['provider'] !== '' && $option['model'] !== '';
            })
            ->values();

        $candidates = collect();

        if ($defaultProvider !== '' && $defaultModel !== '') {
            $candidates->push([
                'provider' => $defaultProvider,
                'model' => $defaultModel,
            ]);
        }

        $sameProviderOptions = $options->filter(function ($option) use ($defaultProvider, $defaultModel) {
            return $option['provider'] === $defaultProvider
                && $option['model'] !== $defaultModel;
        })->values();

        $crossProviderOptions = $options->filter(function ($option) use ($defaultProvider) {
            return $option['provider'] !== $defaultProvider;
        })->values();

        $preferredSameProvider = $this->pickPreferredOption($sameProviderOptions);
        if (! empty($preferredSameProvider)) {
            $candidates->push($preferredSameProvider);
        } elseif ($sameProviderOptions->isNotEmpty()) {
            $candidates->push($sameProviderOptions->first());
        }

        $preferredCrossProvider = $this->pickPreferredOption($crossProviderOptions);
        if (! empty($preferredCrossProvider)) {
            $candidates->push($preferredCrossProvider);
        } elseif ($crossProviderOptions->isNotEmpty()) {
            $candidates->push($crossProviderOptions->first());
        }

        if ($candidates->isEmpty()) {
            $fallback = $this->pickPreferredOption($options);
            if (! empty($fallback)) {
                $candidates->push($fallback);
            }
        }

        return $candidates
            ->unique(function ($option) {
                return $option['provider'] . '|' . $option['model'];
            })
            ->values()
            ->all();
    }

    protected function pickPreferredOption($options): array
    {
        foreach ($options as $option) {
            $model = strtolower((string) ($option['model'] ?? ''));
            foreach (self::LIGHTWEIGHT_MODEL_HINTS as $hint) {
                if (str_contains($model, $hint)) {
                    return $option;
                }
            }
        }

        return [];
    }

    protected function shouldTryNextCandidate(string $message): bool
    {
        $lower = strtolower(trim($message));
        if ($lower === '') {
            return true;
        }

        return str_contains($lower, 'quota')
            || str_contains($lower, 'rate limit')
            || str_contains($lower, 'too many requests')
            || str_contains($lower, 'resource has been exhausted')
            || str_contains($lower, 'model')
            || str_contains($lower, 'provider request failed')
            || str_contains($lower, 'provider rejected the api key')
            || str_contains($lower, 'empty response');
    }
}
