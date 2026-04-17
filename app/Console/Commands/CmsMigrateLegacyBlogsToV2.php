<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cms\Entities\CmsBlogPost;
use Modules\Cms\Entities\CmsBlogPostVariant;
use Modules\Cms\Entities\CmsBlogSetting;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsPageMeta;
use Modules\Cms\Entities\CmsSiteDetail;
use Modules\Cms\Utils\BlogLocaleUtil;
use Modules\Cms\Utils\BlogV2Service;

class CmsMigrateLegacyBlogsToV2 extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cms:blog-v2-migrate-legacy {--dry-run : Validate migration without persisting}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate legacy cms_pages(type=blog) records into CMS blog v2 tables.';

    public function handle(BlogV2Service $blogV2Service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $legacyPosts = CmsPage::query()->where('type', 'blog')->orderBy('id')->get();

        if ($legacyPosts->isEmpty()) {
            $this->warn('No legacy blog rows found.');
            return self::SUCCESS;
        }

        $this->info('Migrating '.$legacyPosts->count().' legacy blog posts...');
        DB::beginTransaction();
        try {
            $this->migrateSettings();
            foreach ($legacyPosts as $legacyPost) {
                $this->migratePost($legacyPost, $blogV2Service);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry-run complete. Transaction rolled back.');
            } else {
                DB::commit();
                $this->info('Migration complete.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Migration failed: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function migrateSettings(): void
    {
        $legacySettings = CmsSiteDetail::getValue('blog_settings');
        if (! is_array($legacySettings)) {
            $legacySettings = [];
        }

        CmsBlogSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'listing_title_en' => (string) ($legacySettings['listing_title'] ?? __('cms::lang.blog')),
                'listing_title_vi' => (string) ($legacySettings['listing_title'] ?? __('cms::lang.blog')),
                'listing_hero_text_en' => (string) ($legacySettings['listing_hero_text'] ?? ''),
                'listing_hero_text_vi' => (string) ($legacySettings['listing_hero_text'] ?? ''),
                'listing_banner_image' => (string) ($legacySettings['listing_banner_image'] ?? ''),
                'listing_meta_title_en' => (string) ($legacySettings['listing_meta_title'] ?? ''),
                'listing_meta_title_vi' => (string) ($legacySettings['listing_meta_title'] ?? ''),
                'listing_meta_description_en' => (string) ($legacySettings['listing_meta_description'] ?? ''),
                'listing_meta_description_vi' => (string) ($legacySettings['listing_meta_description'] ?? ''),
                'listing_meta_keywords_en' => (string) ($legacySettings['listing_meta_keywords'] ?? ''),
                'listing_meta_keywords_vi' => (string) ($legacySettings['listing_meta_keywords'] ?? ''),
                'show_author' => (bool) ($legacySettings['show_author'] ?? true),
                'show_publish_date' => (bool) ($legacySettings['show_publish_date'] ?? true),
                'show_related_posts' => (bool) ($legacySettings['show_related_posts'] ?? true),
                'show_comments' => true,
                'show_likes' => true,
                'show_social_share' => true,
                'require_comment_approval' => true,
                'posts_per_page' => max(1, (int) ($legacySettings['posts_per_page'] ?? 12)),
                'default_locale' => BlogLocaleUtil::default(),
            ]
        );
    }

    private function migratePost(CmsPage $legacyPost, BlogV2Service $blogV2Service): void
    {
        $blogPost = CmsBlogPost::query()->updateOrCreate(
            ['legacy_cms_page_id' => $legacyPost->id],
            [
                'created_by' => $legacyPost->created_by,
                'status' => (int) $legacyPost->is_enabled === 1 ? 'published' : 'draft',
                'is_enabled' => (int) $legacyPost->is_enabled === 1,
                'priority' => (int) ($legacyPost->priority ?? 0),
                'feature_image' => $legacyPost->feature_image,
                'allow_comments' => true,
                'show_author_card' => true,
                'show_social_share' => true,
                'show_related_posts' => true,
                'related_posts_limit' => 4,
            ]
        );

        $legacyMeta = $this->metaMapForLegacyPost((int) $legacyPost->id);
        foreach (BlogLocaleUtil::supported() as $locale) {
            $title = (string) $legacyPost->title;
            $variant = CmsBlogPostVariant::query()->firstOrNew([
                'cms_blog_post_id' => $blogPost->id,
                'locale' => $locale,
            ]);

            $slugSeed = Str::slug($title) ?: 'blog-post-'.$legacyPost->id;
            $variant->fill([
                'title' => $title,
                'slug' => $blogV2Service->uniqueSlug($locale, $slugSeed, $variant->id),
                'hero_text' => $this->metaValue($legacyMeta, 'hero_text'),
                'excerpt' => Str::limit(strip_tags((string) $legacyPost->content), 180),
                'content_html' => (string) $legacyPost->content,
                'meta_title' => $this->metaValue($legacyMeta, 'meta_title'),
                'meta_description' => (string) $legacyPost->meta_description,
                'meta_keywords' => $this->metaValue($legacyMeta, 'meta_keywords'),
                'status' => (int) $legacyPost->is_enabled === 1 ? 'published' : 'draft',
                'published_at' => (int) $legacyPost->is_enabled === 1 ? now() : null,
            ]);
            $variant->save();

            $mediaPayload = [
                'hero_image' => $legacyPost->feature_image ?: null,
                'body_image_one' => $this->metaValue($legacyMeta, 'banner_image'),
                'split_image' => null,
                'body_image_two' => null,
            ];

            $sectionMap = [
                'media' => $mediaPayload,
                'lead' => [
                    'text' => $this->metaValue($legacyMeta, 'hero_text'),
                ],
                'quote_primary' => [
                    'text' => '',
                ],
                'story' => [
                    'title' => '',
                    'body' => '',
                    'cta_label' => '',
                    'cta_url' => '',
                ],
                'quote_secondary' => [
                    'text' => '',
                ],
                'closing' => [
                    'title' => '',
                    'body' => '',
                ],
                'tags' => [
                    'items' => $this->extractTags((string) ($legacyPost->tags ?? '')),
                ],
            ];

            foreach ($sectionMap as $sectionKey => $payload) {
                $variant->sections()->updateOrCreate(
                    ['section_key' => $sectionKey],
                    ['payload' => $payload]
                );
            }
        }

        $blogV2Service->updatePostStatusFromVariants($blogPost);
    }

    private function extractTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    private function metaMapForLegacyPost(int $legacyPageId): array
    {
        $map = [];
        $rows = CmsPageMeta::query()->where('cms_page_id', $legacyPageId)->get();
        foreach ($rows as $row) {
            $decoded = json_decode((string) $row->meta_value, true);
            $map[$row->meta_key] = is_array($decoded) ? $decoded : [];
        }

        return $map;
    }

    private function metaValue(array $metaMap, string $key): string
    {
        $value = $metaMap[$key]['value'] ?? '';
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
