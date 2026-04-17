<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsBlogLike extends Model
{
    protected $guarded = ['id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPost::class, 'cms_blog_post_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPostVariant::class, 'cms_blog_post_variant_id');
    }
}
