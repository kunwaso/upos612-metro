<?php

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Entities\CmsBlogComment;
use Modules\Cms\Entities\CmsBlogLike;
use Modules\Cms\Entities\CmsBlogPost;
use Modules\Cms\Entities\CmsBlogPostVariant;
use Modules\Cms\Entities\CmsBlogSetting;
use Modules\Cms\Http\Requests\StoreBlogV2CommentRequest;
use Modules\Cms\Utils\BlogLocaleUtil;
use Modules\Cms\Utils\BlogV2Service;

class BlogFrontendController extends Controller
{
    public function __construct(protected BlogV2Service $blogV2Service)
    {
    }

    public function indexDefault()
    {
        return $this->index(BlogLocaleUtil::default());
    }

    public function index(string $locale)
    {
        $locale = BlogLocaleUtil::normalize($locale);
        app()->setLocale($locale);
        $settings = CmsBlogSetting::current();
        $postsPerPage = max(1, (int) $settings->posts_per_page);

        $blogs = CmsBlogPostVariant::query()
            ->with(['post.createdBy'])
            ->where('locale', $locale)
            ->where('status', 'published')
            ->whereHas('post', fn ($query) => $query->where('is_enabled', 1))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($postsPerPage);

        $alternateIndex = [];
        foreach (BlogLocaleUtil::supported() as $supportedLocale) {
            $alternateIndex[$supportedLocale] = $this->blogIndexUrl($supportedLocale);
        }
        $defaultLocale = BlogLocaleUtil::default();

        return view('cms::frontend.blogs.index')
            ->with(compact('locale', 'blogs', 'settings', 'alternateIndex', 'defaultLocale'));
    }

    public function showDefault(Request $request, string $slug, int $id)
    {
        return $this->show($request, BlogLocaleUtil::default(), $slug, $id);
    }

    public function show(Request $request, string $locale, string $slug, int $id)
    {
        $locale = BlogLocaleUtil::normalize($locale);
        app()->setLocale($locale);
        $post = CmsBlogPost::query()
            ->with(['createdBy', 'variants.sections'])
            ->where('is_enabled', 1)
            ->findOrFail($id);

        $variant = $this->blogV2Service->resolveVariantForLocale($post, $locale);
        abort_if($variant === null, 404);

        if ($slug !== $variant->slug || $locale !== $variant->locale) {
            return redirect($this->blogShowUrl($variant->locale, $variant->slug, $post->id), 301);
        }

        $settings = CmsBlogSetting::current();
        $sections = $this->blogV2Service->sectionMap($variant);
        $comments = collect();
        if ($settings->show_comments && $post->allow_comments) {
            $comments = CmsBlogComment::query()
                ->with([
                    'user',
                    'children' => fn ($query) => $query->where('status', 'approved')->with('user'),
                ])
                ->where('cms_blog_post_id', $post->id)
                ->where('status', 'approved')
                ->whereNull('parent_id')
                ->orderByDesc('id')
                ->get();
        }

        $relatedVariants = collect();
        if ($settings->show_related_posts && $post->show_related_posts) {
            $relatedVariants = CmsBlogPostVariant::query()
                ->with('post')
                ->where('locale', $locale)
                ->where('status', 'published')
                ->where('id', '!=', $variant->id)
                ->whereHas('post', fn ($query) => $query->where('is_enabled', 1)->where('id', '!=', $post->id))
                ->limit(max(1, (int) $post->related_posts_limit))
                ->orderByDesc('published_at')
                ->get();
        }

        $sessionKey = (string) $request->session()->getId();
        if ($sessionKey === '') {
            $request->session()->start();
            $sessionKey = (string) $request->session()->getId();
        }

        $likesCount = CmsBlogLike::query()
            ->where('cms_blog_post_id', $post->id)
            ->where('cms_blog_post_variant_id', $variant->id)
            ->count();
        $userLiked = CmsBlogLike::query()
            ->where('cms_blog_post_id', $post->id)
            ->where('cms_blog_post_variant_id', $variant->id)
            ->where('session_key', $sessionKey)
            ->exists();

        $alternates = [];
        foreach ($post->variants as $row) {
            if ($row->status !== 'published') {
                continue;
            }
            $alternates[$row->locale] = $this->blogShowUrl($row->locale, $row->slug, $post->id);
        }
        $defaultLocale = BlogLocaleUtil::default();

        return view('cms::frontend.blogs.show')
            ->with(compact(
                'locale',
                'post',
                'variant',
                'settings',
                'sections',
                'comments',
                'relatedVariants',
                'likesCount',
                'userLiked',
                'alternates',
                'defaultLocale'
            ));
    }

