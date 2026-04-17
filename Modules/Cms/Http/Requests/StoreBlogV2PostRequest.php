<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Cms\Utils\BlogLocaleUtil;

class StoreBlogV2PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('cms.blog.posts.create');
    }

    public function rules(): array
    {
        $rules = [
            'priority' => 'nullable|integer|min:0',
            'feature_image' => 'nullable|image|max:5120',
            'allow_comments' => 'nullable|boolean',
            'show_author_card' => 'nullable|boolean',
            'show_social_share' => 'nullable|boolean',
            'show_related_posts' => 'nullable|boolean',
            'related_posts_limit' => 'nullable|integer|min:1|max:12',
        ];

        foreach (BlogLocaleUtil::supported() as $locale) {
            $rules["title.$locale"] = 'required|string|max:255';
            $rules["slug.$locale"] = 'nullable|string|max:255';
            $rules["hero_text.$locale"] = 'nullable|string|max:1500';
            $rules["excerpt.$locale"] = 'nullable|string|max:2000';
            $rules["content_html.$locale"] = 'nullable|string';
            $rules["meta_title.$locale"] = 'nullable|string|max:255';
            $rules["meta_description.$locale"] = 'nullable|string|max:500';
            $rules["meta_keywords.$locale"] = 'nullable|string|max:500';
            $rules["variant_status.$locale"] = 'nullable|in:draft,published,archived';
            $rules["tags.$locale"] = 'nullable|string|max:1000';
            $rules["section_lead.$locale"] = 'nullable|string|max:2000';
            $rules["section_quote_primary.$locale"] = 'nullable|string|max:1500';
            $rules["section_story_title.$locale"] = 'nullable|string|max:255';
            $rules["section_story_body.$locale"] = 'nullable|string|max:4000';
            $rules["section_story_cta_label.$locale"] = 'nullable|string|max:255';
            $rules["section_story_cta_url.$locale"] = 'nullable|url|max:1000';
            $rules["section_quote_secondary.$locale"] = 'nullable|string|max:1500';
            $rules["section_closing_title.$locale"] = 'nullable|string|max:255';
            $rules["section_closing_body.$locale"] = 'nullable|string|max:3000';
            $rules["hero_image.$locale"] = 'nullable|image|max:5120';
            $rules["body_image_one.$locale"] = 'nullable|image|max:5120';
            $rules["split_image.$locale"] = 'nullable|image|max:5120';
            $rules["body_image_two.$locale"] = 'nullable|image|max:5120';
        }

        return $rules;
    }
}
