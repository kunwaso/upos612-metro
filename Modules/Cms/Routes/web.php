<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\BlogAdminController;
use Modules\Cms\Http\Controllers\BlogFrontendController;
use Modules\Cms\Http\Controllers\BlogPortalController;
use Modules\Cms\Http\Controllers\CmsController;
use Modules\Cms\Http\Controllers\CmsPageController;
use Modules\Cms\Http\Controllers\InstallController;
use Modules\Cms\Http\Controllers\SettingsController;
use Modules\Cms\Utils\BlogLocaleUtil;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
$blogLocales = BlogLocaleUtil::supported();
$blogLocaleRegex = implode('|', array_map(static fn ($locale) => preg_quote($locale, '/'), $blogLocales));
$defaultBlogLocale = BlogLocaleUtil::default();
$nonDefaultBlogLocales = array_values(array_filter(
    $blogLocales,
    static fn (string $locale) => $locale !== $defaultBlogLocale
));
$nonDefaultBlogLocaleRegex = implode('|', array_map(static fn ($locale) => preg_quote($locale, '/'), $nonDefaultBlogLocales));
Route::pattern('locale', $blogLocaleRegex);

Route::get('/', [CmsController::class, 'index'])->name('cms.home');
Route::get('shop/page/{page}', [CmsPageController::class, 'showPage']);
Route::get('shop/contact-us', [CmsController::class, 'contactUs'])->name('cms.contact.us');
Route::post('shop/submit-contact-form', [CmsController::class, 'postContactForm'])->name('cms.submit.contact.form');

// about us routes
Route::get('shop/about-us', [CmsController::class, 'aboutUs'])->name('cms.about.us');

// decor-store static storefront (reference HTML clones)
Route::get('shop/catalog', [CmsController::class, 'shopCatalog'])->name('cms.store.shop');
Route::get('shop/collections', [CmsController::class, 'shopCollections'])->name('cms.store.collections');
Route::redirect('shop/product', '/shop/catalog', 301);
Route::get('shop/product/{id}', [CmsController::class, 'shopProductShow'])
    ->whereNumber('id')
    ->name('cms.store.product.show');
Route::get('shop/product/{id}/request-quote', [CmsController::class, 'rfqShow'])
    ->whereNumber('id')
    ->name('cms.store.rfq.show');
Route::post('shop/product/{id}/request-quote', [CmsController::class, 'rfqStore'])
    ->whereNumber('id')
    ->middleware('throttle:10,1')
    ->name('cms.store.rfq.store');
Route::redirect('shop/cart', '/shop/catalog', 301)->name('cms.store.cart');
Route::redirect('shop/checkout', '/shop/catalog', 301)->name('cms.store.checkout');
Route::get('shop/faq', [CmsController::class, 'shopFaq'])->name('cms.store.faq');

// products routes
Route::get('shop/products/bao-bi-cuon', [CmsController::class, 'baobicuon'])->name('cms.products.baobicuon');
Route::get('shop/products/hop-thung-carton', [CmsController::class, 'hopthungcarton'])->name('cms.products.hopthungcarton');
Route::get('shop/products/day-dai', [CmsController::class, 'daydai'])->name('cms.products.daydai');
Route::get('shop/products/air-silicagel', [CmsController::class, 'airsilicagel'])->name('cms.products.airsilicagel');
Route::get('shop/products/sanphamkhac', [CmsController::class, 'sanphamkhac'])->name('cms.products.sanphamkhac');

// Canonical public blog routes for default locale (non-prefixed).
Route::get('shop/blogs', [BlogFrontendController::class, 'indexDefault'])->name('cms.blogs.index');
Route::get('shop/blog/{slug}-{id}', [BlogFrontendController::class, 'showDefault'])
    ->whereNumber('id')
    ->name('cms.blog.show');
Route::post('shop/blog/{slug}-{id}/like', [BlogFrontendController::class, 'toggleLikeDefault'])
    ->whereNumber('id')
    ->name('cms.blog.like');
