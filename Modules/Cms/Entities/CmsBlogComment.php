<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsBlogComment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPost::class, 'cms_blog_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'moderated_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
