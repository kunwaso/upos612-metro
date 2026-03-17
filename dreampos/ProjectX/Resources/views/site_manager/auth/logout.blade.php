@extends('projectx::site_manager.auth.layout')

@section('title', __('lang_v1.logout'))
@section('aside_title', config('app.name', 'ultimatePOS'))
@section('aside_subtitle', __('lang_v1.you_are_logged_out'))

@section('auth_content')
    <div class="w-100 text-center" data-auth-view="projectx-logout">
        <div class="mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">{{ config('app.name', 'ultimatePOS') }}</h1>
            <div class="text-gray-500 fw-semibold fs-6">@lang('lang_v1.you_are_logged_out')</div>
        </div>

        <div class="d-grid">
            <a href="{{ route('login') }}" class="btn btn-primary">{{ __('business.sign_in') }}</a>
        </div>
    </div>
@endsection
