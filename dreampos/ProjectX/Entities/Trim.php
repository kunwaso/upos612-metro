<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class Trim extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SAMPLE_REQUESTED = 'sample_requested';
    public const STATUS_SAMPLE_RECEIVED = 'sample_received';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_BULK_ORDERED = 'bulk_ordered';
    public const STATUS_BULK_RECEIVED = 'bulk_received';
    public const STATUS_QC_PASSED = 'qc_passed';
    public const STATUS_QC_FAILED = 'qc_failed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SAMPLE_REQUESTED,
        self::STATUS_SAMPLE_RECEIVED,
        self::STATUS_APPROVED,
        self::STATUS_BULK_ORDERED,
        self::STATUS_BULK_RECEIVED,
        self::STATUS_QC_PASSED,
        self::STATUS_QC_FAILED,
    ];

    public const STATUS_BADGE_MAP = [
        self::STATUS_DRAFT => 'badge-light-warning',
        self::STATUS_SAMPLE_REQUESTED => 'badge-light-info',
        self::STATUS_SAMPLE_RECEIVED => 'badge-light-primary',
        self::STATUS_APPROVED => 'badge-light-success',
        self::STATUS_BULK_ORDERED => 'badge-light-dark',
        self::STATUS_BULK_RECEIVED => 'badge-light-success',
        self::STATUS_QC_PASSED => 'badge-light-success',
        self::STATUS_QC_FAILED => 'badge-light-danger',
    ];

    public const UOM_OPTIONS = [
        'pcs',
        'cm',
        'inches',
        'yards',
        'sets',
        'gross',
        'gg',
    ];

    protected $table = 'projectx_trims';

    protected $guarded = ['id'];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'quantity_per_garment' => 'decimal:4',
        'approved_at' => 'datetime',
        'qc_at' => 'datetime',
        'share_enabled' => 'boolean',
        'share_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'share_password_hash',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function trimCategory()
    {
        return $this->belongsTo(TrimCategory::class, 'trim_category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Contact::class, 'supplier_contact_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function shareViews()
    {
        return $this->hasMany(TrimShareView::class, 'trim_id')
            ->orderBy('viewed_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('projectx_trims.business_id', $business_id);
    }

    public function getBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_MAP[$this->status] ?? 'badge-light-secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_DRAFT => __('projectx::lang.trim_status_draft'),
            self::STATUS_SAMPLE_REQUESTED => __('projectx::lang.trim_status_sample_requested'),
            self::STATUS_SAMPLE_RECEIVED => __('projectx::lang.trim_status_sample_received'),
            self::STATUS_APPROVED => __('projectx::lang.trim_status_approved'),
            self::STATUS_BULK_ORDERED => __('projectx::lang.trim_status_bulk_ordered'),
            self::STATUS_BULK_RECEIVED => __('projectx::lang.trim_status_bulk_received'),
            self::STATUS_QC_PASSED => __('projectx::lang.trim_status_qc_passed'),
            self::STATUS_QC_FAILED => __('projectx::lang.trim_status_qc_failed'),
        ];

        return $labels[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }
}
