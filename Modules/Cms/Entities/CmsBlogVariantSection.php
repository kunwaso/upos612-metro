<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsBlogVariantSection extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(CmsBlogPostVariant::class, 'cms_blog_post_variant_id');
    }
}
