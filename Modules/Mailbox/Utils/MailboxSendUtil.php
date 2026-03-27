<?php

namespace Modules\Mailbox\Utils;

use App\Utils\Util;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Mailbox\Entities\MailboxMessage;
use Modules\Mailbox\Jobs\SendMailboxMessageJob;
use Modules\Mailbox\Jobs\SyncMailboxAccountJob;
use Modules\Mailbox\Services\GmailMailboxClient;
use Modules\Mailbox\Services\SmtpMailboxSender;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailboxSendUtil
{
    protected MailboxAccountUtil $accountUtil;

    protected MailboxSyncUtil $syncUtil;

    protected GmailMailboxClient $gmailClient;

    protected SmtpMailboxSender $smtpSender;

    protected Util $util;

    public function __construct(
        MailboxAccountUtil $accountUtil,
        MailboxSyncUtil $syncUtil,
        GmailMailboxClient $gmailClient,
        SmtpMailboxSender $smtpSender,
        Util $util
    ) {
        $this->accountUtil = $accountUtil;
        $this->syncUtil = $syncUtil;
        $this->gmailClient = $gmailClient;
        $this->smtpSender = $smtpSender;
        $this->util = $util;
    }

    public function dispatchSend(int $businessId, int $userId, array $payload): void
    {
        $account = $this->accountUtil->getAccountForOwner($businessId, $userId, (int) $payload['account_id']);
        $payload['attachments'] = $this->stageAttachments((array) ($payload['attachments'] ?? []), $userId);

        if (! empty($payload['reply_message_id'])) {
            $replyMessage = MailboxMessage::query()
                ->forOwner($businessId, $userId)
                ->where('id', (int) $payload['reply_message_id'])
                ->firstOrFail();

            $references = array_values(array_unique(array_filter(array_merge(
                (array) ($replyMessage->references_json ?? []),
                [$replyMessage->internet_message_id]
            ))));

            $payload['provider_thread_id'] = $replyMessage->provider_thread_id;
            $payload['in_reply_to'] = $replyMessage->internet_message_id;
            $payload['references'] = $references;
        }

        SendMailboxMessageJob::dispatch($businessId, $userId, $payload);
    }

    public function handleSend(int $businessId, int $userId, array $payload): MailboxMessage
    {
        $account = $this->accountUtil->getAccountForOwner($businessId, $userId, (int) $payload['account_id']);

        try {
            if ($account->provider === 'gmail') {
                $rawMime = $this->buildMimeMessage($account, $payload);
                $result = $this->gmailClient->sendMessage($account, $rawMime, Arr::get($payload, 'provider_thread_id'));
                $message = $this->syncUtil->persistGmailApiMessage($account, $this->gmailClient->getMessage($account, (string) $result->getId()));
            } else {
                $result = $this->smtpSender->send($account, $payload);
                $payload['internet_message_id'] = $result['internet_message_id'] ?? null;
                $message = $this->syncUtil->createLocalOutgoingMessage($account, $payload, $result);
            }

            $this->util->activityLog($message, 'mailbox_message_sent', null, ['account_id' => $account->id], true, $businessId);

            if ($account->sync_enabled) {
                SyncMailboxAccountJob::dispatch((int) $account->id);
            }

            return $message;
        } finally {
            $this->cleanupStagedAttachments((array) ($payload['attachments'] ?? []));
        }
    }

    protected function buildMimeMessage($account, array $payload): string
    {
        $email = new Email();
        $email->from(new Address((string) $account->email_address, (string) ($account->sender_name ?: $account->display_name ?: $account->email_address)));
        $email->subject((string) ($payload['subject'] ?? ''));
        $email->to(...$this->mapAddresses((array) ($payload['to'] ?? [])));

        if (! empty($payload['cc'])) {
            $email->cc(...$this->mapAddresses((array) $payload['cc']));
        }

        if (! empty($payload['bcc'])) {
            $email->bcc(...$this->mapAddresses((array) $payload['bcc']));
        }

        $html = (string) ($payload['body_html'] ?? '');
        $email->html($html !== '' ? $html : '<p></p>');
        $email->text(trim(strip_tags($html)) !== '' ? trim(strip_tags($html)) : ' ');

        $messageId = sprintf('<%s@%s>', (string) Str::uuid(), explode('@', (string) $account->email_address)[1] ?? 'localhost');
        $email->getHeaders()->addIdHeader('Message-ID', trim($messageId, '<>'));

        if (! empty($payload['in_reply_to'])) {
            $email->getHeaders()->addTextHeader('In-Reply-To', (string) $payload['in_reply_to']);
        }

        if (! empty($payload['references'])) {
            $email->getHeaders()->addTextHeader('References', implode(' ', (array) $payload['references']));
        }

        foreach ((array) ($payload['attachments'] ?? []) as $attachment) {
            if (! empty($attachment['path']) && is_file((string) $attachment['path'])) {
                $email->attachFromPath(
                    (string) $attachment['path'],
                    (string) ($attachment['name'] ?? basename((string) $attachment['path'])),
                    (string) ($attachment['mime_type'] ?? 'application/octet-stream')
                );
            }
        }

        return $email->toString();
    }

    protected function stageAttachments(array $attachments, int $userId): array
    {
        return collect($attachments)->map(function ($attachment) use ($userId) {
            if (! $attachment instanceof UploadedFile) {
                return $attachment;
            }

            $safeName = Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME), '-');
            $extension = strtolower((string) $attachment->getClientOriginalExtension());
            $targetName = Str::uuid() . '-' . ($safeName !== '' ? $safeName : 'attachment') . ($extension !== '' ? '.' . $extension : '');
            $storedPath = $attachment->storeAs('mailbox/tmp/' . $userId, $targetName, 'local');

            return [
                'stored_path' => $storedPath,
                'path' => storage_path('app/' . $storedPath),
                'name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getClientMimeType(),
                'size_bytes' => $attachment->getSize(),
            ];
        })->all();
    }

    protected function cleanupStagedAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (! empty($attachment['stored_path'])) {
                Storage::disk('local')->delete((string) $attachment['stored_path']);
            }
        }
    }

    protected function mapAddresses(array $emails): array
    {
        return collect($emails)->map(function ($email) {
            return new Address((string) $email);
        })->all();
    }
}
