<?php

namespace Modules\VasAccounting\Entities;

class VasPostingFailure extends BaseVasModel
{
    protected $table = 'vas_posting_failures';

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'retry_count' => 'integer',
    ];
}
