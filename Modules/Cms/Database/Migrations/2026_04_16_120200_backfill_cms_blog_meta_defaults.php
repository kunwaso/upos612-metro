<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsPageMeta;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'hero_text' => ['value' => ''],
            'meta_title' => ['value' => ''],
            'meta_keywords' => ['value' => ''],
            'category' => ['value' => ''],
            'banner_image' => ['value' => null],
        ];

        $blogIds = CmsPage::where('type', 'blog')->pluck('id');
        foreach ($blogIds as $blogId) {
            foreach ($defaults as $metaKey => $metaValue) {
                CmsPageMeta::firstOrCreate(
                    ['cms_page_id' => $blogId, 'meta_key' => $metaKey],
                    ['meta_value' => json_encode($metaValue)]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CmsPageMeta::whereIn('meta_key', [
            'hero_text',
            'meta_title',
            'meta_keywords',
            'category',
            'banner_image',
        ])->delete();
    }
};
