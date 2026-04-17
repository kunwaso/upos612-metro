<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsBlogPost extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'bool',
        'allow_comments' => 'bool',
        'show_author_card' => 'bool',
        'show_social_share' => 'bool',
        'show_related_posts' => 'bool',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CmsBlogPostVariant::class, 'cms_blog_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CmsBlogComment::class, 'cms_blog_post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CmsBlogLike::class, 'cms_blog_post_id');
    }

    public function getFeatureImageUrlAttribute(): ?string
    {
        if (empty($this->feature_image)) {
            return null;
        }

        return asset('/uploads/cms/'.rawurlencode($this->feature_image));
    }
}
