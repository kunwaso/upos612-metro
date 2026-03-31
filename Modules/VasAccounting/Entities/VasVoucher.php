<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasVoucher extends BaseVasModel
{
    protected $table = 'vas_vouchers';

    protected $casts = [
        'posting_date' => 'date',
        'document_date' => 'date',
        'posted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'is_system_generated' => 'boolean',
        'is_historical_import' => 'boolean',
        'is_reversal' => 'boolean',
        'version_no' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(VasAccountingPeriod::class, 'accounting_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VasVoucherLine::class, 'voucher_id')->orderBy('line_no');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(VasJournalEntry::class, 'voucher_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(VasDocumentApproval::class, 'entity_id')
            ->where('entity_type', self::class)
            ->orderBy('step_no');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(VasDocumentAuditLog::class, 'entity_id')
            ->where('entity_type', self::class)
            ->latest('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VasDocumentAttachment::class, 'entity_id')
            ->where('entity_type', self::class)
            ->latest('id');
    }
}
