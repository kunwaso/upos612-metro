<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsBlogPostVariant extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPost::class, 'cms_blog_post_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CmsBlogVariantSection::class, 'cms_blog_post_variant_id');
    }
}
