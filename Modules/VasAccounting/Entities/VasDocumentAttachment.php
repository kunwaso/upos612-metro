<?php

namespace Modules\VasAccounting\Entities;

class VasDocumentAttachment extends BaseVasModel
{
    protected $table = 'vas_document_attachments';

    protected $casts = [
        'meta' => 'array',
    ];
}
