<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VasEInvoiceDocument extends BaseVasModel
{
    protected $table = 'vas_einvoice_documents';

    protected $casts = [
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'source_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(VasEInvoiceLog::class, 'einvoice_document_id');
    }
}
