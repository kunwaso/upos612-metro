<?php

namespace Modules\StorageManager\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageLocationSetting extends Model
{
    protected $table = 'storage_location_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'require_lot_tracking' => 'boolean',
        'require_expiry_tracking' => 'boolean',
        'enforce_vas_sync' => 'boolean',
        'meta' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function defaultReceivingArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_receiving_area_id');
    }

    public function defaultStagingArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_staging_area_id');
    }

    public function defaultPackingArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_packing_area_id');
    }

    public function defaultDispatchArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_dispatch_area_id');
    }

    public function defaultQuarantineArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_quarantine_area_id');
    }

    public function defaultDamagedArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_damaged_area_id');
    }

    public function defaultCountHoldArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'default_count_hold_area_id');
    }
}
