<?php

namespace Modules\Mailbox\Entities;

use Illuminate\Database\Eloquent\Model;

class MailboxMessage extends Model
{
    public const FOLDER_INBOX = 'inbox';
    public const FOLDER_SENT = 'sent';
    public const FOLDER_TRASH = 'trash';

    protected $table = 'mailbox_messages';

    protected $guarded = ['id'];

    protected $casts = [
        'from_json' => 'array',
        'to_json' => 'array',
        'cc_json' => 'array',
        'bcc_json' => 'array',
        'reply_to_json' => 'array',
        'labels_json' => 'array',
        'references_json' => 'array',
        'metadata_json' => 'array',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_important' => 'boolean',
        'is_draft' => 'boolean',
        'has_attachments' => 'boolean',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'provider_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(MailboxAccount::class, 'mailbox_account_id');
    }

    public function attachments()
    {
        return $this->hasMany(MailboxAttachment::class, 'mailbox_message_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('mailbox_messages.business_id', $businessId);
    }

    public function scopeForOwner($query, int $businessId, int $userId)
    {
        return $query
            ->where('mailbox_messages.business_id', $businessId)
            ->where('mailbox_messages.user_id', $userId);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('mailbox_messages.mailbox_account_id', $accountId);
    }

    public function getPrimaryTimestampAttribute()
    {
        return $this->received_at ?: $this->sent_at ?: $this->created_at;
    }
}
