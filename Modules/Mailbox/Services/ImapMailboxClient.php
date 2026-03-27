<?php

namespace Modules\Mailbox\Services;

use Illuminate\Support\Arr;
use Modules\Mailbox\Entities\MailboxAccount;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

class ImapMailboxClient
{
    public function testConnection(array $payload): array
    {
        $client = $this->makeClient($payload);
        $client->connect();

        $inboxFolder = $client->getFolder((string) ($payload['imap_inbox_folder'] ?? config('mailbox.imap.default_inbox_folder', 'INBOX')));
        $sentFolder = $client->getFolder((string) ($payload['imap_sent_folder'] ?? config('mailbox.imap.default_sent_folder', 'Sent')), null, false);

        return [
            'inbox_exists' => $inboxFolder !== null,
            'sent_exists' => $sentFolder !== null,
        ];
    }

    public function fetchMessages(MailboxAccount $account, int $limit): array
    {
        $cursor = (array) ($account->sync_cursor_json ?? []);
        $folders = $this->folderMap($account);
        $messages = [];

        $client = $this->makeClient([
            'imap_host' => $account->imap_host,
            'imap_port' => $account->imap_port,
            'imap_encryption' => $account->imap_encryption,
            'imap_username' => $account->imap_username,
            'imap_password' => $account->encrypted_imap_password,
        ]);
        $client->connect();

        foreach ($folders as $folderKey => $folderPath) {
            if ($folderPath === null || $folderPath === '') {
                continue;
            }

            $folder = $client->getFolder($folderPath, null, false);
            if (! $folder) {
                continue;
            }

            $imapMessages = $folder->messages()
                ->all()
                ->setFetchOrder('desc')
                ->setSequence(IMAP::ST_UID)
                ->limit(max(1, $limit))
                ->get();

            $lastUid = (int) Arr::get($cursor, 'imap.' . $folderKey . '.last_uid', 0);
            $maxUid = $lastUid;

            foreach ($imapMessages as $imapMessage) {
                $uid = (int) $imapMessage->getUid();
                $maxUid = max($maxUid, $uid);

                if ($lastUid > 0 && $uid <= $lastUid) {
                    continue;
                }

                $messages[] = [
                    'folder_key' => $folderKey,
                    'folder_path' => $folderPath,
                    'message' => $imapMessage,
                ];
            }

            $cursor['imap'][$folderKey]['path'] = $folderPath;
            $cursor['imap'][$folderKey]['last_uid'] = $maxUid;
        }

        return [
            'messages' => $messages,
            'cursor' => $cursor,
        ];
    }

    public function getMessageByProviderId(MailboxAccount $account, string $providerMessageId): ?Message
    {
        [$folderPath, $uid] = $this->decodeProviderMessageId($providerMessageId);
        if ($folderPath === '' || $uid <= 0) {
            return null;
        }

        $client = $this->makeClient([
            'imap_host' => $account->imap_host,
            'imap_port' => $account->imap_port,
            'imap_encryption' => $account->imap_encryption,
            'imap_username' => $account->imap_username,
            'imap_password' => $account->encrypted_imap_password,
        ]);
        $client->connect();

        $folder = $client->getFolder($folderPath, null, false);
        if (! $folder) {
            return null;
        }

        return $folder->messages()
            ->whereUid((string) $uid)
            ->setSequence(IMAP::ST_UID)
            ->setFetchOrder('desc')
            ->limit(1)
            ->get()
            ->first();
    }

    public function setReadState(MailboxAccount $account, string $providerMessageId, bool $read): void
    {
        $message = $this->getMessageByProviderId($account, $providerMessageId);
        if (! $message) {
            return;
        }

        if ($read) {
            $message->setFlag('Seen');

            return;
        }

        $message->unsetFlag('Seen');
    }

    public function setStarState(MailboxAccount $account, string $providerMessageId, bool $starred): void
    {
        $message = $this->getMessageByProviderId($account, $providerMessageId);
        if (! $message) {
            return;
        }

        if ($starred) {
            $message->setFlag('Flagged');

            return;
        }

        $message->unsetFlag('Flagged');
    }

