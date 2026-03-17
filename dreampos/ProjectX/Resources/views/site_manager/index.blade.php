@extends('projectx::layouts.main')

@section('title', __('projectx::lang.site_manager'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.site_manager') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.site_manager_description') }}</div>
    </div>
    @if(auth()->user()->can('projectx.site_manager.edit'))
        <a href="{{ route('projectx.site_manager.edit') }}" class="btn btn-primary btn-sm">
            <i class="ki-duotone ki-pencil fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('projectx::lang.edit') }}
        </a>
    @endif
</div>

@if(session('status') && is_array(session('status')) && !empty(session('status.success')))
    <div class="alert alert-success alert-dismissible d-flex align-items-center mb-5">
        <i class="ki-duotone ki-check-circle fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
        <div class="d-flex flex-column"><h4 class="mb-1 text-dark">{{ session('status.msg') ?? __('lang_v.success') }}</h4></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card card-flush">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.welcome_page_settings') }}</h3>
    </div>
    <div class="card-body pt-5">
        <div class="row g-5">
            <div class="col-md-6">
                <label class="form-label text-muted">{{ __('projectx::lang.site_name') }}</label>
                <div class="fw-semibold text-gray-900">{{ $settings['site_name'] ?? config('app.name') }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">{{ __('projectx::lang.hero_title') }}</label>
                <div class="fw-semibold text-gray-900">{{ $settings['hero_title'] ?? config('app.name') }}</div>
            </div>
            <div class="col-12">
                <label class="form-label text-muted">{{ __('projectx::lang.hero_subtitle') }}</label>
                <div class="fw-semibold text-gray-900">{{ $settings['hero_subtitle'] ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">{{ __('projectx::lang.cta_label') }}</label>
                <div class="fw-semibold text-gray-900">{{ $settings['cta_label'] ?? 'Sign In' }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">{{ __('projectx::lang.cta_url') }}</label>
                <div class="fw-semibold text-gray-900">{{ $settings['cta_url'] ?? route('login') }}</div>
            </div>
        </div>
        <div class="mt-5">
            <a href="{{ url('/') }}" target="_blank" class="btn btn-light-primary">{{ __('projectx::lang.view_welcome_page') }}</a>
        </div>
    </div>
</div>
@endsection
