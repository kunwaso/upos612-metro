<?php

namespace Modules\Mailbox\Entities;

use Illuminate\Database\Eloquent\Model;

class MailboxAttachment extends Model
{
    protected $table = 'mailbox_attachments';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata_json' => 'array',
        'is_inline' => 'boolean',
        'downloaded_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(MailboxAccount::class, 'mailbox_account_id');
    }

    public function message()
    {
        return $this->belongsTo(MailboxMessage::class, 'mailbox_message_id');
    }

    public function scopeForOwner($query, int $businessId, int $userId)
    {
        return $query
            ->where('mailbox_attachments.business_id', $businessId)
            ->where('mailbox_attachments.user_id', $userId);
    }
}
