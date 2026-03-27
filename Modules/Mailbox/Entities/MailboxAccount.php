<?php

namespace Modules\Mailbox\Entities;

use Illuminate\Database\Eloquent\Model;

class MailboxAccount extends Model
{
    public const PROVIDER_GMAIL = 'gmail';
    public const PROVIDER_IMAP = 'imap';

    protected $table = 'mailbox_accounts';

    protected $guarded = ['id'];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'is_active' => 'boolean',
        'sync_cursor_json' => 'array',
        'provider_meta_json' => 'array',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_tested_at' => 'datetime',
        'last_sync_error_at' => 'datetime',
        'encrypted_access_token' => 'encrypted',
        'encrypted_refresh_token' => 'encrypted',
        'encrypted_imap_password' => 'encrypted',
        'encrypted_smtp_password' => 'encrypted',
    ];

    public $log_properties = [
        'provider',
        'display_name',
        'email_address',
        'sync_enabled',
        'is_active',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(MailboxMessage::class, 'mailbox_account_id');
    }

    public function attachments()
    {
        return $this->hasMany(MailboxAttachment::class, 'mailbox_account_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('mailbox_accounts.business_id', $businessId);
    }

    public function scopeForOwner($query, int $businessId, int $userId)
    {
        return $query
            ->where('mailbox_accounts.business_id', $businessId)
            ->where('mailbox_accounts.user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('mailbox_accounts.is_active', true);
    }

    public function scopeSyncable($query)
    {
        return $query
            ->where('mailbox_accounts.is_active', true)
            ->where('mailbox_accounts.sync_enabled', true);
    }

    public function getProviderLabelAttribute(): string
    {
        return $this->provider === self::PROVIDER_GMAIL ? 'Gmail' : 'IMAP / SMTP';
    }
}
