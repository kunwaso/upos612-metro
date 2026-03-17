<?php

namespace Modules\Projectauto\Entities;

use Illuminate\Database\Eloquent\Model;

class ProjectautoAuditLog extends Model
{
    protected $table = 'projectauto_audit_log';

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function task()
    {
        return $this->belongsTo(ProjectautoPendingTask::class, 'pending_task_id');
    }

    public function rule()
    {
        return $this->belongsTo(ProjectautoRule::class, 'rule_id');
    }
}
