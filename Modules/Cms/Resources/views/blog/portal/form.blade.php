@extends('cms::frontend.layouts.app')
@section('title', !empty($post->id) ? __('cms::lang.edit_blog_post') : __('cms::lang.add_blog_post'))

@section('content')
    <section class="page-title-center-alignment cover-background top-space-padding" style="background-image: url({{ asset('modules/cms/assets/images/demo-decor-store-title-bg.jpg') }})">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center position-relative page-title-extra-large">
                    <h1 class="alt-font d-inline-block fw-700 ls-minus-05px text-base-color mb-10px mt-3 md-mt-50px">
                        {{ !empty($post->id) ? __('cms::lang.edit_blog_post') : __('cms::lang.add_blog_post') }}
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <form action="{{ !empty($post->id) ? route('cms.blog.portal.posts.update', ['locale' => $locale, 'post' => $post->id]) : route('cms.blog.portal.posts.store', ['locale' => $locale]) }}" method="post" enctype="multipart/form-data" class="bg-white p-4 border-radius-6px">
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
                </div>
            </div>
        </div>
    </section>
@endsection
