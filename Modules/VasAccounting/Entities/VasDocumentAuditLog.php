<?php

namespace Modules\VasAccounting\Entities;

class VasDocumentAuditLog extends BaseVasModel
{
    protected $table = 'vas_document_audit_logs';

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
