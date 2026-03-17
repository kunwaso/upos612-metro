<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class UserAttendanceOverride extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_BREAK = 'break';
    public const STATUS_LATE = 'late';
    public const STATUS_PERMISSION = 'permission';
    public const STATUS_NOT_PRESENT = 'not_present';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_BREAK,
        self::STATUS_LATE,
        self::STATUS_PERMISSION,
        self::STATUS_NOT_PRESENT,
    ];

    protected $table = 'projectx_user_attendance_overrides';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'hour_slot' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('projectx_user_attendance_overrides.business_id', $business_id);
    }
}
