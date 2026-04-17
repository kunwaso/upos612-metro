@extends('layouts.app')
@section('title', !empty($post->id) ? __('cms::lang.edit_blog_post') : __('cms::lang.add_blog_post'))

@section('content')
    @include('cms::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            {{ !empty($post->id) ? __('cms::lang.edit_blog_post') : __('cms::lang.add_blog_post') }}
        </h1>
    </section>

    <section class="content">
        @component('components.widget')
            <form action="{{ !empty($post->id) ? route('cms.blog.admin.posts.update', $post->id) : route('cms.blog.admin.posts.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                @if(!empty($post->id))
                    @method('PUT')
                @endif

                @include('cms::blog.partials.post_editor_fields', [
                    'post' => $post,
                    'supportedLocales' => $supportedLocales,
                    'variantMap' => $variantMap,
                    'sectionMapByLocale' => $sectionMapByLocale,
                ])
            </form>
        @endcomponent
    </section>
@endsection
