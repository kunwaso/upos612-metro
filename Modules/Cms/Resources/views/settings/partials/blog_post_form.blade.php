@php
    $isEdit = !empty($post);
@endphp

{!! Form::open(['url' => $form_action, 'method' => $method, 'files' => true, 'id' => $isEdit ? 'edit_blog_post_form' : 'create_blog_post_form']) !!}
    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                {!! Form::label('title', __('cms::lang.title') . ':*') !!}
                {!! Form::text('title', old('title', $post->title ?? ''), ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('priority', __('cms::lang.priority') . ':') !!}
                {!! Form::number('priority', old('priority', $post->priority ?? 0), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('hero_text', __('cms::lang.hero_text') . ':') !!}
                {!! Form::textarea('hero_text', old('hero_text', $meta['hero_text'] ?? ''), ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('content', __('cms::lang.content') . ':') !!}
                {!! Form::textarea('content', old('content', $post->content ?? ''), ['class' => 'form-control', 'rows' => 10]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('category', __('cms::lang.category') . ':') !!}
                {!! Form::text('category', old('category', $meta['category'] ?? ''), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('tags', __('cms::lang.tags') . ':') !!}
                {!! Form::text('tags', old('tags', $post->tags ?? ''), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="checkbox" style="margin-top: 30px;">
                <label>
                    {!! Form::checkbox('is_enabled', 1, old('is_enabled', $post->is_enabled ?? true)) !!}
                    @lang('cms::lang.is_enabled')
                </label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('feature_image', __('cms::lang.feature_image') . ':') !!}
                {!! Form::file('feature_image', ['accept' => 'image/*']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('banner_image', __('cms::lang.banner_image') . ':') !!}
                {!! Form::file('banner_image', ['accept' => 'image/*']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('meta_title', __('cms::lang.meta_title') . ':') !!}
                {!! Form::text('meta_title', old('meta_title', $meta['meta_title'] ?? ''), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('meta_description', __('cms::lang.meta_description') . ':') !!}
                {!! Form::text('meta_description', old('meta_description', $post->meta_description ?? ''), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('meta_keywords', __('cms::lang.meta_keywords') . ':') !!}
                {!! Form::text('meta_keywords', old('meta_keywords', $meta['meta_keywords'] ?? ''), ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">{{ $isEdit ? __('messages.update') : __('messages.submit') }}</button>
        </div>
    </div>
{!! Form::close() !!}
