@extends('cms::frontend.layouts.app')
@php
    $mediaSection = $sections['media'] ?? [];
    $leadText = $sections['lead']['text'] ?? '';
    $quotePrimary = $sections['quote_primary']['text'] ?? '';
    $storySection = $sections['story'] ?? [];
    $quoteSecondary = $sections['quote_secondary']['text'] ?? '';
    $closingSection = $sections['closing'] ?? [];
    $tags = $sections['tags']['items'] ?? [];
    $author = $post->createdBy;
    $heroImage = !empty($mediaSection['hero_image']) ? asset('uploads/cms/' . rawurlencode($mediaSection['hero_image'])) : ($post->feature_image_url ?? asset('modules/cms/img/default.png'));
    $bodyImageOne = !empty($mediaSection['body_image_one']) ? asset('uploads/cms/' . rawurlencode($mediaSection['body_image_one'])) : asset('modules/cms/assets/images/demo-decor-store-blog-single-classic-02.jpg');
    $splitImage = !empty($mediaSection['split_image']) ? asset('uploads/cms/' . rawurlencode($mediaSection['split_image'])) : asset('modules/cms/assets/images/demo-decor-store-blog-single-classic-03.jpg');
    $bodyImageTwo = !empty($mediaSection['body_image_two']) ? asset('uploads/cms/' . rawurlencode($mediaSection['body_image_two'])) : asset('modules/cms/assets/images/demo-decor-store-blog-single-classic-04.jpg');
    $bannerImage = !empty($mediaSection['body_image_one']) ? asset('uploads/cms/' . rawurlencode($mediaSection['body_image_one'])) : asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg');
    $metaTitle = $variant->meta_title ?: $variant->title;
    $metaDescription = $variant->meta_description ?: $variant->excerpt;
    $currentIndexUrl = $locale === $defaultLocale ? route('cms.blogs.index') : route('cms.blogs.index.locale', ['locale' => $locale]);
    $currentShowUrl = $locale === $defaultLocale
        ? route('cms.blog.show', ['slug' => $variant->slug, 'id' => $post->id])
        : route('cms.blog.show.locale', ['locale' => $locale, 'slug' => $variant->slug, 'id' => $post->id]);
    $defaultShowUrl = $alternates[$defaultLocale] ?? route('cms.blog.show', ['slug' => $variant->slug, 'id' => $post->id]);
    $likeRouteName = $locale === $defaultLocale ? 'cms.blog.like' : 'cms.blog.like.locale';
    $commentRouteName = $locale === $defaultLocale ? 'cms.blog.comments.store' : 'cms.blog.comments.store.locale';
    $postRouteParams = $locale === $defaultLocale
        ? ['slug' => $variant->slug, 'id' => $post->id]
        : ['locale' => $locale, 'slug' => $variant->slug, 'id' => $post->id];
@endphp
@section('title', $metaTitle)
@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $variant->meta_keywords }}">
    <link rel="canonical" href="{{ $currentShowUrl }}">
    @foreach($alternates as $altLocale => $altUrl)
        <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $defaultShowUrl }}">
