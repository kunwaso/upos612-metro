@extends('projectx::layouts.main')

@section('title', __('essentials::lang.add_knowledge_base'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.add_knowledge_base')</h1>
    </div>
    <a href="{{ route('projectx.essentials.knowledge-base.index') }}" class="btn btn-light-primary btn-sm">@lang('business.back')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <form method="POST" action="{{ route('projectx.essentials.knowledge-base.store') }}">
            @csrf
            <input type="hidden" name="parent_id" value="{{ !empty($parent) ? $parent->id : '' }}">
            <input type="hidden" name="kb_type" value="{{ !empty($parent) ? ($parent->kb_type === 'knowledge_base' ? 'section' : 'article') : 'knowledge_base' }}">
            <div class="row g-5">
                <div class="col-12">
                    <label class="form-label required">@lang('essentials::lang.title')</label>
                    <input type="text" name="title" class="form-control form-control-solid" value="{{ old('title') }}" required>
                </div>
                @if(empty($parent))
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.share_with')</label>
                        <select name="share_with" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="public">@lang('essentials::lang.public')</option>
                            <option value="private">@lang('essentials::lang.private')</option>
                            <option value="only_with">@lang('essentials::lang.only_with')</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.user')</label>
                        <select name="user_ids[]" class="form-select form-select-solid" data-control="select2" multiple>
                            @foreach($users as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12">
                    <label class="form-label">@lang('essentials::lang.content')</label>
                    <textarea name="content" rows="8" class="form-control form-control-solid">{{ old('content') }}</textarea>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-6">
                <button type="submit" class="btn btn-primary btn-sm">@lang('essentials::lang.submit')</button>
            </div>
        </form>
    </div>
</div>
@endsection
