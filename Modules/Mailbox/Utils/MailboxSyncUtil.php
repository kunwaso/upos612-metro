<?php

namespace Modules\Mailbox\Utils;

use App\Utils\Util;
use Carbon\Carbon;
use Google\Service\Gmail\Message as GmailMessage;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Entities\MailboxAttachment;
use Modules\Mailbox\Entities\MailboxMessage;
use Modules\Mailbox\Services\GmailMailboxClient;
use Modules\Mailbox\Services\ImapMailboxClient;
use Webklex\PHPIMAP\Message;

class MailboxSyncUtil
{
    protected Util $util;

    protected GmailMailboxClient $gmailClient;

    protected ImapMailboxClient $imapClient;

    public function __construct(Util $util, GmailMailboxClient $gmailClient, ImapMailboxClient $imapClient)
    {
        $this->util = $util;
        $this->gmailClient = $gmailClient;
        $this->imapClient = $imapClient;
    }

    public function syncAccount(MailboxAccount $account): int
    {
        $count = 0;
        $batchSize = (int) config('mailbox.sync.batch_size', 50);

        try {
            if ($account->provider === MailboxAccount::PROVIDER_GMAIL) {
                $result = $this->gmailClient->fetchMessages($account, $batchSize);

                foreach ((array) ($result['messages'] ?? []) as $message) {
                    $this->persistGmailApiMessage($account, $message);
                    $count++;
                }

                $account->sync_cursor_json = $result['cursor'] ?? $account->sync_cursor_json;
            } else {
                $result = $this->imapClient->fetchMessages($account, $batchSize);

                foreach ((array) ($result['messages'] ?? []) as $item) {
                    $this->persistImapMessage($account, (string) $item['folder_key'], (string) $item['folder_path'], $item['message']);
                    $count++;
                }

                $account->sync_cursor_json = $result['cursor'] ?? $account->sync_cursor_json;
            }

            $account->forceFill([
                'last_synced_at' => Carbon::now(),
                'last_sync_error_at' => null,
                'last_sync_error_message' => null,
            ])->save();

            $this->util->activityLog($account, 'mailbox_sync_completed', null, ['synced_count' => $count], true, (int) $account->business_id);

            return $count;
        } catch (\Throwable $exception) {
            $account->forceFill([
                'last_sync_error_at' => Carbon::now(),
                'last_sync_error_message' => Str::limit($exception->getMessage(), 500, ''),
            ])->save();

            $this->util->activityLog($account, 'mailbox_sync_failed', null, ['error' => $exception->getMessage()], true, (int) $account->business_id);

            throw $exception;
        }
    }

    public function persistGmailApiMessage(MailboxAccount $account, GmailMessage $gmailMessage): MailboxMessage
    {
        return $this->upsertNormalizedMessage($account, $this->normalizeGmailMessage($account, $gmailMessage));
    }

    public function persistImapMessage(MailboxAccount $account, string $folderKey, string $folderPath, Message $imapMessage): MailboxMessage
    {
        return $this->upsertNormalizedMessage($account, $this->normalizeImapMessage($account, $folderKey, $folderPath, $imapMessage));
    }

