@extends('cms::frontend.layouts.app')
@section('title', $blog->title)
@section('meta')
    <meta name="description" content="{{ $blog->meta_description }}">
@endsection
@section('content')
        <!-- start page title -->
        <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center position-relative page-title-extra-large">
                        <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">{{ $blog->title }}</h1>
                    </div>
                    <div class="col-12 breadcrumb breadcrumb-style-01 d-flex justify-content-center">
                        <ul>
                            <li><a href="{{ route('cms.home') }}">Home</a></li>
                            <li><a href="{{ route('cms.blogs.index') }}">{{ __('cms::lang.blog') }}</a></li>
                            <li>{{ $blog->title }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
        <section class="top-space-margin half-section pb-0">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <img class="border-radius-6px w-100" src="{{ $blog->feature_image_url ?? asset('modules/cms/img/default.png') }}" alt="">
                    </div>
                </div>
            </div>
        </section>
        <section class="pb-0 pt-40px">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <span class="fs-18 mb-20px d-inline-block">
                            <span class="fw-600 text-dark-gray">{{ $blog->createdBy->user_full_name ?? '' }}</span>
                            <span class="text-muted ms-2">{{ \Carbon\Carbon::parse($blog->created_at)->diffForHumans() }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 last-paragraph-no-margin">
                        {!! $blog->content !!}
                    </div>
                </div>
            </div>
        </section>
@endsection
