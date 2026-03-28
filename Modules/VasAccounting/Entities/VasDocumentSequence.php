<?php

namespace Modules\VasAccounting\Entities;

class VasDocumentSequence extends BaseVasModel
{
    protected $table = 'vas_document_sequences';

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];
}
