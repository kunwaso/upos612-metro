<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductActivityLog extends Model
{
    protected $table = 'product_activity_log';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const ACTION_FABRIC_CREATED = 'fabric_created';
    const ACTION_SETTINGS_UPDATED = 'settings_updated';
    const ACTION_IMAGE_ADDED = 'image_added';
    const ACTION_IMAGE_REMOVED = 'image_removed';
    const ACTION_ATTACHMENT_ADDED = 'attachment_added';
    const ACTION_COMPOSITION_UPDATED = 'composition_updated';
    const ACTION_PANTONE_UPDATED = 'pantone_updated';
    const ACTION_SALE_ADDED = 'sale_added';
    const ACTION_SUBMITTED_FOR_APPROVAL = 'submitted_for_approval';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';

    const PERIOD_TODAY = 'today';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where($this->table . '.business_id', $business_id);
    }

    public function scopeForProduct($query, int $product_id)
    {
        return $query->where($this->table . '.product_id', $product_id);
    }

    public function scopeWithinLastYear($query)
    {
        return $query->where($this->table . '.created_at', '>=', now()->subYear());
    }

    public function scopeWithinPeriod($query, string $period)
    {
        $period = strtolower($period);
        $now = now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        if ($period === self::PERIOD_WEEK) {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } elseif ($period === self::PERIOD_MONTH) {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        } elseif ($period === self::PERIOD_YEAR) {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
        }

        return $query->whereBetween($this->table . '.created_at', [$start, $end]);
    }
}
