<?php

namespace Modules\VasAccounting\Entities;

class VasIntegrationRun extends BaseVasModel
{
    protected $table = 'vas_integration_runs';

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'payload' => 'array',
        'response_payload' => 'array',
    ];
}
