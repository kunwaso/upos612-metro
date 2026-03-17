@extends('projectx::layouts.main')

@section('title', __('essentials::lang.edit_knowledge_base'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.edit_knowledge_base')</h1>
    </div>
    <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $kb->id]) }}" class="btn btn-light-primary btn-sm">@lang('messages.view')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <form method="POST" action="{{ route('projectx.essentials.knowledge-base.update', ['knowledge_base' => $kb->id]) }}">
            @csrf
            @method('PUT')
            <div class="row g-5">
                <div class="col-12">
                    <label class="form-label required">@lang('essentials::lang.title')</label>
                    <input type="text" name="title" class="form-control form-control-solid" value="{{ old('title', $kb->title) }}" required>
                </div>
                @if($kb->kb_type === 'knowledge_base')
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.share_with')</label>
                        <select name="share_with" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="public" {{ old('share_with', $kb->share_with) === 'public' ? 'selected' : '' }}>@lang('essentials::lang.public')</option>
                            <option value="private" {{ old('share_with', $kb->share_with) === 'private' ? 'selected' : '' }}>@lang('essentials::lang.private')</option>
                            <option value="only_with" {{ old('share_with', $kb->share_with) === 'only_with' ? 'selected' : '' }}>@lang('essentials::lang.only_with')</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.user')</label>
                        <select name="user_ids[]" class="form-select form-select-solid" data-control="select2" multiple>
                            @foreach($users as $id => $label)
                                <option value="{{ $id }}" {{ in_array((int) $id, array_map('intval', old('user_ids', $kb->users->pluck('id')->toArray())), true) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12">
                    <label class="form-label">@lang('essentials::lang.content')</label>
                    <textarea name="content" rows="8" class="form-control form-control-solid">{{ old('content', $kb->content) }}</textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-6">
                <button type="submit" class="btn btn-primary btn-sm">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>
@endsection
