<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDailyTask extends Model
{
    use SoftDeletes;

    protected $table = 'projectx_user_daily_tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'task_date' => 'date:Y-m-d',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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
        return $query->where('projectx_user_daily_tasks.business_id', $business_id);
    }
}
