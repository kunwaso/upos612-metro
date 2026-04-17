@extends('layouts.app')
@section('title', __('cms::lang.blog_comments'))

@section('content')
    @include('cms::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('cms::lang.blog_comments')
            <a href="{{ route('cms.blog.admin.posts.index') }}" class="btn btn-default pull-right">@lang('cms::lang.blog_posts')</a>
        </h1>
    </section>

    <section class="content">
        @component('components.widget')
            <div class="btn-group" style="margin-bottom: 15px;">
                <a href="{{ route('cms.blog.admin.comments.index', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary' : 'btn-default' }}">Pending</a>
                <a href="{{ route('cms.blog.admin.comments.index', ['status' => 'approved']) }}" class="btn {{ $status === 'approved' ? 'btn-primary' : 'btn-default' }}">Approved</a>
                <a href="{{ route('cms.blog.admin.comments.index', ['status' => 'rejected']) }}" class="btn {{ $status === 'rejected' ? 'btn-primary' : 'btn-default' }}">Rejected</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>@lang('cms::lang.blog')</th>
                            <th>@lang('lang_v1.user')</th>
                            <th>@lang('cms::lang.comment')</th>
                            <th>@lang('cms::lang.status')</th>
                            <th>@lang('lang_v1.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                            @php($postTitle = $comment->post?->variants?->firstWhere('locale', config('cms.blog_default_locale', 'en'))?->title ?? ('#' . $comment->cms_blog_post_id))
                            <tr>
                                <td>{{ $comment->id }}</td>
                                <td>{{ $postTitle }}</td>
                                <td>{{ $comment->user?->user_full_name ?? ($comment->user?->username ?? '-') }}</td>
                                <td>{{ $comment->comment }}</td>
                                <td>
                                    @if($comment->status === 'approved')
                                        <span class="label label-success">Approved</span>
                                    @elseif($comment->status === 'rejected')
                                        <span class="label label-danger">Rejected</span>
                                    @else
                                        <span class="label label-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($comment->status === 'pending')
                                        <form action="{{ route('cms.blog.admin.comments.moderate', $comment->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-xs btn-success">@lang('cms::lang.approve')</button>
                                        </form>
                                        <form action="{{ route('cms.blog.admin.comments.moderate', $comment->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-xs btn-danger">@lang('cms::lang.reject')</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">@lang('cms::lang.not_found_please_add_one')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                {{ $comments->links() }}
            </div>
        @endcomponent
    </section>
@endsection