Route::post('shop/blog/{slug}-{id}/comments', [BlogFrontendController::class, 'storeCommentDefault'])
    ->whereNumber('id')
    ->middleware('auth')
    ->name('cms.blog.comments.store');

if (! empty($nonDefaultBlogLocales)) {
    Route::prefix('{locale}')
        ->where(['locale' => $nonDefaultBlogLocaleRegex])
        ->middleware('cms.blog.locale')
        ->group(function () {
            Route::get('shop/blogs', [BlogFrontendController::class, 'index'])->name('cms.blogs.index.locale');
            Route::get('shop/blog/{slug}-{id}', [BlogFrontendController::class, 'show'])
                ->whereNumber('id')
                ->name('cms.blog.show.locale');
            Route::post('shop/blog/{slug}-{id}/like', [BlogFrontendController::class, 'toggleLike'])
                ->whereNumber('id')
                ->name('cms.blog.like.locale');
            Route::post('shop/blog/{slug}-{id}/comments', [BlogFrontendController::class, 'storeComment'])
                ->whereNumber('id')
                ->middleware('auth')
                ->name('cms.blog.comments.store.locale');
        });
}

Route::prefix('{locale}')
    ->where(['locale' => $blogLocaleRegex])
    ->middleware('cms.blog.locale')
    ->group(function () {
        Route::prefix('shop/my-blog')
            ->middleware('auth')
            ->name('cms.blog.portal.')
            ->group(function () {
                Route::get('posts', [BlogPortalController::class, 'index'])
                    ->middleware('can:cms.blog.posts.view')
                    ->name('posts.index');
                Route::get('posts/create', [BlogPortalController::class, 'create'])
                    ->middleware('can:cms.blog.posts.create')
                    ->name('posts.create');
                Route::post('posts/create', [BlogPortalController::class, 'store'])
                    ->middleware('can:cms.blog.posts.create')
                    ->name('posts.store');
                Route::get('posts/{post}/edit', [BlogPortalController::class, 'edit'])
                    ->middleware('can:cms.blog.posts.update')
                    ->name('posts.edit');
                Route::put('posts/{post}/edit', [BlogPortalController::class, 'update'])
                    ->middleware('can:cms.blog.posts.update')
                    ->name('posts.update');
                Route::post('posts/{post}/publish-toggle', [BlogPortalController::class, 'togglePublish'])
                    ->middleware('can:cms.blog.posts.publish')
                    ->name('posts.publish-toggle');
                Route::get('posts/{post}/preview', [BlogPortalController::class, 'preview'])
                    ->middleware('can:cms.blog.posts.view')
                    ->name('posts.preview');
            });
    });

if ($defaultBlogLocale === 'vi') {
    Route::get('vi/shop/blogs', static function () {
        return redirect()->route('cms.blogs.index', [], 301);
    });
    Route::get('vi/shop/blog/{slug}-{id}', static function (string $slug, int $id) {
        return redirect()->route('cms.blog.show', [
            'slug' => $slug,
            'id' => $id,
        ], 301);
    })->whereNumber('id');
}

