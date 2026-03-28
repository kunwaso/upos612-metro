<?php

namespace Modules\VasAccounting\Entities;

class VasIntegrationWebhook extends BaseVasModel
{
    protected $table = 'vas_integration_webhooks';

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'payload' => 'array',
        'response_payload' => 'array',
    ];
}
