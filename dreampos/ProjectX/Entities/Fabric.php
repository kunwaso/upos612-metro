<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    protected $table = 'projectx_fabrics';

    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'fds_date' => 'date:Y-m-d',
        'swatch_submit_date' => 'date:Y-m-d',
        'share_expires_at' => 'datetime',
        'attachments' => 'array',
        'mill_pattern_color' => 'array',
        'notification_email' => 'boolean',
        'notification_phone' => 'boolean',
        'share_enabled' => 'boolean',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_DRAFT = 'draft';
    const STATUS_NEEDS_APPROVAL = 'needs_approval';
    const STATUS_REJECTED = 'rejected';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
        self::STATUS_NEEDS_APPROVAL,
        self::STATUS_REJECTED,
    ];

    const STATUS_BADGE_MAP = [
        self::STATUS_ACTIVE => 'badge-light-primary',
        self::STATUS_DRAFT => 'badge-light-warning',
        self::STATUS_NEEDS_APPROVAL => 'badge-light-info',
        self::STATUS_REJECTED => 'badge-light-danger',
    ];

    const STATUS_PROGRESS_MAP = [
        self::STATUS_ACTIVE => 'bg-primary',
        self::STATUS_DRAFT => 'bg-warning',
        self::STATUS_NEEDS_APPROVAL => 'bg-info',
        self::STATUS_REJECTED => 'bg-danger',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Contact::class, 'supplier_contact_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(\App\Contact::class, 'projectx_fabric_suppliers', 'fabric_id', 'contact_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('projectx_fabric_suppliers.sort_order')
            ->orderBy('projectx_fabric_suppliers.id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function compositionItems()
    {
        return $this->hasMany(FabricCompositionItem::class, 'fabric_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function pantoneItems()
    {
        return $this->hasMany(FabricPantoneItem::class, 'fabric_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activityLogs()
    {
        return $this->hasMany(FabricActivityLog::class, 'fabric_id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function shareViews()
    {
        return $this->hasMany(FabricShareView::class, 'fabric_id')
            ->orderBy('viewed_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('projectx_fabrics.business_id', $business_id);
    }

    public function getBadgeClassAttribute()
    {
        return self::STATUS_BADGE_MAP[$this->status] ?? 'badge-light-secondary';
    }

    public function getProgressClassAttribute()
    {
        return self::STATUS_PROGRESS_MAP[$this->status] ?? 'bg-secondary';
    }

    public function getStatusLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
