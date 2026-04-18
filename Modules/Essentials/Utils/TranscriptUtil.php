<?php

namespace Modules\Essentials\Utils;

use Illuminate\Support\Facades\Http;

class TranscriptUtil
{
    protected const DEFAULT_TRANSLATION_ENGINE = 'py_googletrans';

    protected const TRANSLATION_ENGINES = [
        'py_googletrans',
        'aichat',
    ];

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
     * App locale key -> py-googletrans language code mapping.
     */
    protected const PY_TRANSLATION_LANGUAGE_MAP = [
        'ce' => 'zh-cn',
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
        $settings = $this->getEssentialsSettings($business_id);

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

    public function getTranslationEngine(int $business_id): string
    {
        $settings = $this->getEssentialsSettings($business_id);
        $defaultEngine = strtolower((string) env('ESSENTIALS_TRANSCRIPT_TRANSLATION_DEFAULT_ENGINE', self::DEFAULT_TRANSLATION_ENGINE));
        if (! in_array($defaultEngine, self::TRANSLATION_ENGINES, true)) {
            $defaultEngine = self::DEFAULT_TRANSLATION_ENGINE;
        }

        $engine = strtolower(trim((string) ($settings['transcript_translation_engine'] ?? $defaultEngine)));
        if (! in_array($engine, self::TRANSLATION_ENGINES, true)) {
            return self::DEFAULT_TRANSLATION_ENGINE;
        }

        return $engine;
    }

    public function getTranslationPythonBinary(int $business_id): string
    {
        $settings = $this->getEssentialsSettings($business_id);
        $binary = trim((string) ($settings['transcript_translation_python_bin'] ?? env('ESSENTIALS_TRANSCRIPT_PYTHON_BIN', 'python')));

        return $binary !== '' ? $binary : 'python';
    }

    public function getTranslationPythonScriptPath(int $business_id): string
    {
        $settings = $this->getEssentialsSettings($business_id);
        $scriptPath = trim((string) ($settings['transcript_translation_py_script'] ?? env('ESSENTIALS_TRANSCRIPT_PY_SCRIPT', 'scripts/transcript_translate_google.py')));

        if ($scriptPath === '') {
            $scriptPath = 'scripts/transcript_translate_google.py';
        }

        if ($this->isAbsolutePath($scriptPath)) {
            return $scriptPath;
        }

        return base_path($scriptPath);
    }

    public function getTranslationTimeoutSeconds(int $business_id): int
    {
        $settings = $this->getEssentialsSettings($business_id);
        $timeout = (int) ($settings['transcript_translation_timeout_seconds'] ?? env('ESSENTIALS_TRANSCRIPT_TRANSLATION_TIMEOUT_SECONDS', 8));

        return max(1, min($timeout, 120));
    }

    public function getTranslationCacheTtlSeconds(int $business_id): int
    {
        $settings = $this->getEssentialsSettings($business_id);
        $ttl = (int) ($settings['transcript_translation_cache_ttl_seconds'] ?? env('ESSENTIALS_TRANSCRIPT_TRANSLATION_CACHE_TTL_SECONDS', 600));

        return max(0, min($ttl, 86400));
    }

    public function getTranslationPythonServiceUrls(int $business_id): array
    {
        $settings = $this->getEssentialsSettings($business_id);
        $settingsUrls = $settings['transcript_translation_py_service_urls'] ?? null;
        $envUrls = env('ESSENTIALS_TRANSCRIPT_PY_SERVICE_URLS', '');

        $urls = $this->parseServiceUrlList($settingsUrls);
        if (! empty($urls)) {
            return $urls;
        }

        return $this->parseServiceUrlList($envUrls);
    }

    public function resolvePyTranslationLanguage(?string $language): string
    {
        $language = strtolower(trim((string) $language));
        if ($language === '') {
            return 'auto';
        }

        return (string) (self::PY_TRANSLATION_LANGUAGE_MAP[$language] ?? $language);
    }

    public function buildTranslationCacheKey(string $engine, string $sourceLanguage, string $targetLanguage, string $text): string
    {
        return sprintf(
            'essentials:transcripts:translate:%s:%s:%s:%s',
            strtolower(trim($engine)),
            strtolower(trim($sourceLanguage)),
            strtolower(trim($targetLanguage)),
            hash('sha256', trim($text))
        );
    }

    protected function getEssentialsSettings(int $business_id): array
    {
        $sessionSettings = [
            session('business.essentials_settings'),
            data_get(session('business'), 'essentials_settings'),
        ];

        foreach ($sessionSettings as $candidate) {
            if (is_array($candidate)) {
                return $candidate;
            }

            if (is_string($candidate) && trim($candidate) !== '') {
                $decoded = json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    protected function parseServiceUrlList($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        $urls = [];
        foreach ($value as $item) {
            $url = trim((string) $item);
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $urls[] = $url;
        }

        return array_values(array_unique($urls));
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