// Backward compatibility redirects: c/* -> shop/*
Route::redirect('c/page/{page}', 'shop/page/{page}', 301);
Route::get('c/blogs', static function () {
    return redirect()->route('cms.blogs.index', [], 301);
});
Route::get('c/blog/{slug}-{id}', static function (string $slug, int $id) {
    return redirect()->route('cms.blog.show', [
        'slug' => $slug,
        'id' => $id,
    ], 301);
})->whereNumber('id');
Route::redirect('c/contact-us', 'shop/contact-us', 301);
Route::post('c/submit-contact-form', [CmsController::class, 'postContactForm']);
Route::redirect('c/products/bao-bi-cuon', 'shop/products/bao-bi-cuon', 301);

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu', 'two_factor.verified')
    ->prefix('cms')
    ->group(function () {
        Route::middleware('can:superadmin')->group(function () {
            Route::get('install', [InstallController::class, 'index']);
            Route::post('install', [InstallController::class, 'install']);
            Route::get('install/uninstall', [InstallController::class, 'uninstall']);
            Route::get('install/update', [InstallController::class, 'update']);
        });

        Route::resource('cms-page', CmsPageController::class)->except(['show']);

        // Keep legacy non-blog site details untouched.
        Route::get('site-details', [SettingsController::class, 'index'])
            ->middleware('can:cms.blog.settings.view')
            ->name('cms.site-details.index');
        Route::post('site-details', [SettingsController::class, 'store'])
            ->middleware('can:cms.blog.settings.update')
            ->name('cms.site-details.store');

        // Legacy blog admin paths redirect/forward into the new canonical /cms/blog/* area.
        Route::prefix('site-details/blog-posts')->name('cms.site-details.blog-posts.')->group(function () {
            Route::get('/', static fn () => redirect()->route('cms.blog.admin.posts.index', [], 301))
                ->middleware('can:cms.blog.posts.view')
                ->name('index');
            Route::post('/', [BlogAdminController::class, 'storePost'])
                ->middleware('can:cms.blog.posts.create')
                ->name('store');
            Route::get('{post}/edit', static fn ($post) => redirect()->route('cms.blog.admin.posts.edit', $post, 301))
                ->middleware('can:cms.blog.posts.update')
                ->name('edit');
            Route::put('{post}', [BlogAdminController::class, 'updatePost'])
                ->middleware('can:cms.blog.posts.update')
                ->name('update');
            Route::delete('{post}', [BlogAdminController::class, 'destroyPost'])
                ->middleware('can:cms.blog.posts.delete')
                ->name('destroy');
            Route::post('{post}/toggle-publish', [BlogAdminController::class, 'togglePublish'])
                ->middleware('can:cms.blog.posts.publish')
                ->name('toggle-publish');
        });

        Route::prefix('blog')->name('cms.blog.admin.')->group(function () {
            Route::get('settings', [BlogAdminController::class, 'settings'])
                ->middleware('can:cms.blog.settings.view')
                ->name('settings');
            Route::post('settings', [BlogAdminController::class, 'updateSettings'])
                ->middleware('can:cms.blog.settings.update')
                ->name('settings.update');

            Route::get('posts', [BlogAdminController::class, 'postsIndex'])
                ->middleware('can:cms.blog.posts.view')
                ->name('posts.index');
            Route::get('posts/create', [BlogAdminController::class, 'createPost'])
                ->middleware('can:cms.blog.posts.create')
                ->name('posts.create');
            Route::post('posts/create', [BlogAdminController::class, 'storePost'])
                ->middleware('can:cms.blog.posts.create')
                ->name('posts.store');
            Route::get('posts/{post}/edit', [BlogAdminController::class, 'editPost'])
                ->middleware('can:cms.blog.posts.update')
                ->name('posts.edit');
            Route::put('posts/{post}', [BlogAdminController::class, 'updatePost'])
                ->middleware('can:cms.blog.posts.update')
                ->name('posts.update');
            Route::delete('posts/{post}', [BlogAdminController::class, 'destroyPost'])
                ->middleware('can:cms.blog.posts.delete')
                ->name('posts.destroy');
            Route::post('posts/{post}/toggle-publish', [BlogAdminController::class, 'togglePublish'])
                ->middleware('can:cms.blog.posts.publish')
                ->name('posts.toggle-publish');

            Route::get('comments', [BlogAdminController::class, 'commentsIndex'])
                ->middleware('can:cms.blog.posts.publish')
                ->name('comments.index');
            Route::post('comments/{comment}/moderate', [BlogAdminController::class, 'moderateComment'])
                ->middleware('can:cms.blog.posts.publish')
                ->name('comments.moderate');
        });
    });
