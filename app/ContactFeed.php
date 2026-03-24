<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactFeed extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    /**
     * Get the contact that owns the feed item.
     */
    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    /**
     * Scope query to a single tenant + contact.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $business_id
     * @param int $contact_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForContact($query, $business_id, $contact_id)
    {
        return $query->where('business_id', $business_id)
            ->where('contact_id', $contact_id);
    }
}
