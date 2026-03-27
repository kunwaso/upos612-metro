<?php

namespace Modules\Mailbox\Utils;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Entities\MailboxAttachment;
use Modules\Mailbox\Entities\MailboxMessage;
use Modules\Mailbox\Services\GmailMailboxClient;
use Modules\Mailbox\Services\ImapMailboxClient;

class MailboxMessageUtil
{
    protected MailboxAccountUtil $accountUtil;

    protected MailboxSyncUtil $syncUtil;

    protected GmailMailboxClient $gmailClient;

    protected ImapMailboxClient $imapClient;

    public function __construct(
        MailboxAccountUtil $accountUtil,
        MailboxSyncUtil $syncUtil,
        GmailMailboxClient $gmailClient,
        ImapMailboxClient $imapClient
    ) {
        $this->accountUtil = $accountUtil;
        $this->syncUtil = $syncUtil;
        $this->gmailClient = $gmailClient;
        $this->imapClient = $imapClient;
    }

    public function accountsForOwner(int $businessId, int $userId)
    {
        return $this->accountUtil->listAccountsForOwner($businessId, $userId)->where('is_active', true)->values();
    }

    public function threadSummariesForOwner(int $businessId, int $userId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $accounts = $this->accountsForOwner($businessId, $userId);
        if ($accounts->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => route('mailbox.index')]);
        }

        $baseQuery = MailboxMessage::query()->forOwner($businessId, $userId);

        if (! empty($filters['account_id'])) {
            $baseQuery->where('mailbox_account_id', (int) $filters['account_id']);
        }

        $folder = (string) ($filters['folder'] ?? MailboxMessage::FOLDER_INBOX);
        if ($folder === MailboxMessage::FOLDER_SENT) {
            $baseQuery->where('folder', MailboxMessage::FOLDER_SENT);
        } elseif ($folder === MailboxMessage::FOLDER_TRASH) {
            $baseQuery->where('folder', MailboxMessage::FOLDER_TRASH);
        } elseif ($folder === 'starred') {
            $baseQuery->where('is_starred', true)->where('folder', '!=', MailboxMessage::FOLDER_TRASH);
        } else {
            $baseQuery->where('folder', MailboxMessage::FOLDER_INBOX);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $baseQuery->where(function ($query) use ($search) {
                $query->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('snippet', 'like', '%' . $search . '%')
                    ->orWhere('body_text', 'like', '%' . $search . '%');
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'unread') {
            $baseQuery->where('is_read', false);
        } elseif ($status === 'read') {
            $baseQuery->where('is_read', true);
        } elseif ($status === 'starred') {
            $baseQuery->where('is_starred', true);
        } elseif ($status === 'attachments') {
            $baseQuery->where('has_attachments', true);
        }

        $summaryIds = (clone $baseQuery)
            ->selectRaw('MAX(mailbox_messages.id) as id')
            ->groupBy('thread_key');

        $direction = (string) ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';
        $query = MailboxMessage::query()
            ->with('account', 'attachments')
            ->whereIn('id', $summaryIds)
            ->orderByRaw('COALESCE(received_at, sent_at, created_at) ' . $direction);

        return $query->paginate($perPage)->appends($filters);
    }

    public function folderCounts(int $businessId, int $userId, ?int $accountId = null): array
    {
        $base = MailboxMessage::query()->forOwner($businessId, $userId);
        if ($accountId) {
            $base->where('mailbox_account_id', $accountId);
        }

        return [
            'inbox' => (clone $base)->where('folder', MailboxMessage::FOLDER_INBOX)->count(),
            'sent' => (clone $base)->where('folder', MailboxMessage::FOLDER_SENT)->count(),
            'starred' => (clone $base)->where('is_starred', true)->where('folder', '!=', MailboxMessage::FOLDER_TRASH)->count(),
            'trash' => (clone $base)->where('folder', MailboxMessage::FOLDER_TRASH)->count(),
        ];
    }

    public function getMessageForOwner(int $businessId, int $userId, int $messageId): MailboxMessage
    {
        return MailboxMessage::query()
            ->with('attachments', 'account')
            ->forOwner($businessId, $userId)
            ->where('id', $messageId)
            ->firstOrFail();
    }

    public function getAttachmentForOwner(int $businessId, int $userId, int $attachmentId): MailboxAttachment
    {
        return MailboxAttachment::query()
            ->with('message.account')
            ->forOwner($businessId, $userId)
            ->where('id', $attachmentId)
            ->firstOrFail();
    }

    public function threadForMessage(MailboxMessage $message)
    {
        return MailboxMessage::query()
            ->with('attachments', 'account')
            ->where('mailbox_account_id', $message->mailbox_account_id)
            ->where('thread_key', $message->thread_key)
            ->orderByRaw('COALESCE(sent_at, received_at, created_at) asc')
            ->get();
    }

    public function markThreadAsRead(MailboxMessage $message): void
    {
        foreach ($this->threadForMessage($message)->where('is_read', false) as $threadMessage) {
            $this->updateReadState($threadMessage, true);
        }
    }

    public function updateReadState(MailboxMessage $message, bool $read): void
    {
        if ($message->account->provider === MailboxAccount::PROVIDER_GMAIL) {
            $this->gmailClient->setReadState($message->account, (string) $message->provider_message_id, $read);
        } else {
            $this->imapClient->setReadState($message->account, (string) $message->provider_message_id, $read);
        }

        $message->forceFill(['is_read' => $read])->save();
    }

    public function updateStarState(MailboxMessage $message, bool $starred): void
    {
        if ($message->account->provider === MailboxAccount::PROVIDER_GMAIL) {
            $this->gmailClient->setStarState($message->account, (string) $message->provider_message_id, $starred);
        } else {
            $this->imapClient->setStarState($message->account, (string) $message->provider_message_id, $starred);
        }

        $message->forceFill([
            'is_starred' => $starred,
            'is_important' => $starred ? true : $message->is_important,
        ])->save();
    }

    public function moveToTrash(MailboxMessage $message): void
    {
        if ($message->account->provider === MailboxAccount::PROVIDER_GMAIL) {
            $this->gmailClient->moveToTrash($message->account, (string) $message->provider_message_id);
        } else {
            $this->imapClient->moveToTrash($message->account, (string) $message->provider_message_id);
        }

        $message->forceFill(['folder' => MailboxMessage::FOLDER_TRASH])->save();
    }

    public function ensureAttachmentDownloaded(MailboxAttachment $attachment): MailboxAttachment
    {
        return $this->syncUtil->downloadAttachment($attachment);
    }
}
