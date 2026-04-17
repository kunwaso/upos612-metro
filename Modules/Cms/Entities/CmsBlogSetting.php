<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;

class CmsBlogSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'show_author' => 'bool',
        'show_publish_date' => 'bool',
        'show_related_posts' => 'bool',
        'show_comments' => 'bool',
        'show_likes' => 'bool',
        'show_social_share' => 'bool',
        'require_comment_approval' => 'bool',
        'posts_per_page' => 'int',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'listing_title_en' => __('cms::lang.blog'),
            'listing_title_vi' => __('cms::lang.blog'),
            'default_locale' => config('cms.blog_default_locale', 'en'),
            'posts_per_page' => 12,
            'show_author' => true,
            'show_publish_date' => true,
            'show_related_posts' => true,
            'show_comments' => true,
            'show_likes' => true,
            'show_social_share' => true,
            'require_comment_approval' => true,
        ]);
    }

    public function localized(string $field, string $locale, mixed $fallback = null): mixed
    {
        $locale = strtolower($locale);
        $column = $field.'_'.$locale;
        if (array_key_exists($column, $this->attributes) && ! empty($this->{$column})) {
            return $this->{$column};
        }

        $defaultLocale = strtolower((string) ($this->default_locale ?: config('cms.blog_default_locale', 'en')));
        $fallbackColumn = $field.'_'.$defaultLocale;
        if (array_key_exists($fallbackColumn, $this->attributes) && ! empty($this->{$fallbackColumn})) {
            return $this->{$fallbackColumn};
        }

        return $fallback;
    }
}
