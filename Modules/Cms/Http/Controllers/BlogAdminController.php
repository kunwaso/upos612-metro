<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\Util;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Cms\Entities\CmsBlogComment;
use Modules\Cms\Entities\CmsBlogPost;
use Modules\Cms\Entities\CmsBlogSetting;
use Modules\Cms\Http\Requests\ModerateBlogV2CommentRequest;
use Modules\Cms\Http\Requests\StoreBlogV2PostRequest;
use Modules\Cms\Http\Requests\UpdateBlogV2PostRequest;
use Modules\Cms\Http\Requests\UpdateBlogV2SettingsRequest;
use Modules\Cms\Utils\BlogLocaleUtil;
use Modules\Cms\Utils\BlogV2Service;

class BlogAdminController extends Controller
{
    public function __construct(
        protected Util $commonUtil,
        protected BlogV2Service $blogV2Service
    ) {
    }

    public function settings()
    {
        $settings = CmsBlogSetting::current();
        $supportedLocales = BlogLocaleUtil::supported();

        return view('cms::blog.admin.settings')->with(compact('settings', 'supportedLocales'));
    }

    public function updateSettings(UpdateBlogV2SettingsRequest $request)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            DB::beginTransaction();
            $settings = CmsBlogSetting::current();
            $payload = [
                'show_author' => (bool) $request->boolean('show_author'),
                'show_publish_date' => (bool) $request->boolean('show_publish_date'),
                'show_related_posts' => (bool) $request->boolean('show_related_posts'),
                'show_comments' => (bool) $request->boolean('show_comments'),
                'show_likes' => (bool) $request->boolean('show_likes'),
                'show_social_share' => (bool) $request->boolean('show_social_share'),
                'require_comment_approval' => (bool) $request->boolean('require_comment_approval'),
                'posts_per_page' => max(1, (int) $request->input('posts_per_page', 12)),
                'default_locale' => BlogLocaleUtil::normalize((string) $request->input('default_locale', BlogLocaleUtil::default())),
            ];

            foreach (BlogLocaleUtil::supported() as $locale) {
                $payload['listing_title_'.$locale] = (string) $request->input("listing_title.$locale", '');
                $payload['listing_hero_text_'.$locale] = (string) $request->input("listing_hero_text.$locale", '');
                $payload['listing_meta_title_'.$locale] = (string) $request->input("listing_meta_title.$locale", '');
                $payload['listing_meta_description_'.$locale] = (string) $request->input("listing_meta_description.$locale", '');
                $payload['listing_meta_keywords_'.$locale] = (string) $request->input("listing_meta_keywords.$locale", '');
            }

            if ($request->hasFile('listing_banner_image')) {
                $payload['listing_banner_image'] = $this->commonUtil->uploadFile($request, 'listing_banner_image', 'cms', 'image');
            } else {
                $payload['listing_banner_image'] = $settings->listing_banner_image;
            }

            $settings->fill($payload);
            $settings->save();
            $this->blogV2Service->audit('settings.updated', null, ['default_locale' => $payload['default_locale']]);
            DB::commit();

