<div class="pos-tab-content">
    <h4 class="mb-3">@lang('cms::lang.blog_settings')</h4>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_title', __('cms::lang.title') . ':') !!}
                {!! Form::text('blog_settings[listing_title]', $blogSettings['listing_title'] ?? '', ['class' => 'form-control', 'id' => 'blog_settings_listing_title']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('blog_settings_posts_per_page', __('cms::lang.posts_per_page') . ':') !!}
                {!! Form::number('blog_settings[posts_per_page]', $blogSettings['posts_per_page'] ?? 12, ['class' => 'form-control', 'min' => 1, 'id' => 'blog_settings_posts_per_page']) !!}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_hero_text', __('cms::lang.hero_text') . ':') !!}
                {!! Form::textarea('blog_settings[listing_hero_text]', $blogSettings['listing_hero_text'] ?? '', ['class' => 'form-control', 'rows' => 3, 'id' => 'blog_settings_listing_hero_text']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_banner_image', __('cms::lang.banner_image') . ':') !!}
                {!! Form::file('blog_settings[listing_banner_image]', ['accept' => 'image/*', 'id' => 'blog_settings_listing_banner_image']) !!}
                @if(!empty($blogSettings['listing_banner_image']))
                    <p class="help-block mt-2">
                        <a href="{{ asset('uploads/cms/' . rawurlencode($blogSettings['listing_banner_image'])) }}" target="_blank">
                            {{ $blogSettings['listing_banner_image'] }}
                        </a>
                    </p>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="checkbox mt-4">
                <label>
                    {!! Form::checkbox('blog_settings[show_author]', 1, !empty($blogSettings['show_author'])) !!}
                    @lang('cms::lang.show_author')
                </label>
            </div>
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('blog_settings[show_publish_date]', 1, !empty($blogSettings['show_publish_date'])) !!}
                    @lang('cms::lang.show_publish_date')
                </label>
            </div>
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('blog_settings[show_related_posts]', 1, !empty($blogSettings['show_related_posts'])) !!}
                    @lang('cms::lang.show_related_posts')
                </label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_meta_title', __('cms::lang.meta_title') . ':') !!}
                {!! Form::text('blog_settings[listing_meta_title]', $blogSettings['listing_meta_title'] ?? '', ['class' => 'form-control', 'id' => 'blog_settings_listing_meta_title']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_meta_description', __('cms::lang.meta_description') . ':') !!}
                {!! Form::text('blog_settings[listing_meta_description]', $blogSettings['listing_meta_description'] ?? '', ['class' => 'form-control', 'id' => 'blog_settings_listing_meta_description']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('blog_settings_listing_meta_keywords', __('cms::lang.meta_keywords') . ':') !!}
                {!! Form::text('blog_settings[listing_meta_keywords]', $blogSettings['listing_meta_keywords'] ?? '', ['class' => 'form-control', 'id' => 'blog_settings_listing_meta_keywords']) !!}
            </div>
        </div>
    </div>
</div>
