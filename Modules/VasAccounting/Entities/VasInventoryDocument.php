<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasInventoryDocument extends BaseVasModel
{
    protected $table = 'vas_inventory_documents';

    protected $casts = [
        'posting_date' => 'date',
        'document_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VasWarehouse::class, 'warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(VasWarehouse::class, 'destination_warehouse_id');
    }

    public function offsetAccount(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'offset_account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(VasAccountingPeriod::class, 'accounting_period_id');
    }

    public function postedVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'posted_voucher_id');
    }

    public function reversalVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'reversal_voucher_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VasInventoryDocumentLine::class, 'inventory_document_id')->orderBy('line_no');
    }
}