            return redirect()
                ->route('cms.blog.admin.settings')
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.updated_success'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Blog settings update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
        }
    }

    public function postsIndex()
    {
        $posts = CmsBlogPost::query()
            ->with(['createdBy', 'variants'])
            ->orderBy('priority')
            ->orderByDesc('id')
            ->paginate(20);

        return view('cms::blog.admin.posts.index')->with(compact('posts'));
    }

    public function createPost()
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

        return view('cms::blog.admin.posts.form')->with(compact('post', 'supportedLocales', 'variantMap', 'sectionMapByLocale'));
    }

    public function storePost(StoreBlogV2PostRequest $request)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            DB::beginTransaction();
            $postPayload = [
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
                $postPayload['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');
            }

            $post = CmsBlogPost::query()->create($postPayload);
            foreach (BlogLocaleUtil::supported() as $locale) {
                $this->blogV2Service->upsertVariantFromRequest($post, $request, $locale);
            }
            $this->blogV2Service->updatePostStatusFromVariants($post);
            $this->blogV2Service->audit('post.created', $post, ['surface' => 'admin']);
            DB::commit();

            return redirect()
                ->route('cms.blog.admin.posts.edit', $post->id)
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.added_success'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Blog post create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
        }
    }

    public function editPost(CmsBlogPost $post)
    {
        $post->load(['variants.sections']);
        $supportedLocales = BlogLocaleUtil::supported();
        $variantMap = $post->variants->keyBy('locale');
        $sectionMapByLocale = [];
        foreach ($supportedLocales as $locale) {
            $variant = $variantMap->get($locale);
            $sectionMapByLocale[$locale] = $variant ? $this->blogV2Service->sectionMap($variant) : [];
        }

        return view('cms::blog.admin.posts.form')->with(compact('post', 'supportedLocales', 'variantMap', 'sectionMapByLocale'));
    }

    public function updatePost(UpdateBlogV2PostRequest $request, CmsBlogPost $post)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        try {
            DB::beginTransaction();

            $postPayload = [
                'priority' => max(0, (int) $request->input('priority', 0)),
                'allow_comments' => (bool) $request->boolean('allow_comments', true),
                'show_author_card' => (bool) $request->boolean('show_author_card', true),
                'show_social_share' => (bool) $request->boolean('show_social_share', true),
                'show_related_posts' => (bool) $request->boolean('show_related_posts', true),
                'related_posts_limit' => max(1, (int) $request->input('related_posts_limit', 4)),
            ];
            if ($request->hasFile('feature_image')) {
                $postPayload['feature_image'] = $this->commonUtil->uploadFile($request, 'feature_image', 'cms', 'image');
            }
            $post->update($postPayload);

            foreach (BlogLocaleUtil::supported() as $locale) {
                $this->blogV2Service->upsertVariantFromRequest($post, $request, $locale);
            }

            $this->blogV2Service->updatePostStatusFromVariants($post);
            $this->blogV2Service->audit('post.updated', $post, ['surface' => 'admin']);
            DB::commit();

            return redirect()
                ->route('cms.blog.admin.posts.edit', $post->id)
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.updated_success'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Blog post update failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
        }
    }

    public function destroyPost(CmsBlogPost $post)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        $this->blogV2Service->audit('post.deleted', $post, ['surface' => 'admin']);
        $post->delete();

        return redirect()
            ->route('cms.blog.admin.posts.index')
            ->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.deleted_success'),
            ]);
    }

    public function togglePublish(CmsBlogPost $post)
    {
        $hasPublished = $post->variants()->where('status', 'published')->exists();
        if ($hasPublished) {
            $post->variants()
                ->where('status', 'published')
                ->update([
                    'status' => 'draft',
                    'published_at' => null,
                ]);
        } else {
            $targetLocale = CmsBlogSetting::current()->default_locale ?: BlogLocaleUtil::default();
            $variant = $post->variants()->where('locale', BlogLocaleUtil::normalize($targetLocale))->first()
                ?: $post->variants()->first();
            if ($variant !== null) {
                $variant->status = 'published';
                $variant->published_at = Carbon::now();
                $variant->save();
            }
        }

        $this->blogV2Service->updatePostStatusFromVariants($post->refresh());
        $this->blogV2Service->audit('post.publish_toggled', $post, ['published' => ! $hasPublished, 'surface' => 'admin']);

        return redirect()
            ->route('cms.blog.admin.posts.index')
            ->with('status', [
                'success' => 1,
                'msg' => ! $hasPublished ? __('cms::lang.blog_post_published') : __('cms::lang.blog_post_unpublished'),
            ]);
    }

    public function commentsIndex()
    {
        $status = request()->query('status', 'pending');
        $comments = CmsBlogComment::query()
            ->with(['post.variants', 'user', 'moderator'])
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25);

        return view('cms::blog.admin.comments.index')->with(compact('comments', 'status'));
    }

    public function moderateComment(ModerateBlogV2CommentRequest $request, CmsBlogComment $comment)
    {
        $comment->status = $request->input('status');
        $comment->moderated_by = auth()->id();
        $comment->moderated_at = Carbon::now();
        $comment->save();

        $this->blogV2Service->audit('comment.moderated', $comment->post, [
            'comment_id' => $comment->id,
            'status' => $comment->status,
            'surface' => 'admin',
        ]);

        return redirect()
            ->route('cms.blog.admin.comments.index', ['status' => 'pending'])
            ->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ]);
    }
}
