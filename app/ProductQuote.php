<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductQuote extends Model
{
    public const STATE_DRAFT = 'draft';

    public const STATE_SENT = 'sent';

    public const STATE_CONFIRMED = 'confirmed';

    public const STATE_CONVERTED = 'converted';

    protected $table = 'product_quotes';

    protected $guarded = ['id'];

    protected $casts = [
        'quote_date' => 'date',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected $hidden = [
        'public_link_password',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(ProductQuoteLine::class, 'quote_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('product_quotes.business_id', $business_id);
    }

    public static function generateUuid(): string
    {
        return (string) Str::uuid();
    }

    public static function generateUniquePublicToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('public_token', $token)->exists());

        return $token;
    }

    public function getDerivedStateAttribute(): string
    {
        if (! empty($this->transaction_id)) {
            return self::STATE_CONVERTED;
        }

        if (! empty($this->confirmed_at)) {
            return self::STATE_CONFIRMED;
        }

        if (! empty($this->sent_at)) {
            return self::STATE_SENT;
        }

        return self::STATE_DRAFT;
    }

    public function isEditable(): bool
    {
        return empty($this->transaction_id) && empty($this->confirmed_at);
    }

    public function isConfirmed(): bool
    {
        return ! empty($this->confirmed_at);
    }
}