    public function createLocalOutgoingMessage(MailboxAccount $account, array $payload, array $providerResult = []): MailboxMessage
    {
        $internetMessageId = (string) ($providerResult['internet_message_id'] ?? $payload['internet_message_id'] ?? ('<' . Str::uuid() . '@' . $this->messageDomain($account) . '>'));
        $references = array_values(array_filter((array) ($payload['references'] ?? [])));

        return $this->upsertNormalizedMessage($account, [
            'provider_message_id' => (string) ($providerResult['provider_message_id'] ?? ('local:' . trim($internetMessageId, '<>'))),
            'provider_thread_id' => Arr::get($payload, 'provider_thread_id'),
            'thread_key' => $this->deriveThreadKey(
                MailboxAccount::PROVIDER_IMAP,
                Arr::get($payload, 'provider_thread_id'),
                $internetMessageId,
                (string) Arr::get($payload, 'in_reply_to'),
                $references,
                (string) Arr::get($payload, 'subject', '')
            ),
            'folder' => MailboxMessage::FOLDER_SENT,
            'internet_message_id' => $internetMessageId,
            'subject' => (string) Arr::get($payload, 'subject', ''),
            'snippet' => Str::limit(trim(strip_tags((string) Arr::get($payload, 'body_html', ''))), 180, ''),
            'body_text' => trim(strip_tags((string) Arr::get($payload, 'body_html', ''))),
            'body_html' => $this->sanitizeHtml((string) Arr::get($payload, 'body_html', '')),
            'from_json' => [[
                'email' => (string) $account->email_address,
                'name' => (string) ($account->sender_name ?: $account->display_name ?: $account->email_address),
            ]],
            'to_json' => $this->mapEmails((array) Arr::get($payload, 'to', [])),
            'cc_json' => $this->mapEmails((array) Arr::get($payload, 'cc', [])),
            'bcc_json' => $this->mapEmails((array) Arr::get($payload, 'bcc', [])),
            'reply_to_json' => [],
            'labels_json' => ['SENT'],
            'references_json' => $references,
            'metadata_json' => [
                'source' => 'local_send',
            ],
            'is_read' => true,
            'is_starred' => false,
            'is_important' => false,
            'is_draft' => false,
            'has_attachments' => ! empty($payload['attachments']),
            'sent_at' => Carbon::now(),
            'received_at' => Carbon::now(),
            'provider_updated_at' => Carbon::now(),
            'attachments' => collect((array) ($payload['attachments'] ?? []))->map(function ($attachment) {
                return [
                    'provider_attachment_id' => null,
                    'filename' => (string) ($attachment['name'] ?? basename((string) ($attachment['path'] ?? 'attachment'))),
                    'mime_type' => (string) ($attachment['mime_type'] ?? 'application/octet-stream'),
                    'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
                    'part_id' => null,
                    'content_id' => null,
                    'is_inline' => false,
                ];
            })->all(),
        ]);
    }

    public function upsertNormalizedMessage(MailboxAccount $account, array $payload): MailboxMessage
    {
        $message = MailboxMessage::query()->updateOrCreate(
            [
                'mailbox_account_id' => $account->id,
                'provider_message_id' => (string) $payload['provider_message_id'],
            ],
            [
                'business_id' => (int) $account->business_id,
                'user_id' => (int) $account->user_id,
                'provider' => (string) $account->provider,
                'provider_thread_id' => Arr::get($payload, 'provider_thread_id'),
                'thread_key' => (string) $payload['thread_key'],
                'folder' => (string) Arr::get($payload, 'folder', MailboxMessage::FOLDER_INBOX),
                'internet_message_id' => Arr::get($payload, 'internet_message_id'),
                'subject' => Arr::get($payload, 'subject'),
                'snippet' => Arr::get($payload, 'snippet'),
                'body_text' => Arr::get($payload, 'body_text'),
                'body_html' => Arr::get($payload, 'body_html'),
                'from_json' => Arr::get($payload, 'from_json'),
                'to_json' => Arr::get($payload, 'to_json'),
                'cc_json' => Arr::get($payload, 'cc_json'),
                'bcc_json' => Arr::get($payload, 'bcc_json'),
                'reply_to_json' => Arr::get($payload, 'reply_to_json'),
                'labels_json' => Arr::get($payload, 'labels_json'),
                'references_json' => Arr::get($payload, 'references_json'),
                'metadata_json' => Arr::get($payload, 'metadata_json'),
                'is_read' => (bool) Arr::get($payload, 'is_read', false),
                'is_starred' => (bool) Arr::get($payload, 'is_starred', false),
                'is_important' => (bool) Arr::get($payload, 'is_important', false),
                'is_draft' => (bool) Arr::get($payload, 'is_draft', false),
                'has_attachments' => (bool) Arr::get($payload, 'has_attachments', false),
                'sent_at' => Arr::get($payload, 'sent_at'),
                'received_at' => Arr::get($payload, 'received_at'),
                'provider_updated_at' => Arr::get($payload, 'provider_updated_at'),
                'synced_at' => Carbon::now(),
            ]
        );

        $this->syncAttachments($message, (array) Arr::get($payload, 'attachments', []));

        return $message->load('attachments', 'account');
    }

