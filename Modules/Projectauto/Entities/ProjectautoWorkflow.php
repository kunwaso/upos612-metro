<?php

namespace Modules\Projectauto\Entities;

use Illuminate\Database\Eloquent\Model;

class ProjectautoWorkflow extends Model
{
    protected $table = 'projectauto_workflows';

    protected $guarded = ['id'];

    protected $casts = [
        'draft_graph' => 'array',
        'published_graph' => 'array',
        'last_validation_errors' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
