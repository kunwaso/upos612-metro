<?php

namespace Modules\Cms\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;

class DataController extends Controller
{
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'cms.blog.settings.view',
                'label' => __('cms::lang.permission_blog_settings_view'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.settings.update',
                'label' => __('cms::lang.permission_blog_settings_update'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.posts.view',
                'label' => __('cms::lang.permission_blog_posts_view'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.posts.create',
                'label' => __('cms::lang.permission_blog_posts_create'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.posts.update',
                'label' => __('cms::lang.permission_blog_posts_update'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.posts.delete',
                'label' => __('cms::lang.permission_blog_posts_delete'),
                'default' => false,
            ],
            [
                'value' => 'cms.blog.posts.publish',
                'label' => __('cms::lang.permission_blog_posts_publish'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds cms menus
     *
     * @return null
     */
    public function modifyAdminMenu(): void
    {
        $module_util = new ModuleUtil();
        if (! auth()->check() || ! $module_util->isModuleInstalled('Cms')) {
            return;
        }

        if (
            ! auth()->user()->can('cms.blog.settings.view')
            && ! auth()->user()->can('cms.blog.posts.view')
            && ! auth()->user()->can('superadmin')
        ) {
            return;
        }

        $menuManager = app('menus');
        $menuManager->modify('admin-sidebar-menu', function ($menu) {
            $menu->url(
                action([\Modules\Cms\Http\Controllers\BlogAdminController::class, 'postsIndex']),
                __('cms::lang.cms'),
                ['icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-brand-windows"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17.8 20l-12 -1.5c-1 -.1 -1.8 -.9 -1.8 -1.9v-9.2c0 -1 .8 -1.8 1.8 -1.9l12 -1.5c1.2 -.1 2.2 .8 2.2 1.9v12.1c0 1.2 -1.1 2.1 -2.2 1.9z" /><path d="M12 5l0 14" /><path d="M4 12l16 0" /></svg>', 'style' => config('app.env') == 'demo' ? 'background-color: #9E458B !important;color:white' : '', 'active' => request()->segment(1) == 'cms']
            )->order(100);
        });
    }
}
