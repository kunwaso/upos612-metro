<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasEInvoiceLog extends BaseVasModel
{
    protected $table = 'vas_einvoice_logs';

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(VasEInvoiceDocument::class, 'einvoice_document_id');
    }
}
