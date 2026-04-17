@php
    $isEdit = !empty($post?->id);
@endphp

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="priority">@lang('cms::lang.priority')</label>
            <input type="number" min="0" name="priority" id="priority" class="form-control" value="{{ old('priority', $post->priority ?? 0) }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="related_posts_limit">@lang('cms::lang.related_posts_limit')</label>
            <input type="number" min="1" max="12" name="related_posts_limit" id="related_posts_limit" class="form-control" value="{{ old('related_posts_limit', $post->related_posts_limit ?? 4) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="feature_image">@lang('cms::lang.feature_image')</label>
            <input type="file" name="feature_image" id="feature_image" class="form-control" accept="image/*">
            @if(!empty($post?->feature_image_url))
                <p class="help-block">
                    <a href="{{ $post->feature_image_url }}" target="_blank">{{ $post->feature_image }}</a>
                </p>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <label class="checkbox-inline" style="margin-right: 15px;">
            <input type="checkbox" name="allow_comments" value="1" {{ old('allow_comments', $post->allow_comments ?? true) ? 'checked' : '' }}>
            @lang('cms::lang.allow_comments')
        </label>
        <label class="checkbox-inline" style="margin-right: 15px;">
            <input type="checkbox" name="show_author_card" value="1" {{ old('show_author_card', $post->show_author_card ?? true) ? 'checked' : '' }}>
            @lang('cms::lang.show_author_card')
        </label>
        <label class="checkbox-inline" style="margin-right: 15px;">
            <input type="checkbox" name="show_social_share" value="1" {{ old('show_social_share', $post->show_social_share ?? true) ? 'checked' : '' }}>
            @lang('cms::lang.show_social_share')
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="show_related_posts" value="1" {{ old('show_related_posts', $post->show_related_posts ?? true) ? 'checked' : '' }}>
            @lang('cms::lang.show_related_posts')
        </label>
    </div>
</div>

<hr>

<ul class="nav nav-tabs" role="tablist">
    @foreach($supportedLocales as $index => $localeCode)
        <li role="presentation" class="{{ $index === 0 ? 'active' : '' }}">
            <a href="#locale_{{ $localeCode }}" aria-controls="locale_{{ $localeCode }}" role="tab" data-toggle="tab">
                {{ strtoupper($localeCode) }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content" style="padding-top: 15px;">
    @foreach($supportedLocales as $index => $localeCode)
        @php
            $variant = $variantMap[$localeCode] ?? null;
            $sectionMap = $sectionMapByLocale[$localeCode] ?? [];
            $mediaSection = $sectionMap['media'] ?? [];
            $storySection = $sectionMap['story'] ?? [];
            $closingSection = $sectionMap['closing'] ?? [];
            $tagsSection = $sectionMap['tags']['items'] ?? [];
        @endphp
        <div role="tabpanel" class="tab-pane {{ $index === 0 ? 'active' : '' }}" id="locale_{{ $localeCode }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('cms::lang.title') ({{ strtoupper($localeCode) }}) *</label>
                        <input type="text" name="title[{{ $localeCode }}]" class="form-control" required value="{{ old("title.$localeCode", $variant?->title ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('cms::lang.slug')</label>
                        <input type="text" name="slug[{{ $localeCode }}]" class="form-control" value="{{ old("slug.$localeCode", $variant?->slug ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('cms::lang.status')</label>
                        <select name="variant_status[{{ $localeCode }}]" class="form-control">
                            @php($variantStatus = old("variant_status.$localeCode", $variant?->status ?? 'draft'))
                            <option value="draft" {{ $variantStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ $variantStatus === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ $variantStatus === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.hero_text')</label>
                        <textarea name="hero_text[{{ $localeCode }}]" rows="2" class="form-control">{{ old("hero_text.$localeCode", $variant?->hero_text ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.excerpt')</label>
                        <textarea name="excerpt[{{ $localeCode }}]" rows="2" class="form-control">{{ old("excerpt.$localeCode", $variant?->excerpt ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.content')</label>
                        <textarea name="content_html[{{ $localeCode }}]" rows="8" class="form-control">{{ old("content_html.$localeCode", $variant?->content_html ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.meta_title')</label>
                        <input type="text" name="meta_title[{{ $localeCode }}]" class="form-control" value="{{ old("meta_title.$localeCode", $variant?->meta_title ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.meta_description')</label>
                        <input type="text" name="meta_description[{{ $localeCode }}]" class="form-control" value="{{ old("meta_description.$localeCode", $variant?->meta_description ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.meta_keywords')</label>
                        <input type="text" name="meta_keywords[{{ $localeCode }}]" class="form-control" value="{{ old("meta_keywords.$localeCode", $variant?->meta_keywords ?? '') }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h5>@lang('cms::lang.structured_sections')</h5>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_lead')</label>
                        <textarea name="section_lead[{{ $localeCode }}]" class="form-control" rows="2">{{ old("section_lead.$localeCode", $sectionMap['lead']['text'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_quote_primary')</label>
                        <textarea name="section_quote_primary[{{ $localeCode }}]" class="form-control" rows="2">{{ old("section_quote_primary.$localeCode", $sectionMap['quote_primary']['text'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_story_title')</label>
                        <input type="text" name="section_story_title[{{ $localeCode }}]" class="form-control" value="{{ old("section_story_title.$localeCode", $storySection['title'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_story_body')</label>
                        <textarea name="section_story_body[{{ $localeCode }}]" class="form-control" rows="2">{{ old("section_story_body.$localeCode", $storySection['body'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_story_cta_label')</label>
                        <input type="text" name="section_story_cta_label[{{ $localeCode }}]" class="form-control" value="{{ old("section_story_cta_label.$localeCode", $storySection['cta_label'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_story_cta_url')</label>
                        <input type="url" name="section_story_cta_url[{{ $localeCode }}]" class="form-control" value="{{ old("section_story_cta_url.$localeCode", $storySection['cta_url'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_quote_secondary')</label>
                        <textarea name="section_quote_secondary[{{ $localeCode }}]" class="form-control" rows="2">{{ old("section_quote_secondary.$localeCode", $sectionMap['quote_secondary']['text'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_closing_title')</label>
                        <input type="text" name="section_closing_title[{{ $localeCode }}]" class="form-control" value="{{ old("section_closing_title.$localeCode", $closingSection['title'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>@lang('cms::lang.section_closing_body')</label>
                        <textarea name="section_closing_body[{{ $localeCode }}]" class="form-control" rows="2">{{ old("section_closing_body.$localeCode", $closingSection['body'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>@lang('cms::lang.tags')</label>
                        <input type="text" name="tags[{{ $localeCode }}]" class="form-control" value="{{ old("tags.$localeCode", implode(', ', $tagsSection)) }}" placeholder="tag-a, tag-b">
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach(['hero_image', 'body_image_one', 'split_image', 'body_image_two'] as $mediaKey)
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ ucfirst(str_replace('_', ' ', $mediaKey)) }}</label>
                            <input type="file" name="{{ $mediaKey }}[{{ $localeCode }}]" class="form-control" accept="image/*">
                            @if(!empty($mediaSection[$mediaKey]))
                                <p class="help-block">
                                    <a href="{{ asset('uploads/cms/' . rawurlencode($mediaSection[$mediaKey])) }}" target="_blank">{{ $mediaSection[$mediaKey] }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="text-right">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? __('messages.update') : __('messages.submit') }}</button>
</div>
