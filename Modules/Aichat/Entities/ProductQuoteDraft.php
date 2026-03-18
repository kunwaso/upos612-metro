<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProductQuoteDraft extends Model
{
    public const FLOW_MULTI = 'multi';

    public const FLOW_SINGLE = 'single';

    public const STATUS_COLLECTING = 'collecting';

    public const STATUS_READY = 'ready';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'aichat_product_quote_drafts';

    protected $guarded = ['id'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'payload' => 'array',
        'telegram_chat_id' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $draft) {
            if (empty($draft->id)) {
                $draft->id = (string) Str::uuid();
            }
        });
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_product_quote_drafts.business_id', $business_id);
    }

    public function scopeForUser($query, int $user_id)
    {
        return $query->where('aichat_product_quote_drafts.user_id', $user_id);
    }

    public function scopeForConversation($query, string $conversation_id)
    {
        return $query->where('aichat_product_quote_drafts.conversation_id', $conversation_id);
    }

    public function scopeForTelegramChat($query, int $telegram_chat_id)
    {
        return $query->where('aichat_product_quote_drafts.telegram_chat_id', $telegram_chat_id);
    }

    public function scopeForChannel($query, ?string $conversation_id = null, ?int $telegram_chat_id = null)
    {
        if (empty($conversation_id) && empty($telegram_chat_id)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($builder) use ($conversation_id, $telegram_chat_id) {
            if (! empty($conversation_id)) {
                $builder->orWhere('aichat_product_quote_drafts.conversation_id', $conversation_id);
            }

            if (! empty($telegram_chat_id)) {
                $builder->orWhere('aichat_product_quote_drafts.telegram_chat_id', $telegram_chat_id);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->whereIn('aichat_product_quote_drafts.status', [
            self::STATUS_COLLECTING,
            self::STATUS_READY,
        ]);
    }

    public function isExpired(?Carbon $now = null): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->lte($now ?: now());
    }
}
