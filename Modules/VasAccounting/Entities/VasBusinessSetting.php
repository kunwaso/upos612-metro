<?php

namespace Modules\VasAccounting\Entities;

class VasBusinessSetting extends BaseVasModel
{
    protected $table = 'vas_business_settings';

    protected $casts = [
        'posting_map' => 'array',
        'compliance_settings' => 'array',
        'compliance_effective_date' => 'date',
        'inventory_settings' => 'array',
        'depreciation_settings' => 'array',
        'tax_settings' => 'array',
        'einvoice_settings' => 'array',
        'report_preferences' => 'array',
        'feature_flags' => 'array',
        'approval_settings' => 'array',
        'branch_settings' => 'array',
        'integration_settings' => 'array',
        'budget_settings' => 'array',
        'cutover_settings' => 'array',
        'rollout_settings' => 'array',
        'ui_settings' => 'array',
        'is_enabled' => 'boolean',
        'compliance_legacy_bridge_enabled' => 'boolean',
    ];
}
