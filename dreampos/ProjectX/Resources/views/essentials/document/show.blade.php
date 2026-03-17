@extends('projectx::layouts.main')

@section('title', __('essentials::lang.memo'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ $memo->name }}</h1>
        <div class="text-muted fs-7">@lang('essentials::lang.memo')</div>
    </div>
    <a href="{{ route('projectx.essentials.documents.index', ['type' => 'memos']) }}" class="btn btn-light-primary btn-sm">@lang('business.back')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <div class="mb-5">
            <div class="fw-semibold text-gray-700">@lang('essentials::lang.description')</div>
            <div class="text-gray-900 mt-2">{!! nl2br(e($memo->description)) !!}</div>
        </div>
        <div class="text-muted">@if(!empty($memo->created_at)) @format_datetime($memo->created_at) @endif</div>
    </div>
</div>
@endsection
