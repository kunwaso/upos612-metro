@extends('layouts.app')
@section('title', __('cms::lang.edit_blog_post'))

@section('content')
@include('cms::layouts.nav')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('cms::lang.edit_blog_post')
    </h1>
</section>

<section class="content">
    @component('components.widget')
        @includeIf('cms::settings.partials.blog_post_form', [
            'form_action' => route('cms.site-details.blog-posts.update', $blogPost->id),
            'method' => 'put',
            'post' => $blogPost,
            'meta' => $meta,
        ])
    @endcomponent
</section>
@endsection
