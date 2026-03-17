<?php

namespace Modules\Aichat\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AIChatUtil
{
    protected Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'http_errors' => false,
        ]);
    }

    public function generateText(string $provider, string $apiKey, string $model, array $messages): string
    {
        $provider = strtolower(trim($provider));

        if ($provider === 'gemini') {
            return $this->generateTextWithGemini($apiKey, $model, $messages);
        }

        return $this->generateTextWithOpenAiCompatible($provider, $apiKey, $model, $messages);
    }

    public function streamText(string $provider, string $apiKey, string $model, array $messages): \Generator
    {
        $provider = strtolower(trim($provider));

        if ($provider === 'gemini') {
            yield from $this->streamTextWithGemini($apiKey, $model, $messages);

            return;
        }

        yield from $this->streamTextWithOpenAiCompatible($provider, $apiKey, $model, $messages);
    }

    protected function generateTextWithGemini(string $apiKey, string $model, array $messages): string
    {
        $url = rtrim((string) config('aichat.chat.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/')
            . '/models/' . $model . ':generateContent';

        $payload = [
            'contents' => $this->messagesToGeminiContents($messages),
        ];

        $response = $this->requestJson('POST', $url, [
            'headers' => [
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => (int) config('aichat.chat.request_timeout_seconds', 120),
        ]);

        $texts = $this->extractGeminiTexts($response['data']);

        return trim(implode('', $texts));
    }

    protected function generateTextWithOpenAiCompatible(string $provider, string $apiKey, string $model, array $messages): string
    {
        $url = rtrim((string) config("aichat.chat.providers.{$provider}.base_url"), '/') . '/chat/completions';

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
            $headers['X-Title'] = config('app.name');
        }

        $response = $this->requestJson('POST', $url, [
            'headers' => $headers,
            'json' => [
                'model' => $model,
                'messages' => $messages,
            ],
            'timeout' => (int) config('aichat.chat.request_timeout_seconds', 120),
        ]);

        $data = $response['data'];
        $content = data_get($data, 'choices.0.message.content', '');
        if (is_array($content)) {
            $chunks = [];
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text'])) {
                    $chunks[] = (string) $part['text'];
                }
            }
            $content = implode('', $chunks);
        }

        return trim((string) $content);
    }

    protected function streamTextWithGemini(string $apiKey, string $model, array $messages): \Generator
    {
        $url = rtrim((string) config('aichat.chat.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/')
            . '/models/' . $model . ':streamGenerateContent?alt=sse';

        $payload = [
            'contents' => $this->messagesToGeminiContents($messages),
        ];

        $response = $this->requestStream('POST', $url, [
            'headers' => [
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => (int) config('aichat.chat.stream_timeout_seconds', 180),
        ]);

        $body = $response->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            while (($lineBreak = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $lineBreak));
                $buffer = substr($buffer, $lineBreak + 1);

                if ($line === '' || stripos($line, 'data:') !== 0) {
                    continue;
                }

                $jsonLine = trim(substr($line, 5));
                if ($jsonLine === '' || $jsonLine === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($jsonLine, true);
                if (! is_array($chunk)) {
                    continue;
                }

                foreach ($this->extractGeminiTexts($chunk) as $text) {
                    yield $text;
                }
            }
        }
    }

    protected function streamTextWithOpenAiCompatible(string $provider, string $apiKey, string $model, array $messages): \Generator
    {
        $url = rtrim((string) config("aichat.chat.providers.{$provider}.base_url"), '/') . '/chat/completions';

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
            $headers['X-Title'] = config('app.name');
        }

        $response = $this->requestStream('POST', $url, [
            'headers' => $headers,
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
            ],
            'timeout' => (int) config('aichat.chat.stream_timeout_seconds', 180),
        ]);

        $body = $response->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            while (($lineBreak = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $lineBreak));
                $buffer = substr($buffer, $lineBreak + 1);

                if ($line === '' || stripos($line, 'data:') !== 0) {
                    continue;
                }

                $jsonLine = trim(substr($line, 5));
                if ($jsonLine === '' || $jsonLine === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($jsonLine, true);
                if (! is_array($chunk)) {
                    continue;
                }

                $delta = data_get($chunk, 'choices.0.delta.content');
                if (is_string($delta) && $delta !== '') {
                    yield $delta;
                    continue;
                }

                if (is_array($delta)) {
                    foreach ($delta as $part) {
                        if (is_array($part) && isset($part['text']) && $part['text'] !== '') {
                            yield (string) $part['text'];
                        }
                    }
                }
            }
        }
    }

    protected function requestJson(string $method, string $url, array $options): array
    {
        $options = $this->applySslOptions($options);

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }

        $status = (int) $response->getStatusCode();
        $raw = (string) $response->getBody();
        $data = json_decode($raw, true);

        if ($status >= 400) {
            throw new \RuntimeException($this->friendlyProviderError($data, $raw));
        }

        if (! is_array($data)) {
            throw new \RuntimeException(__('aichat::lang.chat_provider_invalid_response'));
        }

        return [
            'status' => $status,
            'data' => $data,
        ];
    }

    protected function requestStream(string $method, string $url, array $options)
    {
        $options['stream'] = true;
        $options = $this->applySslOptions($options);

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }

        $status = (int) $response->getStatusCode();
        if ($status >= 400) {
            $raw = (string) $response->getBody();
            $data = json_decode($raw, true);
            throw new \RuntimeException($this->friendlyProviderError($data, $raw));
        }

        return $response;
    }

    protected function applySslOptions(array $options): array
    {
        if (! (bool) config('aichat.chat.verify_ssl', true)) {
            $options['verify'] = false;

            return $options;
        }

        $caBundle = trim((string) config('aichat.chat.ca_bundle', ''));
        if ($caBundle === '') {
            return $options;
        }

        if (! is_file($caBundle)) {
            throw new \RuntimeException('Configured AI chat CA bundle file was not found: ' . $caBundle);
        }

        $options['verify'] = $caBundle;

        return $options;
    }

    protected function messagesToGeminiContents(array $messages): array
    {
        $contents = [];
        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $content = (string) ($message['content'] ?? '');

            if ($content === '') {
                continue;
            }

            if ($role === 'assistant') {
                $contents[] = [
                    'role' => 'model',
                    'parts' => [
                        ['text' => $content],
                    ],
                ];
                continue;
            }

            if ($role === 'system') {
                $content = 'System instruction: ' . $content;
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $content],
                ],
            ];
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Hello'],
                ],
            ];
        }

        return $contents;
    }

    protected function extractGeminiTexts($payload): array
    {
        $responses = [];
        if (is_array($payload) && isset($payload['candidates'])) {
            $responses[] = $payload;
        } elseif (is_array($payload) && array_values($payload) === $payload) {
            $responses = $payload;
        }

        $texts = [];
        foreach ($responses as $response) {
            $candidates = $response['candidates'] ?? [];
            if (! is_array($candidates)) {
                continue;
            }

            foreach ($candidates as $candidate) {
                $parts = data_get($candidate, 'content.parts', []);
                if (! is_array($parts)) {
                    continue;
                }

                foreach ($parts as $part) {
                    if (is_array($part) && isset($part['text']) && is_string($part['text']) && $part['text'] !== '') {
                        $texts[] = $part['text'];
                    }
                }
            }
        }

        return $texts;
    }

    protected function friendlyProviderError($jsonPayload, string $rawPayload): string
    {
        $message = (string) data_get($jsonPayload, 'error.message', '');
        if ($message === '') {
            $message = trim($rawPayload);
        }

        $lower = strtolower($message);
        if (
            str_contains($lower, 'quota')
            || str_contains($lower, 'rate limit')
            || str_contains($lower, 'too many requests')
            || str_contains($lower, 'resource has been exhausted')
            || str_contains($lower, 'exceeded your current quota')
        ) {
            return __('aichat::lang.chat_provider_quota_exceeded');
        }
        if (str_contains($lower, 'invalid api key') || str_contains($lower, 'api key not valid')) {
            return __('aichat::lang.chat_provider_invalid_key');
        }
        if (str_contains($lower, 'model') && str_contains($lower, 'not found')) {
            return __('aichat::lang.chat_provider_model_not_found');
        }

        return $message !== '' ? $message : __('aichat::lang.chat_provider_error');
    }
}

