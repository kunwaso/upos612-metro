@extends('layouts.app')
@section('title', __('cms::lang.blog_settings'))

@section('content')
    @include('cms::layouts.nav')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('cms::lang.blog_settings')
            <a href="{{ route('cms.blog.admin.posts.index') }}" class="btn btn-default pull-right">@lang('cms::lang.blog_posts')</a>
        </h1>
    </section>

    <section class="content">
        <form action="{{ route('cms.blog.admin.settings.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    @component('components.widget')
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('cms::lang.posts_per_page')</label>
                                    <input type="number" name="posts_per_page" min="1" max="100" class="form-control" value="{{ old('posts_per_page', $settings->posts_per_page) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>@lang('cms::lang.default_locale')</label>
                                    <select name="default_locale" class="form-control">
                                        @foreach($supportedLocales as $localeCode)
                                            <option value="{{ $localeCode }}" {{ old('default_locale', $settings->default_locale) === $localeCode ? 'selected' : '' }}>
                                                {{ strtoupper($localeCode) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('cms::lang.banner_image')</label>
                                    <input type="file" name="listing_banner_image" class="form-control" accept="image/*">
                                    @if(!empty($settings->listing_banner_image))
                                        <p class="help-block">
                                            <a href="{{ asset('uploads/cms/' . rawurlencode($settings->listing_banner_image)) }}" target="_blank">
                                                {{ $settings->listing_banner_image }}
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_author" value="1" {{ old('show_author', $settings->show_author) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_author')
                                </label>
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_publish_date" value="1" {{ old('show_publish_date', $settings->show_publish_date) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_publish_date')
                                </label>
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_related_posts" value="1" {{ old('show_related_posts', $settings->show_related_posts) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_related_posts')
                                </label>
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_comments" value="1" {{ old('show_comments', $settings->show_comments) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_comments')
                                </label>
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_likes" value="1" {{ old('show_likes', $settings->show_likes) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_likes')
                                </label>
                                <label class="checkbox-inline" style="margin-right: 12px;">
                                    <input type="checkbox" name="show_social_share" value="1" {{ old('show_social_share', $settings->show_social_share) ? 'checked' : '' }}>
                                    @lang('cms::lang.show_social_share')
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="require_comment_approval" value="1" {{ old('require_comment_approval', $settings->require_comment_approval) ? 'checked' : '' }}>
                                    @lang('cms::lang.require_comment_approval')
                                </label>
                            </div>
                        </div>

                        <hr>

                        <ul class="nav nav-tabs" role="tablist">
                            @foreach($supportedLocales as $index => $localeCode)
                                <li role="presentation" class="{{ $index === 0 ? 'active' : '' }}">
                                    <a href="#settings_locale_{{ $localeCode }}" aria-controls="settings_locale_{{ $localeCode }}" role="tab" data-toggle="tab">
                                        {{ strtoupper($localeCode) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content" style="padding-top: 15px;">
                            @foreach($supportedLocales as $index => $localeCode)
                                <div role="tabpanel" class="tab-pane {{ $index === 0 ? 'active' : '' }}" id="settings_locale_{{ $localeCode }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('cms::lang.title')</label>
                                                <input type="text" name="listing_title[{{ $localeCode }}]" class="form-control" value="{{ old("listing_title.$localeCode", $settings->{'listing_title_'.$localeCode}) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('cms::lang.meta_title')</label>
                                                <input type="text" name="listing_meta_title[{{ $localeCode }}]" class="form-control" value="{{ old("listing_meta_title.$localeCode", $settings->{'listing_meta_title_'.$localeCode}) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>@lang('cms::lang.hero_text')</label>
                                                <textarea name="listing_hero_text[{{ $localeCode }}]" class="form-control" rows="2">{{ old("listing_hero_text.$localeCode", $settings->{'listing_hero_text_'.$localeCode}) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('cms::lang.meta_description')</label>
                                                <textarea name="listing_meta_description[{{ $localeCode }}]" class="form-control" rows="2">{{ old("listing_meta_description.$localeCode", $settings->{'listing_meta_description_'.$localeCode}) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('cms::lang.meta_keywords')</label>
                                                <textarea name="listing_meta_keywords[{{ $localeCode }}]" class="form-control" rows="2">{{ old("listing_meta_keywords.$localeCode", $settings->{'listing_meta_keywords_'.$localeCode}) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endcomponent
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">@lang('messages.submit')</button>
                </div>
            </div>
        </form>
    </section>
@endsection
