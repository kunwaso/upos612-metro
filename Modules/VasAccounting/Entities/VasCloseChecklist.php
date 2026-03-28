<?php

namespace Modules\VasAccounting\Entities;

class VasCloseChecklist extends BaseVasModel
{
    protected $table = 'vas_close_checklists';

    protected $casts = [
        'is_required' => 'boolean',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];
}