    public function moveToTrash(MailboxAccount $account, string $providerMessageId): void
    {
        [$folderPath] = $this->decodeProviderMessageId($providerMessageId);
        $message = $this->getMessageByProviderId($account, $providerMessageId);
        if (! $message) {
            return;
        }

        $trashFolder = (string) ($account->imap_trash_folder ?: config('mailbox.imap.default_trash_folder', 'Trash'));
        if ($trashFolder !== '' && strtolower($folderPath) !== strtolower($trashFolder)) {
            $message->move($trashFolder);

            return;
        }

        $message->setFlag('Deleted');
    }

    public function downloadAttachment(MailboxAccount $account, string $providerMessageId, string $providerAttachmentId): array
    {
        $message = $this->getMessageByProviderId($account, $providerMessageId);
        if (! $message) {
            throw new \RuntimeException('Attachment source message was not found.');
        }

        foreach ($message->getAttachments() as $attachment) {
            if ((string) $attachment->getPartNumber() !== (string) $providerAttachmentId) {
                continue;
            }

            $content = (string) $attachment->getContent();

            return [
                'content' => $content,
                'size' => strlen($content),
                'mime_type' => (string) $attachment->getContentType(),
            ];
        }

        throw new \RuntimeException('Attachment was not found on the mail server.');
    }

    public function encodeProviderMessageId(string $folderPath, int $uid): string
    {
        return base64_encode($folderPath) . '::' . $uid;
    }

    public function decodeProviderMessageId(string $providerMessageId): array
    {
        if (! str_contains($providerMessageId, '::')) {
            return ['', 0];
        }

        [$encodedFolder, $uid] = explode('::', $providerMessageId, 2);

        return [
            (string) base64_decode($encodedFolder),
            (int) $uid,
        ];
    }

    protected function makeClient(array $payload)
    {
        $manager = new ClientManager($this->imapConfig($payload));

        return $manager->make([
            'host' => (string) $payload['imap_host'],
            'port' => (int) $payload['imap_port'],
            'protocol' => 'imap',
            'encryption' => $this->normalizeEncryption((string) ($payload['imap_encryption'] ?? 'ssl')),
            'validate_cert' => (bool) config('mailbox.imap.validate_cert', true),
            'username' => (string) $payload['imap_username'],
            'password' => (string) $payload['imap_password'],
            'authentication' => null,
            'timeout' => (int) config('mailbox.imap.timeout', 30),
        ]);
    }

    protected function imapConfig(array $payload): array
    {
        return [
            'default' => 'default',
            'accounts' => [
                'default' => [
                    'host' => (string) $payload['imap_host'],
                    'port' => (int) $payload['imap_port'],
                    'protocol' => 'imap',
                    'encryption' => $this->normalizeEncryption((string) ($payload['imap_encryption'] ?? 'ssl')),
                    'validate_cert' => (bool) config('mailbox.imap.validate_cert', true),
                    'username' => (string) $payload['imap_username'],
                    'password' => (string) $payload['imap_password'],
                    'authentication' => null,
                    'timeout' => (int) config('mailbox.imap.timeout', 30),
                ],
            ],
            'options' => [
                'delimiter' => '/',
                'fetch' => IMAP::FT_PEEK,
                'sequence' => IMAP::ST_UID,
                'fetch_body' => true,
                'fetch_flags' => true,
                'soft_fail' => false,
                'rfc822' => true,
                'debug' => false,
                'uid_cache' => true,
                'message_key' => 'uid',
                'fetch_order' => 'desc',
                'common_folders' => [],
                'open' => [],
            ],
        ];
    }

    protected function normalizeEncryption(string $encryption)
    {
        $encryption = strtolower(trim($encryption));
        if ($encryption === '' || $encryption === 'none') {
            return false;
        }

        return $encryption;
    }

    protected function folderMap(MailboxAccount $account): array
    {
        return [
            'inbox' => $account->imap_inbox_folder ?: config('mailbox.imap.default_inbox_folder', 'INBOX'),
            'sent' => $account->imap_sent_folder ?: config('mailbox.imap.default_sent_folder', 'Sent'),
            'trash' => $account->imap_trash_folder ?: config('mailbox.imap.default_trash_folder', 'Trash'),
        ];
    }
}
