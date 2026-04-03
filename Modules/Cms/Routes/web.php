<?php

use App\Http\Controllers\CmsController;
use App\Http\Controllers\CmsPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [Modules\Cms\Http\Controllers\CmsController::class, 'index'])->name('cms.home');
Route::get('shop/page/{page}', [Modules\Cms\Http\Controllers\CmsPageController::class, 'showPage']);
Route::get('shop/blogs', [Modules\Cms\Http\Controllers\CmsController::class, 'getBlogList'])->name('cms.blogs.index');
Route::get('shop/blog/{slug}-{id}', [Modules\Cms\Http\Controllers\CmsController::class, 'viewBlog'])->name('cms.blog.show');
Route::get('shop/contact-us', [Modules\Cms\Http\Controllers\CmsController::class, 'contactUs'])->name('cms.contact.us');
Route::post('shop/submit-contact-form', [Modules\Cms\Http\Controllers\CmsController::class, 'postContactForm'])->name('cms.submit.contact.form');

// about us routes
Route::get('shop/about-us', [Modules\Cms\Http\Controllers\CmsController::class, 'aboutUs'])->name('cms.about.us');

// decor-store static storefront (reference HTML clones)
Route::get('shop/catalog', [Modules\Cms\Http\Controllers\CmsController::class, 'shopCatalog'])->name('cms.store.shop');
Route::get('shop/collections', [Modules\Cms\Http\Controllers\CmsController::class, 'shopCollections'])->name('cms.store.collections');
Route::redirect('shop/product', '/shop/catalog', 301);
Route::get('shop/product/{id}', [Modules\Cms\Http\Controllers\CmsController::class, 'shopProductShow'])
    ->whereNumber('id')
    ->name('cms.store.product.show');
Route::get('shop/product/{id}/request-quote', [Modules\Cms\Http\Controllers\CmsController::class, 'rfqShow'])
    ->whereNumber('id')
    ->name('cms.store.rfq.show');
Route::post('shop/product/{id}/request-quote', [Modules\Cms\Http\Controllers\CmsController::class, 'rfqStore'])
    ->whereNumber('id')
    ->middleware('throttle:10,1')
    ->name('cms.store.rfq.store');
Route::redirect('shop/cart', '/shop/catalog', 301)->name('cms.store.cart');
Route::redirect('shop/checkout', '/shop/catalog', 301)->name('cms.store.checkout');
Route::get('shop/account', [Modules\Cms\Http\Controllers\CmsController::class, 'shopAccount'])->name('cms.store.account');
Route::get('shop/wishlist', [Modules\Cms\Http\Controllers\CmsController::class, 'shopWishlist'])->name('cms.store.wishlist');
Route::get('shop/faq', [Modules\Cms\Http\Controllers\CmsController::class, 'shopFaq'])->name('cms.store.faq');

// products routes
Route::get('shop/products/bao-bi-cuon', [Modules\Cms\Http\Controllers\CmsController::class, 'baobicuon'])->name('cms.products.baobicuon');
Route::get('shop/products/hop-thung-carton', [Modules\Cms\Http\Controllers\CmsController::class, 'hopthungcarton'])->name('cms.products.hopthungcarton');
Route::get('shop/products/day-dai', [Modules\Cms\Http\Controllers\CmsController::class, 'daydai'])->name('cms.products.daydai');
Route::get('shop/products/air-silicagel', [Modules\Cms\Http\Controllers\CmsController::class, 'airsilicagel'])->name('cms.products.airsilicagel');
Route::get('shop/products/sanphamkhac', [Modules\Cms\Http\Controllers\CmsController::class, 'sanphamkhac'])->name('cms.products.sanphamkhac');


// Backward compatibility redirects: c/* -> shop/*
Route::redirect('c/page/{page}', 'shop/page/{page}', 301);
Route::redirect('c/blogs', 'shop/blogs', 301);
Route::redirect('c/blog/{slug}-{id}', 'shop/blog/{slug}-{id}', 301);
Route::redirect('c/contact-us', 'shop/contact-us', 301);
Route::post('c/submit-contact-form', [Modules\Cms\Http\Controllers\CmsController::class, 'postContactForm']);
Route::redirect('c/products/bao-bi-cuon', 'shop/products/bao-bi-cuon', 301);


Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu', 'superadmin')->prefix('cms')->group(function () {
    Route::get('install', [\Modules\Cms\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [\Modules\Cms\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [\Modules\Cms\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\Cms\Http\Controllers\InstallController::class, 'update']);

    Route::resource('cms-page', \Modules\Cms\Http\Controllers\CmsPageController::class)->except(['show']);
    Route::resource('site-details', \Modules\Cms\Http\Controllers\SettingsController::class);
});
