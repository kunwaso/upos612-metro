<?php

namespace Modules\Aichat\Utils;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TelegramApiUtil
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function getMe(string $botToken): array
    {
        return $this->requestTelegramApi($botToken, 'getMe');
    }

    public function setWebhook(string $botToken, string $webhookUrl, ?string $secretToken = null): array
    {
        $payload = ['url' => $webhookUrl];
        if (! empty($secretToken)) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->requestTelegramApi($botToken, 'setWebhook', $payload);
    }

    public function deleteWebhook(string $botToken): array
    {
        return $this->requestTelegramApi($botToken, 'deleteWebhook', ['drop_pending_updates' => false]);
    }

    public function sendMessage(string $botToken, int $chatId, string $text, array $options = []): array
    {
        $chunks = $this->chatUtil->splitTelegramMessage($text, 4096);
        $responses = [];

        foreach ($chunks as $chunk) {
            $payload = array_merge($options, [
                'chat_id' => $chatId,
                'text' => $chunk,
            ]);

            $responses[] = $this->requestTelegramApi($botToken, 'sendMessage', $payload);
        }

        return [
            'ok' => true,
            'chunks' => count($responses),
            'responses' => $responses,
        ];
    }

    public function sendChatAction(string $botToken, int $chatId, string $action = 'typing'): array
    {
        return $this->requestTelegramApi($botToken, 'sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    protected function requestTelegramApi(string $botToken, string $method, array $payload = []): array
    {
        $url = 'https://api.telegram.org/bot' . $botToken . '/' . $method;

        $response = $this->buildTelegramRequest()->post($url, $payload);
        if (! $response->successful()) {
            $data = $response->json();
            $description = is_array($data) ? trim((string) ($data['description'] ?? '')) : '';

            if ($description !== '') {
                throw new \RuntimeException($description);
            }

            throw new \RuntimeException('Telegram API request failed with status ' . $response->status() . '.');
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['ok'])) {
            $description = is_array($data) ? (string) ($data['description'] ?? 'Unknown Telegram API error.') : 'Unknown Telegram API error.';
            throw new \RuntimeException($description);
        }

        return $data;
    }

    protected function buildTelegramRequest(): PendingRequest
    {
        $timeout = (int) config('aichat.telegram.request_timeout_seconds', 20);
        $request = Http::timeout(max(5, $timeout))->acceptJson()->asJson();

        if (! (bool) config('aichat.telegram.verify_ssl', true)) {
            return $request->withOptions(['verify' => false]);
        }

        $caBundle = trim((string) config('aichat.telegram.ca_bundle', ''));
        if ($caBundle === '') {
            return $request;
        }

        if (! is_file($caBundle)) {
            throw new \RuntimeException('Configured Telegram CA bundle file was not found: ' . $caBundle);
        }

        return $request->withOptions(['verify' => $caBundle]);
    }
}
