@extends('layouts.app')
@section('title', __('cms::lang.blog_posts'))

@section('content')
    @include('cms::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('cms::lang.blog_posts')
            @can('cms.blog.posts.create')
                <a href="{{ route('cms.blog.admin.posts.create') }}" class="btn btn-primary pull-right">
                    <i class="fa fa-plus"></i> @lang('cms::lang.add_blog_post')
                </a>
            @endcan
            @can('cms.blog.posts.publish')
                <a href="{{ route('cms.blog.admin.comments.index') }}" class="btn btn-default pull-right" style="margin-right: 8px;">
                    @lang('cms::lang.blog_comments')
                </a>
            @endcan
            @can('cms.blog.settings.view')
                <a href="{{ route('cms.blog.admin.settings') }}" class="btn btn-default pull-right" style="margin-right: 8px;">
                    @lang('cms::lang.blog_settings')
                </a>
            @endcan
        </h1>
    </section>

    <section class="content">
        @component('components.widget')
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ strtoupper(config('cms.blog_default_locale', 'en')) }}</th>
                            <th>EN</th>
                            <th>VI</th>
                            <th>@lang('cms::lang.status')</th>
                            <th>@lang('cms::lang.priority')</th>
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
                                <td>{{ $post->variants->firstWhere('locale', config('cms.blog_default_locale', 'en'))?->title ?? '-' }}</td>
                                <td>{{ $enVariant?->title ?? '-' }}</td>
                                <td>{{ $viVariant?->title ?? '-' }}</td>
                                <td>
                                    @if($post->status === 'published')
                                        <span class="label label-success">Published</span>
                                    @elseif($post->status === 'archived')
                                        <span class="label label-default">Archived</span>
                                    @else
                                        <span class="label label-warning">Draft</span>
                                    @endif
                                </td>
                                <td>{{ $post->priority }}</td>
                                <td>
                                    @can('cms.blog.posts.update')
                                        <a href="{{ route('cms.blog.admin.posts.edit', $post->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('cms.blog.posts.publish')
                                        <form action="{{ route('cms.blog.admin.posts.toggle-publish', $post->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-warning">
                                                <i class="fa fa-power-off"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('cms.blog.posts.delete')
                                        <form action="{{ route('cms.blog.admin.posts.destroy', $post->id) }}" method="post" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">@lang('cms::lang.not_found_please_add_one')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="text-center">
                {{ $posts->links() }}
            </div>
        @endcomponent
    </section>
@endsection