    public function toggleLikeDefault(Request $request, string $slug, int $id)
    {
        return $this->toggleLike($request, BlogLocaleUtil::default(), $slug, $id);
    }

    public function toggleLike(Request $request, string $locale, string $slug, int $id)
    {
        abort_unless(config('cms.blog_features.likes_enabled', true), 404);
        $locale = BlogLocaleUtil::normalize($locale);
        app()->setLocale($locale);
        $post = CmsBlogPost::query()->where('is_enabled', 1)->findOrFail($id);
        $variant = $this->blogV2Service->resolveVariantForLocale($post, $locale);
        abort_if($variant === null || $variant->slug !== $slug, 404);
        $settings = CmsBlogSetting::current();
        abort_unless($settings->show_likes, 403);

        $sessionKey = (string) $request->session()->getId();
        if ($sessionKey === '') {
            $request->session()->start();
            $sessionKey = (string) $request->session()->getId();
        }

        $liked = false;
        DB::transaction(function () use ($post, $variant, $sessionKey, &$liked) {
            $existing = CmsBlogLike::query()
                ->where('cms_blog_post_id', $post->id)
                ->where('cms_blog_post_variant_id', $variant->id)
                ->where('session_key', $sessionKey)
                ->first();

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                CmsBlogLike::query()->create([
                    'cms_blog_post_id' => $post->id,
                    'cms_blog_post_variant_id' => $variant->id,
                    'session_key' => $sessionKey,
                ]);
                $liked = true;
            }
        });

        if ($request->expectsJson()) {
            $count = CmsBlogLike::query()
                ->where('cms_blog_post_id', $post->id)
                ->where('cms_blog_post_variant_id', $variant->id)
                ->count();

            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $count,
            ]);
        }

        return redirect()->back();
    }

    public function storeCommentDefault(StoreBlogV2CommentRequest $request, string $slug, int $id)
    {
        return $this->storeComment($request, BlogLocaleUtil::default(), $slug, $id);
    }

    public function storeComment(StoreBlogV2CommentRequest $request, string $locale, string $slug, int $id)
    {
        abort_unless(config('cms.blog_features.comments_enabled', true), 404);
        $locale = BlogLocaleUtil::normalize($locale);
        app()->setLocale($locale);
        $post = CmsBlogPost::query()->where('is_enabled', 1)->findOrFail($id);
        $variant = $this->blogV2Service->resolveVariantForLocale($post, $locale);
        abort_if($variant === null || $variant->slug !== $slug, 404);

        $settings = CmsBlogSetting::current();
        abort_unless($settings->show_comments && $post->allow_comments, 403);

        $comment = CmsBlogComment::query()->create([
            'cms_blog_post_id' => $post->id,
            'parent_id' => $request->input('parent_id'),
            'user_id' => auth()->id(),
            'comment' => (string) $request->input('comment'),
            'status' => 'pending',
        ]);

        $this->blogV2Service->audit('comment.created', $post, [
            'comment_id' => $comment->id,
            'locale' => $variant->locale,
            'surface' => 'public',
        ]);

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => __('cms::lang.blog_comment_pending_review'),
        ]);
    }

    private function blogIndexUrl(string $locale): string
    {
        if ($locale === BlogLocaleUtil::default()) {
            return route('cms.blogs.index');
        }

        return route('cms.blogs.index.locale', ['locale' => $locale]);
    }

    private function blogShowUrl(string $locale, string $slug, int $id): string
    {
        if ($locale === BlogLocaleUtil::default()) {
            return route('cms.blog.show', ['slug' => $slug, 'id' => $id]);
        }

        return route('cms.blog.show.locale', ['locale' => $locale, 'slug' => $slug, 'id' => $id]);
    }
}
