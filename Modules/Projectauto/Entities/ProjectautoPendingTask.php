<?php

namespace Modules\Projectauto\Entities;

use Illuminate\Database\Eloquent\Model;

class ProjectautoPendingTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    public const TASK_TYPE_CREATE_INVOICE = 'create_invoice';
    public const TASK_TYPE_ADD_PRODUCT = 'add_product';
    public const TASK_TYPE_ADJUST_STOCK = 'adjust_stock';

    protected $table = 'projectauto_pending_tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'executed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
        ];
    }

    public static function taskTypes(): array
    {
        return [
            self::TASK_TYPE_CREATE_INVOICE,
            self::TASK_TYPE_ADD_PRODUCT,
            self::TASK_TYPE_ADJUST_STOCK,
        ];
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function rule()
    {
        return $this->belongsTo(ProjectautoRule::class, 'rule_id');
    }
}
