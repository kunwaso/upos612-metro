<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalendarSchedule extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'created_by',
        'location_id',
        'title',
        'description',
        'notes',
        'start_at',
        'end_at',
        'all_day',
        'color',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}
