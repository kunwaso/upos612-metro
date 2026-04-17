<?php

namespace Modules\Cms\Utils;

use App\Utils\Util;
use Illuminate\Http\Request;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Entities\CmsPageMeta;

class BlogPostUtil extends Util
{
    public function listPosts()
    {
        return CmsPage::where('type', 'blog')
            ->orderBy('priority', 'asc')
            ->latest('id')
            ->get();
    }

    public function create(Request $request): CmsPage
    {
        $input = $request->only(['title', 'content', 'priority']);
        $input['type'] = 'blog';
        $input['tags'] = $request->input('tags');
        $input['meta_description'] = $request->input('meta_description');
        $input['is_enabled'] = (bool) $request->input('is_enabled', true);
        $input['created_by'] = $request->session()->get('user.id');
        $input['feature_image'] = $this->uploadFile($request, 'feature_image', 'cms', 'image');

        $post = CmsPage::create($input);
        $this->upsertMeta($post->id, $request);

        return $post;
    }

    public function update(CmsPage $post, Request $request): CmsPage
    {
        $input = $request->only(['title', 'content', 'priority']);
        $input['tags'] = $request->input('tags');
        $input['meta_description'] = $request->input('meta_description');
        $input['is_enabled'] = (bool) $request->input('is_enabled', false);

        if ($request->hasFile('feature_image')) {
            $input['feature_image'] = $this->uploadFile($request, 'feature_image', 'cms', 'image');
        }

        $post->update($input);
        $this->upsertMeta($post->id, $request);

        return $post->refresh();
    }

    public function delete(CmsPage $post): void
    {
        $post->delete();
    }

    public function togglePublish(CmsPage $post): CmsPage
    {
        $post->is_enabled = ! $post->is_enabled;
        $post->save();

        return $post;
    }

    public function metaForPost(CmsPage $post): array
    {
        $metaRows = CmsPageMeta::where('cms_page_id', $post->id)->get();
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row->meta_key] = json_decode($row->meta_value, true);
        }

        return [
            'hero_text' => $meta['hero_text']['value'] ?? '',
            'meta_title' => $meta['meta_title']['value'] ?? '',
            'meta_keywords' => $meta['meta_keywords']['value'] ?? '',
            'category' => $meta['category']['value'] ?? '',
            'banner_image' => $meta['banner_image']['value'] ?? null,
        ];
    }

    public function upsertMeta(int $postId, Request $request): void
    {
        $metaPayload = [
            'hero_text' => ['value' => (string) $request->input('hero_text', '')],
            'meta_title' => ['value' => (string) $request->input('meta_title', '')],
            'meta_keywords' => ['value' => (string) $request->input('meta_keywords', '')],
            'category' => ['value' => (string) $request->input('category', '')],
        ];

        if ($request->hasFile('banner_image')) {
            $metaPayload['banner_image'] = [
                'value' => $this->uploadFile($request, 'banner_image', 'cms', 'image'),
            ];
        } else {
            $existing = CmsPageMeta::where('cms_page_id', $postId)->where('meta_key', 'banner_image')->first();
            if ($existing) {
                $decoded = json_decode($existing->meta_value, true);
                $metaPayload['banner_image'] = ['value' => $decoded['value'] ?? null];
            }
        }

        foreach ($metaPayload as $metaKey => $metaValue) {
            CmsPageMeta::updateOrCreate(
                ['cms_page_id' => $postId, 'meta_key' => $metaKey],
                ['meta_value' => json_encode($metaValue)]
            );
        }
    }
}
