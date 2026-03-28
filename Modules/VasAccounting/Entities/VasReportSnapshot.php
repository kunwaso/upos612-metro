<?php

namespace Modules\VasAccounting\Entities;

class VasReportSnapshot extends BaseVasModel
{
    protected $table = 'vas_report_snapshots';

    protected $casts = [
        'generated_at' => 'datetime',
        'filters' => 'array',
        'payload' => 'array',
    ];
}
