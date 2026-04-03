@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.blog'))
@section('content')
        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">{{ __('cms::lang.blog') }}</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li>
                            <li>{{ __('cms::lang.blog') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
        <!-- start section -->
        <section>
            <div class="container">
                <div class="row">
                    <div class="col-12 px-0">
                        <ul class="blog-classic blog-wrapper grid-loading grid grid-4col xl-grid-4col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-double-extra-large" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>
                            @forelse($blogs as $blog)
                            <li class="grid-item">
                                <div class="card bg-transparent border-0 h-100">
                                    <div class="blog-image position-relative overflow-hidden border-radius-4px">
                                        <a href="{{ route('cms.blog.show', ['slug' => $blog->slug, 'id' => $blog->id]) }}"><img src="{{ $blog->feature_image_url ?? asset('modules/cms/img/default.png') }}" alt="" /></a>
                                    </div>
                                    <div class="card-body px-0 pt-30px pb-30px xs-pb-15px">
                                        <span class="fs-13 text-uppercase d-block mb-5px fw-500"><a href="{{ route('cms.blogs.index') }}" class="text-dark-gray fw-700 categories-text">{{ __('cms::lang.blog') }}</a><a href="#" class="blog-date">{{ \Carbon\Carbon::parse($blog->created_at)->format('d M Y') }}</a></span>
                                        <a href="{{ route('cms.blog.show', ['slug' => $blog->slug, 'id' => $blog->id]) }}" class="card-title alt-font fw-600 fs-17 lh-30 text-dark-gray d-inline-block w-95 xs-w-100">{{ $blog->title }}</a>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="grid-item w-100">
                                <p class="text-center w-100">No blogs yet.</p>
                            </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
@endsection
