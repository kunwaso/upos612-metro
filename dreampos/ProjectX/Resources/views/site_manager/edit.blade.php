@extends('projectx::layouts.main')

@section('title', __('projectx::lang.site_manager') . ' - ' . __('projectx::lang.edit'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <a href="{{ route('projectx.site_manager.index') }}" class="btn btn-light-primary btn-sm mb-2">
            <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('projectx::lang.site_manager') }}
        </a>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.edit') }} {{ __('projectx::lang.welcome_page_settings') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.site_manager_description') }}</div>
    </div>
</div>

@if(session('status') && is_array(session('status')) && empty(session('status.success')))
    <div class="alert alert-danger alert-dismissible d-flex align-items-center mb-5">
        <i class="ki-duotone ki-information fs-2hx text-danger me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
        <div class="d-flex flex-column"><h4 class="mb-1 text-dark">{{ session('status.msg') ?? __('messages.something_went_wrong') }}</h4></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card card-flush">
    <div class="card-body pt-5">
        <form method="POST" action="{{ route('projectx.site_manager.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label" for="site_name">{{ __('projectx::lang.site_name') }}</label>
                    <input type="text" id="site_name" name="site_name" class="form-control form-control-solid" value="{{ old('site_name', $settings['site_name'] ?? config('app.name')) }}" maxlength="255" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="logo_url">{{ __('projectx::lang.logo_url') }}</label>
                    <input type="text" id="logo_url" name="logo_url" class="form-control form-control-solid" value="{{ old('logo_url', $settings['logo_url'] ?? '') }}" maxlength="500" placeholder="https://..." />
                </div>
                <div class="col-12">
                    <label class="form-label" for="hero_title">{{ __('projectx::lang.hero_title') }}</label>
                    <input type="text" id="hero_title" name="hero_title" class="form-control form-control-solid" value="{{ old('hero_title', $settings['hero_title'] ?? config('app.name')) }}" maxlength="500" />
                </div>
                <div class="col-12">
                    <label class="form-label" for="hero_subtitle">{{ __('projectx::lang.hero_subtitle') }}</label>
                    <textarea id="hero_subtitle" name="hero_subtitle" class="form-control form-control-solid" rows="2" maxlength="1000">{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cta_label">{{ __('projectx::lang.cta_label') }}</label>
                    <input type="text" id="cta_label" name="cta_label" class="form-control form-control-solid" value="{{ old('cta_label', $settings['cta_label'] ?? 'Sign In') }}" maxlength="100" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cta_url">{{ __('projectx::lang.cta_url') }}</label>
                    <input type="text" id="cta_url" name="cta_url" class="form-control form-control-solid" value="{{ old('cta_url', $settings['cta_url'] ?? route('login')) }}" maxlength="500" />
                </div>
                <div class="col-12">
                    <label class="form-label" for="footer_copyright">{{ __('projectx::lang.footer_copyright') }}</label>
                    <input type="text" id="footer_copyright" name="footer_copyright" class="form-control form-control-solid" value="{{ old('footer_copyright', $settings['footer_copyright'] ?? '') }}" maxlength="500" placeholder="© {{ date('Y') }} {{ config('app.name') }}" />
                </div>
                <div class="col-12">
                    <label class="form-label" for="nav_items">{{ __('projectx::lang.nav_items') }}</label>
                    @php
    $navItemsValue = old('nav_items');
    if ($navItemsValue === null) {
        $navItemsValue = $settings['nav_items'] ?? [];
    }
    $navItemsValue = is_array($navItemsValue) ? json_encode($navItemsValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $navItemsValue;
@endphp
                    <textarea id="nav_items" name="nav_items" class="form-control form-control-solid font-monospace" rows="5" placeholder='[{"label":"Home","url":"#"},{"label":"How it Works","url":"#how-it-works"}]'>{{ $navItemsValue }}</textarea>
                    <div class="form-text">{{ __('projectx::lang.nav_items_help') }}</div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-8">
                <a href="{{ route('projectx.site_manager.index') }}" class="btn btn-light me-3">{{ __('projectx::lang.cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('projectx::lang.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