    public function downloadAttachment(MailboxAttachment $attachment): MailboxAttachment
    {
        if (! empty($attachment->disk) && ! empty($attachment->disk_path) && Storage::disk($attachment->disk)->exists($attachment->disk_path)) {
            return $attachment;
        }

        $message = $attachment->message()->with('account')->firstOrFail();
        $account = $message->account;

        if ($account->provider === MailboxAccount::PROVIDER_GMAIL) {
            $download = $this->gmailClient->downloadAttachment(
                $account,
                (string) $message->provider_message_id,
                (string) $attachment->provider_attachment_id
            );
        } else {
            $download = $this->imapClient->downloadAttachment(
                $account,
                (string) $message->provider_message_id,
                (string) $attachment->provider_attachment_id
            );
        }

        $disk = (string) config('mailbox.attachment_disk', 'local');
        $path = $this->attachmentStoragePath($attachment);
        Storage::disk($disk)->put($path, $download['content']);

        $attachment->forceFill([
            'disk' => $disk,
            'disk_path' => $path,
            'downloaded_at' => Carbon::now(),
            'size_bytes' => (int) ($download['size'] ?? strlen((string) $download['content'])),
            'mime_type' => (string) ($attachment->mime_type ?: Arr::get($download, 'mime_type', 'application/octet-stream')),
            'hash_sha256' => hash('sha256', (string) $download['content']),
        ])->save();

        return $attachment->fresh();
    }

    protected function normalizeGmailMessage(MailboxAccount $account, GmailMessage $gmailMessage): array
    {
        $headers = $this->gmailClient->topLevelHeaders($gmailMessage);
        $textParts = [];
        $htmlParts = [];
        $attachments = [];
        $this->gmailClient->collectBodyParts($gmailMessage->getPayload(), $textParts, $htmlParts, $attachments);

        $labels = array_values((array) $gmailMessage->getLabelIds());
        $html = trim(implode("\n", array_filter($htmlParts)));
        $text = trim(implode("\n", array_filter($textParts)));
        $receivedAt = $this->gmailDate($headers, $gmailMessage);
        $references = $this->splitReferences((string) Arr::get($headers, 'references', ''));
        $messageId = (string) $gmailMessage->getId();

        return [
            'provider_message_id' => $messageId,
            'provider_thread_id' => (string) $gmailMessage->getThreadId(),
            'thread_key' => $this->deriveThreadKey(
                MailboxAccount::PROVIDER_GMAIL,
                (string) $gmailMessage->getThreadId(),
                (string) Arr::get($headers, 'message-id', ''),
                (string) Arr::get($headers, 'in-reply-to', ''),
                $references,
                (string) Arr::get($headers, 'subject', '')
            ),
            'folder' => $this->gmailFolder($labels),
            'internet_message_id' => (string) Arr::get($headers, 'message-id'),
            'subject' => (string) Arr::get($headers, 'subject', ''),
            'snippet' => (string) $gmailMessage->getSnippet(),
            'body_text' => $text !== '' ? $text : trim(strip_tags($html)),
            'body_html' => $html !== '' ? $this->sanitizeHtml($html) : null,
            'from_json' => $this->parseAddressHeader((string) Arr::get($headers, 'from', '')),
            'to_json' => $this->parseAddressHeader((string) Arr::get($headers, 'to', '')),
            'cc_json' => $this->parseAddressHeader((string) Arr::get($headers, 'cc', '')),
            'bcc_json' => $this->parseAddressHeader((string) Arr::get($headers, 'bcc', '')),
            'reply_to_json' => $this->parseAddressHeader((string) Arr::get($headers, 'reply-to', '')),
            'labels_json' => $labels,
            'references_json' => $references,
            'metadata_json' => [
                'history_id' => $this->gmailClient->latestHistoryId($gmailMessage),
                'internal_date' => $gmailMessage->getInternalDate(),
                'size_estimate' => $gmailMessage->getSizeEstimate(),
            ],
            'is_read' => ! in_array('UNREAD', $labels, true),
            'is_starred' => in_array('STARRED', $labels, true),
            'is_important' => in_array('IMPORTANT', $labels, true),
            'is_draft' => in_array('DRAFT', $labels, true),
            'has_attachments' => ! empty($attachments),
            'sent_at' => $receivedAt,
            'received_at' => $receivedAt,
            'provider_updated_at' => $receivedAt,
            'attachments' => $attachments,
        ];
    }

