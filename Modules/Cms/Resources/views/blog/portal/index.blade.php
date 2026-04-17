@extends('cms::frontend.layouts.app')
@section('title', __('cms::lang.my_blog_posts'))

@section('content')
    <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center position-relative page-title-extra-large">
                    <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">
                        @lang('cms::lang.my_blog_posts')
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="row mb-20px align-items-end">
                <div class="col-lg-9 col-md-8">
                    <form method="get" action="{{ route('cms.blog.portal.posts.index', ['locale' => $locale]) }}" class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">@lang('lang_v1.search')</label>
                            <input
                                type="text"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                class="form-control"
                                placeholder="@lang('cms::lang.search_posts_placeholder')"
                            >
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">@lang('cms::lang.status')</label>
                            <select name="status" class="form-control">
                                @php($selectedStatus = $filters['status'] ?? '')
                                <option value="">@lang('cms::lang.all_statuses')</option>
                                <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $selectedStatus === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived" {{ $selectedStatus === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">@lang('cms::lang.locale')</label>
                            <select name="variant_locale" class="form-control">
                                @php($selectedVariantLocale = $filters['variant_locale'] ?? '')
                                <option value="">@lang('cms::lang.all_locales')</option>
                                @foreach($supportedLocales as $supportedLocale)
                                    <option value="{{ $supportedLocale }}" {{ $selectedVariantLocale === $supportedLocale ? 'selected' : '' }}>
                                        {{ strtoupper($supportedLocale) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-default w-100">@lang('cms::lang.filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-3 col-md-4 text-md-end mt-3 mt-md-0">
                    @can('cms.blog.posts.create')
                        <a href="{{ route('cms.blog.portal.posts.create', ['locale' => $locale]) }}" class="btn btn-dark-gray btn-small btn-round-edge">
                            @lang('cms::lang.add_blog_post')
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped bg-white">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>EN</th>
                                    <th>VI</th>
                                    <th>@lang('cms::lang.status')</th>
                                    <th>@lang('lang_v1.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($posts as $post)
                                    @php
                                        $enVariant = $post->variants->firstWhere('locale', 'en');
                                        $viVariant = $post->variants->firstWhere('locale', 'vi');
                                    @endphp
                                    <tr>
                                        <td>{{ $post->id }}</td>
                                        <td>{{ $enVariant?->title ?? '-' }}</td>
                                        <td>{{ $viVariant?->title ?? '-' }}</td>
                                        <td>
                                            @if($post->status === 'published')
                                                <span class="badge bg-success">Published</span>
                                            @elseif($post->status === 'archived')
                                                <span class="badge bg-secondary">Archived</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('cms.blog.portal.posts.edit', ['locale' => $locale, 'post' => $post->id]) }}" class="btn btn-xs btn-info">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="{{ route('cms.blog.portal.posts.preview', ['locale' => $locale, 'post' => $post->id]) }}" class="btn btn-xs btn-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @can('cms.blog.posts.publish')
                                                <form action="{{ route('cms.blog.portal.posts.publish-toggle', ['locale' => $locale, 'post' => $post->id]) }}" method="post" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-warning"><i class="fa fa-power-off"></i></button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">@lang('cms::lang.not_found_please_add_one')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        {{ $posts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
