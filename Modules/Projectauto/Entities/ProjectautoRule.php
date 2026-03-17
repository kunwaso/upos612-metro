<?php

namespace Modules\Projectauto\Entities;

use Illuminate\Database\Eloquent\Model;

class ProjectautoRule extends Model
{
    public const TRIGGER_PAYMENT_STATUS_UPDATED = 'payment_status_updated';
    public const TRIGGER_SALES_ORDER_STATUS_UPDATED = 'sales_order_status_updated';

    protected $table = 'projectauto_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'workflow_id' => 'integer',
        'conditions' => 'array',
        'payload_template' => 'array',
        'is_active' => 'boolean',
        'stop_on_match' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
