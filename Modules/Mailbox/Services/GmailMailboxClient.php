<?php

namespace Modules\Mailbox\Services;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\ModifyMessageRequest;
use Illuminate\Support\Arr;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Modules\Mailbox\Entities\MailboxAccount;

class GmailMailboxClient
{
    public function redirectResponse()
    {
        return Socialite::driver('google')
            ->scopes((array) config('mailbox.gmail.scopes', []))
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function getUserFromCallback(): SocialiteUser
    {
        return Socialite::driver('google')
            ->scopes((array) config('mailbox.gmail.scopes', []))
            ->user();
    }

    public function getProfile(MailboxAccount $account): array
    {
        $service = $this->service($account);
        $profile = $service->users->getProfile('me');

        return [
            'email_address' => (string) $profile->getEmailAddress(),
            'history_id' => (string) $profile->getHistoryId(),
            'messages_total' => (int) $profile->getMessagesTotal(),
            'threads_total' => (int) $profile->getThreadsTotal(),
        ];
    }

    public function fetchMessages(MailboxAccount $account, int $batchSize): array
    {
        $cursor = (array) ($account->sync_cursor_json ?? []);
        $historyId = Arr::get($cursor, 'gmail_history_id');

        if (empty($historyId)) {
            return $this->initialBackfill($account, $batchSize);
        }

        try {
            $service = $this->service($account);
            $response = $service->users_history->listUsersHistory('me', [
                'startHistoryId' => $historyId,
                'maxResults' => min(500, max(1, $batchSize)),
                'historyTypes' => ['messageAdded', 'labelsAdded', 'labelsRemoved'],
            ]);

            $messageIds = [];
            foreach ((array) $response->getHistory() as $history) {
                foreach ((array) $history->getMessagesAdded() as $item) {
                    $message = $item->getMessage();
                    if ($message && $message->getId()) {
                        $messageIds[] = (string) $message->getId();
                    }
                }

                foreach ((array) $history->getLabelsAdded() as $item) {
                    $message = $item->getMessage();
                    if ($message && $message->getId()) {
                        $messageIds[] = (string) $message->getId();
                    }
                }

                foreach ((array) $history->getLabelsRemoved() as $item) {
                    $message = $item->getMessage();
                    if ($message && $message->getId()) {
                        $messageIds[] = (string) $message->getId();
                    }
                }
            }

            $messageIds = array_values(array_unique($messageIds));
            $messages = [];
            foreach ($messageIds as $messageId) {
                $messages[] = $this->getMessage($account, $messageId);
            }

            return [
                'messages' => $messages,
                'cursor' => array_merge($cursor, ['gmail_history_id' => (string) $response->getHistoryId()]),
            ];
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() === 404) {
                return $this->initialBackfill($account, $batchSize);
            }

            throw $exception;
        }
    }

    public function getMessage(MailboxAccount $account, string $messageId): GmailMessage
    {
        return $this->service($account)->users_messages->get('me', $messageId, [
            'format' => 'full',
        ]);
    }

    public function downloadAttachment(MailboxAccount $account, string $messageId, string $attachmentId): array
    {
        $body = $this->service($account)->users_messages_attachments->get('me', $messageId, $attachmentId);
        $content = $this->decodeBase64Url((string) $body->getData());

        return [
            'content' => $content,
            'size' => strlen($content),
        ];
    }

    public function setReadState(MailboxAccount $account, string $messageId, bool $read): void
    {
        $this->modifyLabels($account, $messageId, $read ? [] : ['UNREAD'], $read ? ['UNREAD'] : []);
    }

    public function setStarState(MailboxAccount $account, string $messageId, bool $starred): void
    {
        $this->modifyLabels($account, $messageId, $starred ? ['STARRED'] : [], $starred ? [] : ['STARRED']);
    }

    public function moveToTrash(MailboxAccount $account, string $messageId): void
    {
        $this->service($account)->users_messages->trash('me', $messageId);
    }

    public function sendMessage(MailboxAccount $account, string $rawMime, ?string $threadId = null): GmailMessage
    {
        $message = new GmailMessage();
        $message->setRaw($this->encodeBase64Url($rawMime));

        if (! empty($threadId)) {
            $message->setThreadId($threadId);
        }

        return $this->service($account)->users_messages->send('me', $message);
    }

    public function refreshAccessToken(MailboxAccount $account): array
    {
        $client = $this->makeClient();
        $refreshToken = (string) $account->encrypted_refresh_token;
        if ($refreshToken === '') {
            throw new \RuntimeException('Missing Gmail refresh token.');
        }

        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (isset($token['error'])) {
            throw new \RuntimeException((string) ($token['error_description'] ?? $token['error']));
        }

        $account->forceFill([
            'encrypted_access_token' => (string) ($token['access_token'] ?? ''),
            'encrypted_refresh_token' => (string) ($token['refresh_token'] ?? $refreshToken),
            'token_expires_at' => isset($token['expires_in']) ? Carbon::now()->addSeconds((int) $token['expires_in']) : null,
        ])->save();

        return $token;
    }

