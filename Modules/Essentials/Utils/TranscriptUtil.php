<?php

namespace Modules\Essentials\Utils;

use Illuminate\Support\Facades\Http;

class TranscriptUtil
{
    /**
     * Language metadata used across the transcript feature.
     * - transcription: ISO code for STT provider
     * - speech: locale for browser SpeechRecognition
     */
    protected const LANGUAGE_CODE_MAP = [
        'en' => ['transcription' => 'en', 'speech' => 'en-US'],
        'es' => ['transcription' => 'es', 'speech' => 'es-ES'],
        'sq' => ['transcription' => 'sq', 'speech' => 'sq-AL'],
        'hi' => ['transcription' => 'hi', 'speech' => 'hi-IN'],
        'nl' => ['transcription' => 'nl', 'speech' => 'nl-NL'],
        'fr' => ['transcription' => 'fr', 'speech' => 'fr-FR'],
        'de' => ['transcription' => 'de', 'speech' => 'de-DE'],
        'ar' => ['transcription' => 'ar', 'speech' => 'ar-SA'],
        'tr' => ['transcription' => 'tr', 'speech' => 'tr-TR'],
        'id' => ['transcription' => 'id', 'speech' => 'id-ID'],
        'ps' => ['transcription' => 'ps', 'speech' => 'ps-AF'],
        'pt' => ['transcription' => 'pt', 'speech' => 'pt-PT'],
        'vi' => ['transcription' => 'vi', 'speech' => 'vi-VN'],
        // App locale key for Chinese in this project is `ce`.
        'ce' => ['transcription' => 'zh', 'speech' => 'zh-CN'],
        'ro' => ['transcription' => 'ro', 'speech' => 'ro-RO'],
        'lo' => ['transcription' => 'lo', 'speech' => 'lo-LA'],
        'he' => ['transcription' => 'he', 'speech' => 'he-IL'],
    ];

    /**
     * Transcribe an audio file using the Groq Whisper API.
     *
     * @param  string  $filePath  Absolute path to the audio file on disk
     * @param  string  $apiKey    Groq API key (Bearer token)
     * @param  string|null $language Optional ISO language code for STT
     * @return string             Transcribed text
     *
     * @throws \RuntimeException  On API failure or unexpected response
     */
    public function transcribe(string $filePath, string $apiKey, ?string $language = null): string
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException('Audio file not found: ' . $filePath);
        }

        $payload = [
            'model' => 'whisper-large-v3-turbo',
            'response_format' => 'json',
        ];

        if (! empty($language)) {
            $payload['language'] = $language;
        }

        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', $payload);

        if (! $response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Groq API error: ' . $errorMessage);
        }

        $text = $response->json('text');

        if (! is_string($text)) {
            throw new \RuntimeException('Groq API returned an unexpected response format.');
        }

        return trim($text);
    }

    /**
     * Retrieve the Groq API key for a given business from essentials settings.
     *
     * @param  int  $business_id
     * @return string|null
     */
    public function getApiKey(int $business_id): ?string
    {
        $settings = session('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        $key = $settings['groq_api_key'] ?? null;

        if (empty($key)) {
            $key = env('GROQ_API_KEY') ?: null;
        }

        return $key;
    }

    /**
     * UI language options sourced from application language config.
     */
    public function getLanguageOptions(): array
    {
        $langs = (array) config('constants.langs', []);
        $options = [];
        foreach ($langs as $key => $meta) {
            $label = (string) data_get($meta, 'full_name', strtoupper((string) $key));
            $options[(string) $key] = $label;
        }

        return $options;
    }

    /**
     * Return speech-recognition locales keyed by app language code.
     */
    public function getSpeechRecognitionLocales(): array
    {
        $locales = [];
        foreach (self::LANGUAGE_CODE_MAP as $key => $config) {
            $locales[$key] = (string) ($config['speech'] ?? 'en-US');
        }

        return $locales;
    }

    /**
     * Return STT language codes keyed by app language code.
     */
    public function getTranscriptionLanguageCodes(): array
    {
        $codes = [];
        foreach (self::LANGUAGE_CODE_MAP as $key => $config) {
            $codes[$key] = (string) ($config['transcription'] ?? 'en');
        }

        return $codes;
    }

    public function resolveSpeechRecognitionLocale(?string $language): string
    {
        $language = strtolower((string) $language);

        return (string) data_get(self::LANGUAGE_CODE_MAP, $language . '.speech', 'en-US');
    }

    public function resolveTranscriptionLanguage(?string $language): string
    {
        $language = strtolower((string) $language);

        return (string) data_get(self::LANGUAGE_CODE_MAP, $language . '.transcription', 'en');
    }

    public function getLanguageLabel(?string $language): string
    {
        $language = strtolower((string) $language);
        $options = $this->getLanguageOptions();

        return (string) ($options[$language] ?? strtoupper($language));
    }
}
