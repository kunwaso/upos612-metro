<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\Util;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Cms\Entities\CmsBlogPost;
use Modules\Cms\Http\Requests\StoreBlogV2PostRequest;
use Modules\Cms\Http\Requests\UpdateBlogV2PostRequest;
use Modules\Cms\Utils\BlogLocaleUtil;
use Modules\Cms\Utils\BlogV2Service;

class BlogPortalController extends BlogFrontendController
{
    public function __construct(
        BlogV2Service $blogV2Service,
        protected Util $commonUtil
    ) {
        parent::__construct($blogV2Service);
    }

    public function index(string $locale)
    {
        $locale = BlogLocaleUtil::normalize($locale);
        $request = request();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $variantLocale = strtolower((string) $request->query('variant_locale', ''));

        $postsQuery = CmsBlogPost::query()
            ->with(['createdBy', 'variants'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    if (is_numeric($search)) {
                        $innerQuery
                            ->where('id', (int) $search)
                            ->orWhereHas('variants', function ($variantQuery) use ($search) {
                                $variantQuery->where('title', 'like', '%'.$search.'%');
                            });
                    } else {
                        $innerQuery->whereHas('variants', function ($variantQuery) use ($search) {
                            $variantQuery->where('title', 'like', '%'.$search.'%');
                        });
                    }
                });
            })
            ->when(in_array($status, ['draft', 'published', 'archived'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(
                in_array($variantLocale, BlogLocaleUtil::supported(), true),
                function ($query) use ($variantLocale) {
                    $query->whereHas('variants', function ($variantQuery) use ($variantLocale) {
                        $variantQuery->where('locale', $variantLocale);
                    });
                }
            );

        $posts = $postsQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $filters = [
            'q' => $search,
            'status' => $status,
            'variant_locale' => $variantLocale,
        ];

        return view('cms::blog.portal.index')->with([
            'locale' => $locale,
            'posts' => $posts,
            'filters' => $filters,
            'supportedLocales' => BlogLocaleUtil::supported(),
        ]);
    }

    public function create(string $locale)
    {
        $post = new CmsBlogPost([
            'allow_comments' => true,
            'show_author_card' => true,
            'show_social_share' => true,
            'show_related_posts' => true,
            'related_posts_limit' => 4,
            'priority' => 0,
        ]);
        $supportedLocales = BlogLocaleUtil::supported();
        $variantMap = [];
        $sectionMapByLocale = [];

        return view('cms::blog.portal.form')->with(compact('post', 'supportedLocales', 'variantMap', 'sectionMapByLocale', 'locale'));
    }

    public function store(StoreBlogV2PostRequest $request, string $locale)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            DB::beginTransaction();
            $payload = [
                'created_by' => auth()->id(),
                'priority' => max(0, (int) $request->input('priority', 0)),
                'allow_comments' => (bool) $request->boolean('allow_comments', true),
                'show_author_card' => (bool) $request->boolean('show_author_card', true),
                'show_social_share' => (bool) $request->boolean('show_social_share', true),
                'show_related_posts' => (bool) $request->boolean('show_related_posts', true),
                'related_posts_limit' => max(1, (int) $request->input('related_posts_limit', 4)),
                'status' => 'draft',
                'is_enabled' => false,
            ];
            if ($request->hasFile('feature_image')) {
                $payload['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');
            }

            $post = CmsBlogPost::query()->create($payload);
            foreach (BlogLocaleUtil::supported() as $supportedLocale) {
                $this->blogV2Service->upsertVariantFromRequest($post, $request, $supportedLocale);
            }
            $this->blogV2Service->updatePostStatusFromVariants($post);
            $this->blogV2Service->audit('post.created', $post, ['surface' => 'portal']);
            DB::commit();

            return redirect()
                ->route('cms.blog.portal.posts.edit', [
                    'locale' => BlogLocaleUtil::normalize($locale),
                    'post' => $post->id,
                ])
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.added_success'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Portal blog create failed', ['error' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function edit(string $locale, CmsBlogPost $post)
    {
        $post->load(['variants.sections']);
        $supportedLocales = BlogLocaleUtil::supported();
        $variantMap = $post->variants->keyBy('locale');
        $sectionMapByLocale = [];
        foreach ($supportedLocales as $supportedLocale) {
            $variant = $variantMap->get($supportedLocale);
            $sectionMapByLocale[$supportedLocale] = $variant ? $this->blogV2Service->sectionMap($variant) : [];
        }

        return view('cms::blog.portal.form')->with([
            'locale' => BlogLocaleUtil::normalize($locale),
            'post' => $post,
            'supportedLocales' => $supportedLocales,
            'variantMap' => $variantMap,
            'sectionMapByLocale' => $sectionMapByLocale,
        ]);
    }

    public function update(UpdateBlogV2PostRequest $request, string $locale, CmsBlogPost $post)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            DB::beginTransaction();
            $payload = [
                'priority' => max(0, (int) $request->input('priority', 0)),
                'allow_comments' => (bool) $request->boolean('allow_comments', true),
                'show_author_card' => (bool) $request->boolean('show_author_card', true),
                'show_social_share' => (bool) $request->boolean('show_social_share', true),
                'show_related_posts' => (bool) $request->boolean('show_related_posts', true),
                'related_posts_limit' => max(1, (int) $request->input('related_posts_limit', 4)),
            ];
            if ($request->hasFile('feature_image')) {
                $payload['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');
            }
            $post->update($payload);

            foreach (BlogLocaleUtil::supported() as $supportedLocale) {
                $this->blogV2Service->upsertVariantFromRequest($post, $request, $supportedLocale);
            }
            $this->blogV2Service->updatePostStatusFromVariants($post);
            $this->blogV2Service->audit('post.updated', $post, ['surface' => 'portal']);
            DB::commit();

            return redirect()
                ->route('cms.blog.portal.posts.edit', [
                    'locale' => BlogLocaleUtil::normalize($locale),
                    'post' => $post->id,
                ])
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.updated_success'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Portal blog update failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function togglePublish(string $locale, CmsBlogPost $post)
    {
        $hasPublished = $post->variants()->where('status', 'published')->exists();
        if ($hasPublished) {
            $post->variants()->where('status', 'published')->update([
                'status' => 'draft',
                'published_at' => null,
            ]);
        } else {
            $variant = $post->variants()->where('locale', BlogLocaleUtil::normalize($locale))->first()
                ?: $post->variants()->first();
            if ($variant !== null) {
                $variant->status = 'published';
                $variant->published_at = Carbon::now();
                $variant->save();
            }
        }

        $this->blogV2Service->updatePostStatusFromVariants($post->refresh());
        $this->blogV2Service->audit('post.publish_toggled', $post, ['published' => ! $hasPublished, 'surface' => 'portal']);

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => ! $hasPublished ? __('cms::lang.blog_post_published') : __('cms::lang.blog_post_unpublished'),
        ]);
    }

    public function preview(string $locale, CmsBlogPost $post)
    {
        $locale = BlogLocaleUtil::normalize($locale);
        $post->load(['createdBy', 'variants.sections']);
        $variant = $post->variants->where('locale', $locale)->first() ?: $post->variants->first();
        abort_if($variant === null, 404);

        $settings = \Modules\Cms\Entities\CmsBlogSetting::current();
        $settings->show_comments = false;
        $settings->show_likes = false;
        $sections = $this->blogV2Service->sectionMap($variant);
        $comments = collect();
        $relatedVariants = collect();
        $likesCount = 0;
        $userLiked = false;
        $alternates = [];
        foreach ($post->variants as $row) {
            $alternates[$row->locale] = route('cms.blog.portal.posts.preview', [
                'locale' => $row->locale,
                'post' => $post->id,
            ]);
        }

        return view('cms::frontend.blogs.show')->with(compact(
            'locale',
            'post',
            'variant',
            'settings',
            'sections',
            'comments',
            'relatedVariants',
            'likesCount',
            'userLiked',
            'alternates'
        ));
    }
}
