@extends('projectx::site_manager.layouts.public')

@section('title', 'Blog - ' . $siteName)

@section('content')
    <div class="container-xxl mt-20 mb-20">
        <div class="card">
            <div class="card-body p-lg-20">
                <div class="mb-17">
                    <h3 class="text-gray-900 mb-7">{{ $blogHeading }}</h3>
                    <div class="separator separator-dashed mb-9"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="h-100 d-flex flex-column justify-content-between pe-lg-6 mb-lg-0 mb-10">
                                <div class="mb-3">
                                    <iframe class="embed-responsive-item card-rounded h-275px w-100" src="{{ $blogFeature['videoUrl'] }}" allowfullscreen="allowfullscreen"></iframe>
                                </div>
                                <div class="mb-5">
                                    <a href="{{ route('public.blog.index') }}" class="fs-2 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">{{ $blogFeature['title'] }}</a>
                                    <div class="fw-semibold fs-5 text-gray-600 text-gray-900 mt-4">{{ $blogFeature['excerpt'] }}</div>
                                </div>
                                <div class="d-flex flex-stack flex-wrap">
                                    <div class="d-flex align-items-center pe-2">
                                        <div class="symbol symbol-35px symbol-circle me-3">
                                            <img alt="" src="{{ $blogFeature['avatar'] }}" />
                                        </div>
                                        <div class="fs-5 fw-bold">
                                            <span class="text-gray-700">{{ $blogFeature['author'] }}</span>
                                            <span class="text-muted">on {{ $blogFeature['publishedAt'] }}</span>
                                        </div>
                                    </div>
                                    <span class="badge badge-light-primary fw-bold my-2">{{ $blogFeature['category'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 justify-content-between d-flex flex-column">
                            @foreach ($posts as $post)
                                <div class="ps-lg-6 {{ $loop->last ? '' : 'mb-16' }}">
                                    <div class="mb-6">
                                        <a href="{{ route('public.blog.index') }}" class="fw-bold text-gray-900 mb-4 fs-2 lh-base text-hover-primary">{{ $post['title'] }}</a>
                                        <div class="fw-semibold fs-5 mt-4 text-gray-600 text-gray-900">{{ $post['excerpt'] }}</div>
                                    </div>
                                    <div class="d-flex flex-stack flex-wrap">
                                        <div class="d-flex align-items-center pe-2">
                                            <div class="symbol symbol-35px symbol-circle me-3">
                                                <img src="{{ $post['avatar'] }}" class="" alt="" />
                                            </div>
                                            <div class="fs-5 fw-bold">
                                                <span class="text-gray-700">{{ $post['author'] }}</span>
                                                <span class="text-muted">on {{ $post['publishedAt'] }}</span>
                                            </div>
                                        </div>
                                        <span class="badge badge-light-info fw-bold my-2">{{ $post['category'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
