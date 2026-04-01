<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;
use Modules\VasAccounting\Entities\BaseVasModel;
use Modules\VasAccounting\Entities\VasAccountingPeriod;

class FinanceDocument extends BaseVasModel
{
    protected $table = 'vas_fin_documents';

    protected $casts = [
        'document_date' => 'date',
        'posting_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'gross_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'open_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:8',
        'meta' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(VasAccountingPeriod::class, 'accounting_period_id');
    }

    public function reversalDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_document_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceDocumentLine::class, 'document_id')->orderBy('line_no');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(FinanceDocumentStatusHistory::class, 'document_id')->orderBy('acted_at');
    }

    public function accountingEvents(): HasMany
    {
        return $this->hasMany(FinanceAccountingEvent::class, 'document_id')->orderByDesc('id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(FinanceJournalEntry::class, 'document_id')->orderByDesc('id');
    }

    public function approvalInstances(): HasMany
    {
        return $this->hasMany(FinanceApprovalInstance::class, 'document_id')->orderByDesc('id');
    }

    public function traceLinks(): HasMany
    {
        return $this->hasMany(FinanceTraceLink::class, 'document_id')->orderBy('id');
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(FinanceDocumentLink::class, 'child_document_id')->orderBy('id');
    }

    public function childLinks(): HasMany
    {
        return $this->hasMany(FinanceDocumentLink::class, 'parent_document_id')->orderBy('id');
    }

    public function openItems(): HasMany
    {
        return $this->hasMany(FinanceOpenItem::class, 'document_id')->orderByDesc('id');
    }

    public function matchRuns(): HasMany
    {
        return $this->hasMany(FinanceMatchRun::class, 'document_id')->orderByDesc('id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(FinanceInventoryMovement::class, 'document_id')->orderByDesc('id');
    }

    public function treasuryReconciliations(): HasMany
    {
        return $this->hasMany(FinanceTreasuryReconciliation::class, 'document_id')->orderByDesc('id');
    }
}
