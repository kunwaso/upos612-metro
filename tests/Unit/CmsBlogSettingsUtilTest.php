<?php

namespace Tests\Unit;

use Modules\Cms\Utils\BlogSettingsUtil;
use Tests\TestCase;

class CmsBlogSettingsUtilTest extends TestCase
{
    public function test_apply_defaults_normalizes_flags_and_page_size(): void
    {
        $util = app(BlogSettingsUtil::class);

        $settings = $util->applyDefaults([
            'listing_title' => 'My Blog',
            'show_author' => 0,
            'show_publish_date' => 1,
            'show_related_posts' => 0,
            'posts_per_page' => 0,
        ]);

        $this->assertSame('My Blog', $settings['listing_title']);
        $this->assertFalse($settings['show_author']);
        $this->assertTrue($settings['show_publish_date']);
        $this->assertFalse($settings['show_related_posts']);
        $this->assertSame(1, $settings['posts_per_page']);
    }
}
