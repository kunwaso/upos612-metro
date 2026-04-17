<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Cms\Utils\BlogLocaleUtil;

class UpdateBlogV2SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('cms.blog.settings.update');
    }

    public function rules(): array
    {
        $rules = [
            'listing_banner_image' => 'nullable|image|max:5120',
            'show_author' => 'nullable|boolean',
            'show_publish_date' => 'nullable|boolean',
            'show_related_posts' => 'nullable|boolean',
            'show_comments' => 'nullable|boolean',
            'show_likes' => 'nullable|boolean',
            'show_social_share' => 'nullable|boolean',
            'require_comment_approval' => 'nullable|boolean',
            'posts_per_page' => 'nullable|integer|min:1|max:100',
            'default_locale' => 'required|in:'.implode(',', BlogLocaleUtil::supported()),
        ];

        foreach (BlogLocaleUtil::supported() as $locale) {
            $rules["listing_title.$locale"] = 'nullable|string|max:255';
            $rules["listing_hero_text.$locale"] = 'nullable|string|max:1500';
            $rules["listing_meta_title.$locale"] = 'nullable|string|max:255';
            $rules["listing_meta_description.$locale"] = 'nullable|string|max:500';
            $rules["listing_meta_keywords.$locale"] = 'nullable|string|max:500';
        }

        return $rules;
    }
}
