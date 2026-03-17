@extends('layouts.auth2')

@section('title', config('app.name', 'ultimatePOS'))
@section('aside_title', config('app.name', 'ultimatePOS'))
@section('aside_subtitle', config('constants.app_title', __('lang_v1.login_to_your') . ' ' . config('app.name', 'ultimatePOS')))

@section('auth_content')
    <div class="w-100 text-center" data-auth-view="welcome">
        <h1 class="text-gray-900 fw-bolder mb-4">{{ config('app.name', 'ultimatePOS') }}</h1>
        <div class="text-gray-500 fw-semibold fs-5 mb-10">{{ config('constants.app_title', '') }}</div>

        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <a href="{{ route('login', ['lang' => request()->query('lang')]) }}" class="btn btn-primary">
                {{ __('business.sign_in') }}
            </a>
            @if (config('constants.allow_registration'))
                <a href="{{ route('business.getRegister', ['lang' => request()->query('lang')]) }}" class="btn btn-light-primary">
                    {{ __('business.register_now') }}
                </a>
            @endif
        </div>
    </div>
@endsection
