@extends('projectx::layouts.main')

@section('title', __('essentials::lang.knowledge_base'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ $kb_object->title }}</h1>
        @if(!empty($kb_object->share_with))
            <div class="text-muted fs-7">
                <strong>@lang('essentials::lang.share_with'):</strong>
                @lang('essentials::lang.' . $kb_object->share_with)
                @if($kb_object->share_with === 'only_with' && !empty($users))
                    ({{ implode(', ', $users) }})
                @endif
            </div>
        @endif
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->can('essentials.edit_knowledge_base'))
            <a href="{{ route('projectx.essentials.knowledge-base.edit', ['knowledge_base' => $kb_object->id]) }}" class="btn btn-light-primary btn-sm">@lang('messages.edit')</a>
        @endif
        <a href="{{ route('projectx.essentials.knowledge-base.index') }}" class="btn btn-light btn-sm">@lang('business.back')</a>
    </div>
</div>

<div class="row g-7">
    <div class="col-xl-3">
        <div class="card card-flush">
            <div class="card-body pt-7">
                @include('projectx::essentials.knowledge_base.partials.sidebar', ['knowledge_base' => $knowledge_base, 'kb_object' => $kb_object, 'section_id' => $section_id, 'article_id' => $article_id])
            </div>
        </div>
    </div>
    <div class="col-xl-9">
        <div class="card card-flush">
            <div class="card-body pt-7">
                {!! $kb_object->content !!}
            </div>
        </div>
    </div>
</div>
@endsection
