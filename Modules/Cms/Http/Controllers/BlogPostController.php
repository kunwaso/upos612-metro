<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\Util;
use Illuminate\Routing\Controller;
use Modules\Cms\Entities\CmsPage;
use Modules\Cms\Http\Requests\StoreBlogPostRequest;
use Modules\Cms\Http\Requests\ToggleBlogPostPublishRequest;
use Modules\Cms\Http\Requests\UpdateBlogPostRequest;
use Modules\Cms\Utils\BlogPostUtil;
use Modules\Cms\Utils\BlogSettingsUtil;

class BlogPostController extends Controller
{
    protected $commonUtil;

    protected $blogPostUtil;

    protected $blogSettingsUtil;

    public function __construct(Util $commonUtil, BlogPostUtil $blogPostUtil, BlogSettingsUtil $blogSettingsUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->blogPostUtil = $blogPostUtil;
        $this->blogSettingsUtil = $blogSettingsUtil;
    }

    public function index()
    {
        $details = \Modules\Cms\Entities\CmsSiteDetail::getSiteDetails();
        $logo = \Modules\Cms\Entities\CmsSiteDetail::getValue('logo', false);
        $blogSettings = $this->blogSettingsUtil->getSettings();
        $blogPosts = $this->blogPostUtil->listPosts();

        return view('cms::settings.index')->with(compact('details', 'logo', 'blogSettings', 'blogPosts'));
    }

    public function store(StoreBlogPostRequest $request)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        $this->blogPostUtil->create($request);

        return redirect()
            ->route('cms.site-details.blog-posts.index')
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function edit(CmsPage $blogPost)
    {
        abort_if($blogPost->type !== 'blog', 404);

        $meta = $this->blogPostUtil->metaForPost($blogPost);

        return view('cms::settings.blog_post_edit')->with(compact('blogPost', 'meta'));
    }

    public function update(UpdateBlogPostRequest $request, CmsPage $blogPost)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        abort_if($blogPost->type !== 'blog', 404);
        $this->blogPostUtil->update($blogPost, $request);

        return redirect()
            ->route('cms.site-details.blog-posts.index')
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    public function destroy(CmsPage $blogPost)
    {
        $notAllowedInDemo = $this->commonUtil->notAllowedInDemo();
        if (! empty($notAllowedInDemo)) {
            return $notAllowedInDemo;
        }

        abort_if($blogPost->type !== 'blog', 404);
        $this->blogPostUtil->delete($blogPost);

        return redirect()
            ->route('cms.site-details.blog-posts.index')
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.deleted_success')]);
    }

    public function togglePublish(ToggleBlogPostPublishRequest $request, CmsPage $blogPost)
    {
        abort_if($blogPost->type !== 'blog', 404);
        $post = $this->blogPostUtil->togglePublish($blogPost);

        return redirect()
            ->route('cms.site-details.blog-posts.index')
            ->with('status', [
                'success' => 1,
                'msg' => $post->is_enabled ? __('cms::lang.blog_post_published') : __('cms::lang.blog_post_unpublished'),
            ]);
    }
}