    protected function normalizeImapMessage(MailboxAccount $account, string $folderKey, string $folderPath, Message $imapMessage): array
    {
        $html = trim($imapMessage->getHTMLBody());
        $text = trim($imapMessage->getTextBody());
        $references = collect((array) $imapMessage->getReferences()->toArray())->map(function ($reference) {
            return trim((string) $reference);
        })->filter()->values()->all();
        $flags = collect($imapMessage->getFlags()->toArray())->map(function ($flag) {
            return strtolower((string) $flag);
        })->values()->all();
        $attachments = collect($imapMessage->getAttachments())->map(function ($attachment) {
            return [
                'provider_attachment_id' => (string) $attachment->getPartNumber(),
                'filename' => (string) ($attachment->getFilename() ?: $attachment->getName() ?: ('attachment-' . $attachment->getPartNumber())),
                'mime_type' => (string) ($attachment->getContentType() ?: 'application/octet-stream'),
                'size_bytes' => (int) ($attachment->getSize() ?: strlen((string) $attachment->getContent())),
                'part_id' => (string) $attachment->getPartNumber(),
                'content_id' => (string) ($attachment->getId() ?: ''),
                'is_inline' => strtolower((string) $attachment->getDisposition()) === 'inline',
            ];
        })->all();
        $receivedAt = $this->imapDate($imapMessage);
        $internetMessageId = trim((string) $imapMessage->getMessageId());

        return [
            'provider_message_id' => $this->imapClient->encodeProviderMessageId($folderPath, (int) $imapMessage->getUid()),
            'provider_thread_id' => null,
            'thread_key' => $this->deriveThreadKey(
                MailboxAccount::PROVIDER_IMAP,
                null,
                $internetMessageId,
                trim((string) $imapMessage->getInReplyTo()),
                $references,
                (string) $imapMessage->getSubject()
            ),
            'folder' => $folderKey,
            'internet_message_id' => $internetMessageId,
            'subject' => (string) $imapMessage->getSubject(),
            'snippet' => Str::limit(trim(strip_tags($html !== '' ? $html : $text)), 180, ''),
            'body_text' => $text !== '' ? $text : trim(strip_tags($html)),
            'body_html' => $html !== '' ? $this->sanitizeHtml($html) : null,
            'from_json' => $this->mapImapAddresses((array) $imapMessage->getFrom()->toArray()),
            'to_json' => $this->mapImapAddresses((array) $imapMessage->getTo()->toArray()),
            'cc_json' => $this->mapImapAddresses((array) $imapMessage->getCc()->toArray()),
            'bcc_json' => $this->mapImapAddresses((array) $imapMessage->getBcc()->toArray()),
            'reply_to_json' => $this->mapImapAddresses((array) $imapMessage->getReplyTo()->toArray()),
            'labels_json' => $flags,
            'references_json' => $references,
            'metadata_json' => [
                'provider_uid' => (int) $imapMessage->getUid(),
                'folder_path' => $folderPath,
            ],
            'is_read' => in_array('seen', $flags, true),
            'is_starred' => in_array('flagged', $flags, true),
            'is_important' => in_array('flagged', $flags, true),
            'is_draft' => in_array('draft', $flags, true),
            'has_attachments' => ! empty($attachments),
            'sent_at' => $receivedAt,
            'received_at' => $receivedAt,
            'provider_updated_at' => $receivedAt,
            'attachments' => $attachments,
        ];
    }

    protected function syncAttachments(MailboxMessage $message, array $attachments): void
    {
        $existing = $message->attachments()->get()->keyBy(function (MailboxAttachment $attachment) {
            return $this->attachmentKey(
                (string) ($attachment->provider_attachment_id ?? ''),
                (string) ($attachment->part_id ?? ''),
                (string) $attachment->safe_filename
            );
        });

        $seenKeys = [];
        foreach ($attachments as $attachmentData) {
            $safeFilename = $this->sanitizeFilename((string) ($attachmentData['filename'] ?? 'attachment'));
            $key = $this->attachmentKey(
                (string) ($attachmentData['provider_attachment_id'] ?? ''),
                (string) ($attachmentData['part_id'] ?? ''),
                $safeFilename
            );
            $seenKeys[] = $key;
            $attachment = $existing->get($key) ?: new MailboxAttachment();

            $attachment->forceFill([
                'business_id' => (int) $message->business_id,
                'user_id' => (int) $message->user_id,
                'mailbox_account_id' => (int) $message->mailbox_account_id,
                'mailbox_message_id' => (int) $message->id,
                'provider_attachment_id' => Arr::get($attachmentData, 'provider_attachment_id'),
                'filename' => (string) ($attachmentData['filename'] ?? $safeFilename),
                'safe_filename' => $safeFilename,
                'mime_type' => Arr::get($attachmentData, 'mime_type'),
                'size_bytes' => Arr::get($attachmentData, 'size_bytes'),
                'part_id' => Arr::get($attachmentData, 'part_id'),
                'content_id' => Arr::get($attachmentData, 'content_id'),
                'is_inline' => (bool) Arr::get($attachmentData, 'is_inline', false),
                'metadata_json' => Arr::get($attachmentData, 'metadata_json'),
            ])->save();
        }

        $message->attachments()
            ->get()
            ->reject(function (MailboxAttachment $attachment) use ($seenKeys) {
                return in_array($this->attachmentKey((string) ($attachment->provider_attachment_id ?? ''), (string) ($attachment->part_id ?? ''), (string) $attachment->safe_filename), $seenKeys, true);
            })
            ->each(function (MailboxAttachment $attachment) {
                $attachment->delete();
            });
    }