@endsection
@section('content')
    <!-- start page title -->
    <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ $bannerImage }})">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center position-relative page-title-extra-large">
                    <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">{{ $variant->title }}</h1>
                    @if(!empty($variant->hero_text))
                        <p class="text-dark-gray">{{ $variant->hero_text }}</p>
                    @endif
                </div>
                <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                    <ul>
                        <li><a href="{{ route('cms.home') }}">Home</a></li>
                        <li><a href="{{ $currentIndexUrl }}">{{ __('cms::lang.blog') }}</a></li>
                        <li>{{ $variant->title }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- end page title -->

    <!-- start section -->
    <section class="top-space-margin half-section pb-0">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <img class="border-radius-6px" src="{{ $heroImage }}" alt="">
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    <!-- start section -->
    <section class="overlap-section text-center p-0 sm-pt-50px">
        <img class="rounded-circle box-shadow-medium-bottom w-150px border border-9 border-color-white" src="{{ $author?->image_url ?? asset('modules/cms/assets/images/avtar-07.jpg') }}" alt="">
    </section>
    <!-- end section -->

    <!-- start section -->
    <section class="pb-0 pt-40px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <span class="fs-18 mb-20px d-inline-block">
                        @if($settings->show_author)
                            {{ __('cms::lang.writer_by') }}
                            <a href="{{ $currentIndexUrl }}" class="fw-600 d-inline-block align-middle text-dark-gray">
                                {{ $author?->user_full_name ?? __('cms::lang.unknown_author') }}
                            </a>
                        @endif
                        @if($settings->show_publish_date && !empty($variant->published_at))
                            <span class="text-muted ms-2">{{ $variant->published_at->format('d M Y') }}</span>
                        @endif
                    </span>
                    <h2 class="alt-font fw-700 text-dark-gray mx-auto w-80 xl-w-100 mb-5">{{ $variant->title }}</h2>
                    <i class="feather icon-feather-more-horizontal- icon-double-large text-light-gray d-inline-block mb-5 d-inline-block"></i>
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    <!-- start section -->
    <section class="py-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="dropcap-style-02 last-paragraph-no-margin">
                        <p>
                            <span class="alt-font first-letter first-letter-block first-letter-round bg-dark-gray text-white">
                                {{ strtoupper(substr(strip_tags($leadText ?: $variant->excerpt ?: $variant->title), 0, 1)) }}
                            </span>
                            {{ $leadText ?: strip_tags($variant->excerpt) }}
                        </p>
                        <img src="{{ $bodyImageOne }}" class="mb-30px mt-6" alt="">
                        <p class="pb-25px text-center alt-font text-dark-gray ls-1px fw-600 text-uppercase">
                            {{ $quotePrimary ?: __('cms::lang.default_quote_primary') }}
                        </p>
                        <div class="h-3px w-100 bg-dark-gray"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    <!-- start section -->
    <section>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row">
                        <div class="col-xl-5 col-lg-6 md-mb-50px">
                            <h4 class="alt-font text-dark-gray fw-700">{{ $storySection['title'] ?? '' }}</h4>
                            <p>{{ $storySection['body'] ?? '' }}</p>
                            @if(!empty($storySection['cta_label']) && !empty($storySection['cta_url']))
                                <a href="{{ $storySection['cta_url'] }}" class="btn btn-dark-gray btn-medium mt-15px">{{ $storySection['cta_label'] }}</a>
                            @endif
                        </div>
                        <div class="col-lg-6 offset-xl-1"><img src="{{ $splitImage }}" alt=""></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    <!-- start section -->
    <section class="pt-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row">
                        <div class="col-12 last-paragraph-no-margin">
                            <div class="h-3px w-100 bg-dark-gray"></div>
                            <p class="pt-25px text-center alt-font text-dark-gray ls-1px fw-600 text-uppercase">
                                {{ $quoteSecondary ?: __('cms::lang.default_quote_secondary') }}
                            </p>
                            <img src="{{ $bodyImageTwo }}" class="mb-8 mt-30px" alt="">
                        </div>
                        <div class="col-md-6">
                            <h4 class="alt-font text-dark-gray fw-700">{{ $closingSection['title'] ?? '' }}</h4>
                        </div>
                        <div class="col-md-6 last-paragraph-no-margin">
                            <p>{{ $closingSection['body'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    <!-- start section -->
    <section class="half-section pt-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row mb-30px">
                        <div class="tag-cloud col-md-9 text-center text-md-start sm-mb-15px">
                            @foreach($tags as $tag)
                                <a href="{{ $currentIndexUrl }}">{{ $tag }}</a>
                            @endforeach
                        </div>
                        @if($settings->show_likes)
                            <div class="tag-cloud col-md-3 text-uppercase text-center text-md-end">
                                <form action="{{ route($likeRouteName, $postRouteParams) }}" method="post" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="likes-count fw-500 mx-0 btn btn-link p-0">
                                        <i class="fa-regular fa-heart text-red me-10px"></i>
                                        <span class="text-dark-gray text-dark-gray-hover">{{ $likesCount }} {{ __('cms::lang.likes_label') }}</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if($settings->show_author && $post->show_author_card)
                        <div class="row">
                            <div class="col-12 mb-6">
                                <div class="d-block d-md-flex w-100 box-shadow-extra-large align-items-center border-radius-4px p-7 sm-p-35px">
                                    <div class="w-140px text-center me-50px sm-mx-auto">
                                        <img src="{{ $author?->image_url ?? asset('modules/cms/assets/images/avtar-07.jpg') }}" class="rounded-circle w-120px" alt="">
                                        <a href="{{ $currentIndexUrl }}" class="text-dark-gray fw-600 mt-20px d-inline-block lh-20">{{ $author?->user_full_name ?? __('cms::lang.unknown_author') }}</a>
                                        <span class="fs-15 lh-20 d-block sm-mb-15px">{{ __('cms::lang.author_role_default') }}</span>
                                    </div>
                                    <div class="w-75 sm-w-100 text-center text-md-start last-paragraph-no-margin">
                                        <p>{{ $variant->excerpt ?: strip_tags($variant->content_html) }}</p>
                                        <a href="{{ $currentIndexUrl }}" class="btn btn-link btn-large text-dark-gray mt-15px fw-600">{{ __('cms::lang.all_author_posts') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($settings->show_social_share && $post->show_social_share)
                        <div class="row justify-content-center">
                            <div class="col-12 text-center elements-social social-icon-style-04">
                                @php
                                    $shareUrl = urlencode(request()->fullUrl());
                                    $shareTitle = urlencode($variant->title);
                                @endphp
                                <ul class="large-icon dark">
                                    <li><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank"><i class="fa-brands fa-facebook-f"></i><span></span></a></li>
                                    <li><a class="twitter" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank"><i class="fa-brands fa-twitter"></i><span></span></a></li>
                                    <li><a class="linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank"><i class="fa-brands fa-linkedin-in"></i><span></span></a></li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!-- end section -->

    @if($settings->show_related_posts && $post->show_related_posts && $relatedVariants->count() > 0)
        <!-- start section -->
        <section class="bg-very-light-gray">
            <div class="container">
                <div class="row justify-content-center mb-1">
                    <div class="col-lg-7 text-center">
                        <span class="text-uppercase fs-14 ls-2px fw-600 d-inline-block">{{ __('cms::lang.you_may_also_like') }}</span>
                        <h4 class="alt-font text-dark-gray fw-700">{{ __('cms::lang.related_posts') }}</h4>
                    </div>
                </div>
                <div class="row g-0">
                    <div class="col-12">
                        <ul class="blog-classic blog-wrapper grid grid-4col xl-grid-4col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-extra-large">
                            <li class="grid-sizer"></li>
                            @foreach($relatedVariants as $relatedVariant)
                                @php($relatedPost = $relatedVariant->post)
                                <li class="grid-item">
                                    <div class="card bg-transparent border-0 h-100">
                                        <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                            <a href="{{ $locale === $defaultLocale ? route('cms.blog.show', ['slug' => $relatedVariant->slug, 'id' => $relatedPost->id]) : route('cms.blog.show.locale', ['locale' => $locale, 'slug' => $relatedVariant->slug, 'id' => $relatedPost->id]) }}">
                                                <img src="{{ $relatedPost?->feature_image_url ?? asset('modules/cms/img/default.png') }}" alt="" />
                                            </a>
                                        </div>
                                        <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                            <span class="fs-13 text-uppercase d-block mb-5px fw-500">
                                                <a href="{{ $currentIndexUrl }}" class="text-dark-gray fw-700 categories-text">{{ __('cms::lang.blog') }}</a>
                                                <a href="#" class="blog-date">{{ optional($relatedVariant->published_at)->format('d M Y') }}</a>
                                            </span>
                                            <a href="{{ $locale === $defaultLocale ? route('cms.blog.show', ['slug' => $relatedVariant->slug, 'id' => $relatedPost->id]) : route('cms.blog.show.locale', ['locale' => $locale, 'slug' => $relatedVariant->slug, 'id' => $relatedPost->id]) }}" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 sm-w-100">
                                                {{ $relatedVariant->title }}
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
    @endif

    @if($settings->show_comments && $post->allow_comments)
        <!-- start section -->
        <section>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9 text-center mb-2">
                        <h6 class="alt-font text-dark-gray fw-700">{{ $comments->count() }} {{ __('cms::lang.comments_label') }}</h6>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <ul class="blog-comment">
                            @forelse($comments as $comment)
                                <li>
                                    <div class="d-block d-md-flex w-100 align-items-center align-items-md-start ">
                                        <div class="w-90px sm-w-65px sm-mb-10px">
                                            <img src="{{ $comment->user?->image_url ?? asset('modules/cms/assets/images/avtar-18.jpg') }}" class="rounded-circle" alt="">
                                        </div>
                                        <div class="w-100 ps-30px last-paragraph-no-margin sm-ps-0">
                                            <a href="#" class="text-dark-gray fw-600">{{ $comment->user?->user_full_name ?? __('cms::lang.unknown_author') }}</a>
                                            <div class="fs-14 lh-24 mb-10px">{{ $comment->created_at->format('d M Y, h:i A') }}</div>
                                            <p class="w-85 sm-w-100">{{ $comment->comment }}</p>
                                        </div>
                                    </div>
                                    @if($comment->children->count() > 0)
                                        <ul class="child-comment">
                                            @foreach($comment->children as $childComment)
                                                <li>
                                                    <div class="d-block d-md-flex w-100 align-items-center align-items-md-start ">
                                                        <div class="w-90px sm-w-65px sm-mb-10px">
                                                            <img src="{{ $childComment->user?->image_url ?? asset('modules/cms/assets/images/avtar-19.jpg') }}" class="rounded-circle" alt="">
                                                        </div>
                                                        <div class="w-100 ps-30px last-paragraph-no-margin sm-ps-0">
                                                            <a href="#" class="text-dark-gray fw-600">{{ $childComment->user?->user_full_name ?? __('cms::lang.unknown_author') }}</a>
                                                            <div class="fs-14 lh-24 mb-10px">{{ $childComment->created_at->format('d M Y, h:i A') }}</div>
                                                            <p class="w-85 sm-w-100">{{ $childComment->comment }}</p>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @empty
                                <li>{{ __('cms::lang.no_comments_yet') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->

        <!-- start section -->
        <section id="comments" class="pt-0">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9 mb-3 sm-mb-6">
                        <h6 class="alt-font text-dark-gray fw-700 mb-5px">{{ __('cms::lang.write_comment') }}</h6>
                        @if(auth()->check())
                            <div class="mb-5px">{{ __('cms::lang.comment_submission_help') }}</div>
                        @else
                            <div class="mb-5px">
                                <a href="{{ route('login') }}">{{ __('cms::lang.login_to_comment') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                @if(auth()->check())
                    <div class="row justify-content-center">
                        <div class="col-lg-9">
                            <form action="{{ route($commentRouteName, $postRouteParams) }}" method="post" class="row contact-form-style-02">
                                @csrf
                                <div class="col-md-12 mb-30px">
                                    <textarea class="border-radius-4px form-control" cols="40" rows="4" name="comment" placeholder="{{ __('cms::lang.comment_placeholder') }}"></textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-dark-gray btn-small btn-round-edge submit" type="submit">{{ __('cms::lang.post_comment') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </section>
        <!-- end section -->
    @endif
@endsection
