<?php

namespace Modules\Mailbox\Utils;

use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Services\GmailMailboxClient;
use Modules\Mailbox\Services\ImapMailboxClient;

class MailboxAccountUtil
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

    public function listAccountsForOwner(int $businessId, int $userId)
    {
        return MailboxAccount::query()
            ->forOwner($businessId, $userId)
            ->orderByDesc('is_active')
            ->orderBy('provider')
            ->orderBy('email_address')
            ->get();
    }

    public function getAccountForOwner(int $businessId, int $userId, int $accountId): MailboxAccount
    {
        return MailboxAccount::query()
            ->forOwner($businessId, $userId)
            ->where('id', $accountId)
            ->firstOrFail();
    }

    public function testImapConnection(array $payload): array
    {
        return $this->imapClient->testConnection($payload);
    }

    public function storeImapAccount(int $businessId, int $userId, array $payload, ?MailboxAccount $account = null): MailboxAccount
    {
        $before = $account ? clone $account : null;
        $account = $account ?: new MailboxAccount();

        $resetCursor = $this->shouldResetCursor($account, $payload);
        $imapPassword = trim((string) ($payload['imap_password'] ?? ''));
        $smtpPassword = trim((string) ($payload['smtp_password'] ?? ''));

        $account->forceFill([
            'business_id' => $businessId,
            'user_id' => $userId,
            'provider' => MailboxAccount::PROVIDER_IMAP,
            'display_name' => Arr::get($payload, 'display_name'),
            'sender_name' => Arr::get($payload, 'sender_name'),
            'email_address' => (string) $payload['email_address'],
            'imap_host' => (string) $payload['imap_host'],
            'imap_port' => (int) $payload['imap_port'],
            'imap_encryption' => $this->normalizeEncryption((string) ($payload['imap_encryption'] ?? 'ssl')),
            'imap_username' => (string) $payload['imap_username'],
            'imap_inbox_folder' => (string) ($payload['imap_inbox_folder'] ?: config('mailbox.imap.default_inbox_folder', 'INBOX')),
            'imap_sent_folder' => (string) ($payload['imap_sent_folder'] ?: config('mailbox.imap.default_sent_folder', 'Sent')),
            'imap_trash_folder' => (string) ($payload['imap_trash_folder'] ?: config('mailbox.imap.default_trash_folder', 'Trash')),
            'smtp_host' => (string) $payload['smtp_host'],
            'smtp_port' => (int) $payload['smtp_port'],
            'smtp_encryption' => $this->normalizeEncryption((string) ($payload['smtp_encryption'] ?? 'ssl')),
            'smtp_username' => (string) $payload['smtp_username'],
            'sync_enabled' => (bool) ($payload['sync_enabled'] ?? false),
            'is_active' => true,
            'last_tested_at' => Carbon::now(),
            'last_sync_error_at' => null,
            'last_sync_error_message' => null,
        ]);

        if ($imapPassword !== '') {
            $account->encrypted_imap_password = $imapPassword;
        }

        if ($smtpPassword !== '') {
            $account->encrypted_smtp_password = $smtpPassword;
        }

        if ($resetCursor) {
            $account->sync_cursor_json = null;
        }

        $account->save();

        $this->audit($account, $before ? 'mailbox_account_updated' : 'mailbox_account_connected', $before, [
            'provider' => 'imap',
        ]);

        return $account;
    }

    public function storeGmailAccount(int $businessId, int $userId, SocialiteUser $socialiteUser, array $profile): MailboxAccount
    {
        $emailAddress = (string) ($socialiteUser->getEmail() ?: Arr::get($profile, 'email_address'));
        $account = MailboxAccount::query()
            ->forOwner($businessId, $userId)
            ->where('provider', MailboxAccount::PROVIDER_GMAIL)
            ->where(function ($query) use ($emailAddress, $socialiteUser) {
                $query->where('email_address', $emailAddress);

                if (! empty($socialiteUser->getId())) {
                    $query->orWhere('provider_account_id', (string) $socialiteUser->getId());
                }
            })
            ->first();

        $before = $account ? clone $account : null;
        $account = $account ?: new MailboxAccount();

        $refreshToken = (string) ($socialiteUser->refreshToken ?? '');
        if ($refreshToken === '' && $account->exists) {
            $refreshToken = (string) $account->encrypted_refresh_token;
        }

        if ($refreshToken === '') {
            throw new \RuntimeException(__('mailbox::lang.oauth_missing_refresh_token'));
        }

        $account->forceFill([
            'business_id' => $businessId,
            'user_id' => $userId,
            'provider' => MailboxAccount::PROVIDER_GMAIL,
            'display_name' => $socialiteUser->getName(),
            'sender_name' => $socialiteUser->getName(),
            'email_address' => $emailAddress,
            'provider_account_id' => (string) $socialiteUser->getId(),
            'avatar_url' => $socialiteUser->getAvatar(),
            'encrypted_access_token' => (string) $socialiteUser->token,
            'encrypted_refresh_token' => $refreshToken,
            'token_expires_at' => ! empty($socialiteUser->expiresIn) ? Carbon::now()->addSeconds((int) $socialiteUser->expiresIn) : null,
            'sync_enabled' => true,
            'is_active' => true,
            'provider_meta_json' => array_filter([
                'history_id' => Arr::get($profile, 'history_id'),
                'messages_total' => Arr::get($profile, 'messages_total'),
                'threads_total' => Arr::get($profile, 'threads_total'),
            ], function ($value) {
                return $value !== null && $value !== '';
            }),
            'sync_cursor_json' => [
                'gmail_history_id' => (string) Arr::get($profile, 'history_id', ''),
            ],
            'last_sync_error_at' => null,
            'last_sync_error_message' => null,
        ]);

        $account->save();

        $this->audit($account, $before ? 'mailbox_account_updated' : 'mailbox_account_connected', $before, [
            'provider' => 'gmail',
        ]);

        return $account;
    }

    public function disconnectAccount(MailboxAccount $account): void
    {
        $before = clone $account;

        $account->forceFill([
            'is_active' => false,
            'sync_enabled' => false,
            'encrypted_access_token' => null,
            'encrypted_refresh_token' => null,
            'encrypted_imap_password' => null,
            'encrypted_smtp_password' => null,
            'token_expires_at' => null,
            'last_sync_error_at' => null,
            'last_sync_error_message' => null,
        ])->save();

        $this->audit($account, 'mailbox_account_disconnected', $before);
    }

    public function audit(MailboxAccount $account, string $action, $before = null, array $properties = []): void
    {
        $this->util->activityLog($account, $action, $before, $properties, true, (int) $account->business_id);
    }

    protected function shouldResetCursor(MailboxAccount $account, array $payload): bool
    {
        if (! $account->exists) {
            return true;
        }

        $watchedFields = [
            'email_address',
            'imap_host',
            'imap_port',
            'imap_username',
            'imap_inbox_folder',
            'imap_sent_folder',
            'imap_trash_folder',
        ];

        foreach ($watchedFields as $field) {
            if ((string) $account->getAttribute($field) !== (string) Arr::get($payload, $field, '')) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeEncryption(string $value): ?string
    {
        $value = strtolower(trim($value));

        return $value === '' || $value === 'none' ? null : $value;
    }
}
