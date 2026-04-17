<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsBlogAuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPost::class, 'cms_blog_post_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'actor_user_id');
    }
}