    public function latestHistoryId(GmailMessage $message): ?string
    {
        return $message->getHistoryId() ? (string) $message->getHistoryId() : null;
    }

    public function topLevelHeaders(GmailMessage $message): array
    {
        $headers = [];
        $payload = $message->getPayload();
        if (! $payload) {
            return $headers;
        }

        foreach ((array) $payload->getHeaders() as $header) {
            $headers[strtolower((string) $header->getName())] = (string) $header->getValue();
        }

        return $headers;
    }

    public function collectBodyParts(?MessagePart $part, array &$textParts = [], array &$htmlParts = [], array &$attachments = []): void
    {
        if (! $part) {
            return;
        }

        $mimeType = strtolower((string) $part->getMimeType());
        $body = $part->getBody();
        $filename = (string) $part->getFilename();

        if ($mimeType === 'text/plain' && $body && $body->getData()) {
            $textParts[] = $this->decodeBase64Url((string) $body->getData());
        }

        if ($mimeType === 'text/html' && $body && $body->getData()) {
            $htmlParts[] = $this->decodeBase64Url((string) $body->getData());
        }

        if ($filename !== '' || ($body && $body->getAttachmentId())) {
            $attachments[] = [
                'provider_attachment_id' => $body ? (string) $body->getAttachmentId() : null,
                'filename' => $filename !== '' ? $filename : ('attachment-' . ($part->getPartId() ?: uniqid())),
                'mime_type' => $mimeType ?: 'application/octet-stream',
                'size_bytes' => $body ? (int) $body->getSize() : null,
                'part_id' => $part->getPartId() ? (string) $part->getPartId() : null,
                'content_id' => $this->headerValue($part, 'content-id'),
                'is_inline' => stripos((string) $this->headerValue($part, 'content-disposition'), 'inline') !== false,
            ];
        }

        foreach ((array) $part->getParts() as $childPart) {
            $this->collectBodyParts($childPart, $textParts, $htmlParts, $attachments);
        }
    }

    protected function initialBackfill(MailboxAccount $account, int $batchSize): array
    {
        $messages = [];
        foreach (['INBOX', 'SENT'] as $labelId) {
            foreach ($this->listMessageIdsByLabel($account, $labelId, $batchSize) as $messageId) {
                $messages[$messageId] = $this->getMessage($account, $messageId);
            }
        }

        $profile = $this->getProfile($account);

        return [
            'messages' => array_values($messages),
            'cursor' => array_merge((array) ($account->sync_cursor_json ?? []), ['gmail_history_id' => (string) ($profile['history_id'] ?? '')]),
        ];
    }

    protected function listMessageIdsByLabel(MailboxAccount $account, string $labelId, int $batchSize): array
    {
        $response = $this->service($account)->users_messages->listUsersMessages('me', [
            'labelIds' => [$labelId],
            'maxResults' => min(500, max(1, $batchSize)),
            'includeSpamTrash' => $labelId === 'TRASH',
        ]);

        $ids = [];
        foreach ((array) $response->getMessages() as $message) {
            if ($message->getId()) {
                $ids[] = (string) $message->getId();
            }
        }

        return $ids;
    }

    protected function modifyLabels(MailboxAccount $account, string $messageId, array $addLabels = [], array $removeLabels = []): void
    {
        $request = new ModifyMessageRequest();
        $request->setAddLabelIds($addLabels);
        $request->setRemoveLabelIds($removeLabels);

        $this->service($account)->users_messages->modify('me', $messageId, $request);
    }

    protected function service(MailboxAccount $account): Gmail
    {
        return new Gmail($this->authenticatedClient($account));
    }

    protected function authenticatedClient(MailboxAccount $account): GoogleClient
    {
        $client = $this->makeClient();
        $accessToken = (string) ($account->encrypted_access_token ?? '');

        if ($accessToken !== '') {
            $client->setAccessToken([
                'access_token' => $accessToken,
                'refresh_token' => (string) ($account->encrypted_refresh_token ?? ''),
                'expires_in' => $account->token_expires_at ? max(0, Carbon::now()->diffInSeconds($account->token_expires_at, false)) : null,
                'created' => $account->token_expires_at ? max(0, $account->token_expires_at->copy()->subSeconds(max(0, Carbon::now()->diffInSeconds($account->token_expires_at, false)))->timestamp) : time(),
            ]);
        }

        if ($account->token_expires_at && $account->token_expires_at->isFuture() && $accessToken !== '') {
            return $client;
        }

        if (! empty($account->encrypted_refresh_token)) {
            $token = $this->refreshAccessToken($account);
            $client->setAccessToken($token);
        }

        return $client;
    }

    protected function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri((string) config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    protected function headerValue(MessagePart $part, string $name): ?string
    {
        foreach ((array) $part->getHeaders() as $header) {
            if (strtolower((string) $header->getName()) === strtolower($name)) {
                return (string) $header->getValue();
            }
        }

        return null;
    }

    protected function decodeBase64Url(string $data): string
    {
        $normalized = strtr($data, '-_', '+/');
        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        return (string) base64_decode($normalized);
    }

    protected function encodeBase64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
