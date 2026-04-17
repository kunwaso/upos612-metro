<?php

namespace Modules\Cms\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Cms\Entities\CmsBlogAuditLog;
use Modules\Cms\Entities\CmsBlogPost;
use Modules\Cms\Entities\CmsBlogPostVariant;
use Modules\Cms\Entities\CmsBlogVariantSection;

class BlogV2Service
{
    public function sectionKeys(): array
    {
        return [
            'media',
            'lead',
            'quote_primary',
            'story',
            'quote_secondary',
            'closing',
            'tags',
        ];
    }

    public function updatePostStatusFromVariants(CmsBlogPost $post): void
    {
        $hasPublished = $post->variants()->where('status', 'published')->exists();
        $post->status = $hasPublished ? 'published' : 'draft';
        $post->is_enabled = $hasPublished;
        $post->save();
    }

    public function uniqueSlug(string $locale, string $title, ?int $ignoreVariantId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'blog-post';
        }

        $slug = $base;
        $increment = 1;
        while (
            CmsBlogPostVariant::query()
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->when($ignoreVariantId !== null, fn ($query) => $query->where('id', '!=', $ignoreVariantId))
                ->exists()
        ) {
            $increment++;
            $slug = $base.'-'.$increment;
        }

        return $slug;
    }

    public function upsertVariantFromRequest(CmsBlogPost $post, Request $request, string $locale): CmsBlogPostVariant
    {
        $locale = BlogLocaleUtil::normalize($locale);
        $variantData = [
            'title' => (string) $request->input("title.$locale"),
            'hero_text' => (string) $request->input("hero_text.$locale", ''),
            'excerpt' => (string) $request->input("excerpt.$locale", ''),
            'content_html' => (string) $request->input("content_html.$locale", ''),
            'meta_title' => (string) $request->input("meta_title.$locale", ''),
            'meta_description' => (string) $request->input("meta_description.$locale", ''),
            'meta_keywords' => (string) $request->input("meta_keywords.$locale", ''),
            'status' => (string) $request->input("variant_status.$locale", 'draft'),
        ];

        $variant = CmsBlogPostVariant::query()->firstOrNew([
            'cms_blog_post_id' => $post->id,
            'locale' => $locale,
        ]);

        if (! in_array($variantData['status'], ['draft', 'published', 'archived'], true)) {
            $variantData['status'] = 'draft';
        }

        $providedSlug = trim((string) $request->input("slug.$locale", ''));
        $targetSlug = $providedSlug !== '' ? Str::slug($providedSlug) : $this->uniqueSlug($locale, $variantData['title'], $variant->id);
        if ($targetSlug === '') {
            $targetSlug = $this->uniqueSlug($locale, $variantData['title'], $variant->id);
        }

        $variantData['slug'] = $this->uniqueSlug($locale, $targetSlug, $variant->id);
        $variantData['published_at'] = $variantData['status'] === 'published' ? (Carbon::now()) : null;
        $variant->fill($variantData);
        $variant->save();

        $this->upsertSectionsFromRequest($variant, $request, $locale);

        return $variant;
    }

    public function upsertSectionsFromRequest(CmsBlogPostVariant $variant, Request $request, string $locale): void
    {
        $locale = BlogLocaleUtil::normalize($locale);
        $tagsRaw = (string) $request->input("tags.$locale", '');
        $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw)), static fn ($tag) => $tag !== ''));

        $mediaPayload = [
            'hero_image' => $this->resolveSectionMedia($variant, $request, $locale, 'hero_image'),
            'body_image_one' => $this->resolveSectionMedia($variant, $request, $locale, 'body_image_one'),
            'split_image' => $this->resolveSectionMedia($variant, $request, $locale, 'split_image'),
            'body_image_two' => $this->resolveSectionMedia($variant, $request, $locale, 'body_image_two'),
        ];

        $sectionMap = [
            'media' => $mediaPayload,
            'lead' => [
                'text' => (string) $request->input("section_lead.$locale", ''),
            ],
            'quote_primary' => [
                'text' => (string) $request->input("section_quote_primary.$locale", ''),
            ],
            'story' => [
                'title' => (string) $request->input("section_story_title.$locale", ''),
                'body' => (string) $request->input("section_story_body.$locale", ''),
                'cta_label' => (string) $request->input("section_story_cta_label.$locale", ''),
                'cta_url' => (string) $request->input("section_story_cta_url.$locale", ''),
            ],
            'quote_secondary' => [
                'text' => (string) $request->input("section_quote_secondary.$locale", ''),
            ],
            'closing' => [
                'title' => (string) $request->input("section_closing_title.$locale", ''),
                'body' => (string) $request->input("section_closing_body.$locale", ''),
            ],
            'tags' => [
                'items' => $tags,
            ],
        ];

        foreach ($sectionMap as $sectionKey => $payload) {
            CmsBlogVariantSection::query()->updateOrCreate(
                [
                    'cms_blog_post_variant_id' => $variant->id,
                    'section_key' => $sectionKey,
                ],
                [
                    'payload' => $payload,
                ]
            );
        }
    }

    public function sectionMap(CmsBlogPostVariant $variant): array
    {
        $map = [];
        foreach ($variant->sections as $section) {
            $map[$section->section_key] = is_array($section->payload) ? $section->payload : [];
        }

        return $map;
    }

    public function resolveVariantForLocale(CmsBlogPost $post, string $locale): ?CmsBlogPostVariant
    {
        $locale = BlogLocaleUtil::normalize($locale);
        $variant = $post->variants()->where('locale', $locale)->where('status', 'published')->first();
        if ($variant !== null) {
            return $variant;
        }

        return $post->variants()
            ->where('status', 'published')
            ->orderByRaw('CASE WHEN locale = ? THEN 0 WHEN locale = ? THEN 1 ELSE 2 END', [$locale, BlogLocaleUtil::default()])
            ->first();
    }

    public function resolveSectionMedia(CmsBlogPostVariant $variant, Request $request, string $locale, string $key): ?string
    {
        if ($request->hasFile("$key.$locale")) {
            $uploaded = app(\App\Utils\Util::class)->uploadFile($request, "$key.$locale", 'cms', 'image');
            if (! empty($uploaded)) {
                return $uploaded;
            }
        }

        $existing = CmsBlogVariantSection::query()
            ->where('cms_blog_post_variant_id', $variant->id)
            ->where('section_key', 'media')
            ->first();
        $existingPayload = is_array($existing?->payload) ? $existing->payload : [];

        return Arr::get($existingPayload, $key);
    }

    public function audit(string $action, ?CmsBlogPost $post = null, array $metadata = []): void
    {
        CmsBlogAuditLog::query()->create([
            'cms_blog_post_id' => $post?->id,
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