    protected function attachmentKey(string $providerAttachmentId, string $partId, string $safeFilename): string
    {
        return implode('|', [$providerAttachmentId, $partId, $safeFilename]);
    }

    protected function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.SafeIframe', false);
        $config->set('URI.SafeIframeRegexp', null);
        $config->set('Cache.DefinitionImpl', null);

        return (new HTMLPurifier($config))->purify($html);
    }

    protected function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = Str::slug($name ?: 'attachment', '-');

        return $extension !== '' ? $name . '.' . strtolower($extension) : $name;
    }

    protected function attachmentStoragePath(MailboxAttachment $attachment): string
    {
        return implode('/', [
            'mailbox',
            (int) $attachment->business_id,
            (int) $attachment->user_id,
            (int) $attachment->mailbox_account_id,
            (int) $attachment->mailbox_message_id,
            $attachment->safe_filename,
        ]);
    }

    protected function gmailFolder(array $labels): string
    {
        if (in_array('TRASH', $labels, true)) {
            return MailboxMessage::FOLDER_TRASH;
        }

        if (in_array('SENT', $labels, true)) {
            return MailboxMessage::FOLDER_SENT;
        }

        return MailboxMessage::FOLDER_INBOX;
    }

    protected function gmailDate(array $headers, GmailMessage $gmailMessage): Carbon
    {
        $headerDate = Arr::get($headers, 'date');
        if (! empty($headerDate)) {
            try {
                return Carbon::parse($headerDate);
            } catch (\Throwable $exception) {
                // Fall back to internal date.
            }
        }

        $internalDate = (string) $gmailMessage->getInternalDate();
        if ($internalDate !== '') {
            return Carbon::createFromTimestamp(intdiv((int) $internalDate, 1000));
        }

        return Carbon::now();
    }

    protected function imapDate(Message $message): Carbon
    {
        try {
            return $message->getDate()->toDate();
        } catch (\Throwable $exception) {
            return Carbon::now();
        }
    }

    protected function deriveThreadKey(string $provider, ?string $providerThreadId, string $messageId, string $inReplyTo, array $references, string $subject): string
    {
        if (! empty($providerThreadId)) {
            return $provider . ':' . $providerThreadId;
        }

        if (! empty($inReplyTo)) {
            return $provider . ':reply:' . sha1($inReplyTo);
        }

        if (! empty($references)) {
            return $provider . ':refs:' . sha1(end($references));
        }

        if (! empty($messageId)) {
            return $provider . ':msg:' . sha1($messageId);
        }

        return $provider . ':subject:' . sha1(Str::lower(trim($subject)));
    }

    protected function parseAddressHeader(string $headerValue): array
    {
        if (trim($headerValue) === '') {
            return [];
        }

        return collect(str_getcsv($headerValue))
            ->map(function ($entry) {
                $entry = trim((string) $entry);
                if (preg_match('/^(.*)<(.+)>$/', $entry, $matches)) {
                    return [
                        'name' => trim(trim($matches[1]), '" '),
                        'email' => trim($matches[2]),
                    ];
                }

                return [
                    'name' => null,
                    'email' => $entry,
                ];
            })
            ->filter(function ($entry) {
                return ! empty($entry['email']);
            })
            ->values()
            ->all();
    }

    protected function mapImapAddresses(array $addresses): array
    {
        return collect($addresses)->map(function ($address) {
            if (is_object($address) && method_exists($address, 'toArray')) {
                $address = $address->toArray();
            }

            return [
                'name' => Arr::get((array) $address, 'personal'),
                'email' => Arr::get((array) $address, 'mail'),
            ];
        })->filter(function ($address) {
            return ! empty($address['email']);
        })->values()->all();
    }

    protected function splitReferences(string $header): array
    {
        return collect(preg_split('/\s+/', trim($header)) ?: [])
            ->map(function ($item) {
                return trim((string) $item);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function mapEmails(array $emails): array
    {
        return collect($emails)->map(function ($email) {
            return [
                'name' => null,
                'email' => (string) $email,
            ];
        })->all();
    }

    protected function messageDomain(MailboxAccount $account): string
    {
        $parts = explode('@', (string) $account->email_address);

        return $parts[1] ?? 'localhost';
    }
}
