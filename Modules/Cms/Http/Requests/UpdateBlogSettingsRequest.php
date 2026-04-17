<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('cms.blog.settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'blog_settings.listing_title' => 'nullable|string|max:255',
            'blog_settings.listing_hero_text' => 'nullable|string|max:1000',
            'blog_settings.listing_banner_image' => 'nullable|image|max:5120',
            'blog_settings.listing_meta_title' => 'nullable|string|max:255',
            'blog_settings.listing_meta_description' => 'nullable|string|max:500',
            'blog_settings.listing_meta_keywords' => 'nullable|string|max:500',
            'blog_settings.show_author' => 'nullable|boolean',
            'blog_settings.show_publish_date' => 'nullable|boolean',
            'blog_settings.show_related_posts' => 'nullable|boolean',
            'blog_settings.posts_per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
