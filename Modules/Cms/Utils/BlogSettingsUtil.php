<?php

namespace Modules\Cms\Utils;

use App\Utils\Util;
use Illuminate\Http\Request;
use Modules\Cms\Entities\CmsSiteDetail;

class BlogSettingsUtil extends Util
{
    public const BLOG_SETTINGS_KEY = 'blog_settings';

    public function getSettings(): array
    {
        $settings = CmsSiteDetail::getValue(self::BLOG_SETTINGS_KEY);

        return is_array($settings) ? $this->applyDefaults($settings) : $this->defaults();
    }

    public function save(Request $request): array
    {
        $settings = $request->input('blog_settings', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = $this->applyDefaults($settings);

        if ($request->hasFile('blog_settings.listing_banner_image')) {
            $settings['listing_banner_image'] = $this->uploadFile(
                $request,
                'blog_settings.listing_banner_image',
                'cms',
                'image'
            );
        } elseif (empty($settings['listing_banner_image'])) {
            $settings['listing_banner_image'] = $this->getSettings()['listing_banner_image'];
        }

        CmsSiteDetail::updateOrCreate(
            ['site_key' => self::BLOG_SETTINGS_KEY],
            ['site_value' => json_encode($settings)]
        );

        return $settings;
    }

    public function defaults(): array
    {
        return [
            'listing_title' => __('cms::lang.blog'),
            'listing_hero_text' => '',
            'listing_banner_image' => null,
            'listing_meta_title' => '',
            'listing_meta_description' => '',
            'listing_meta_keywords' => '',
            'show_author' => true,
            'show_publish_date' => true,
            'show_related_posts' => true,
            'posts_per_page' => 12,
        ];
    }

    public function applyDefaults(array $settings): array
    {
        $defaults = $this->defaults();
        $normalized = array_merge($defaults, $settings);
        $normalized['show_author'] = (bool) ($normalized['show_author'] ?? false);
        $normalized['show_publish_date'] = (bool) ($normalized['show_publish_date'] ?? false);
        $normalized['show_related_posts'] = (bool) ($normalized['show_related_posts'] ?? false);
        $normalized['posts_per_page'] = max(1, (int) ($normalized['posts_per_page'] ?? 12));

        return $normalized;
    }
}
