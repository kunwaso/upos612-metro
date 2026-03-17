@extends('layouts.auth2')

@section('title', __('business.register_now'))
@section('aside_title', __('business.register_now'))
@section('aside_subtitle', __('business.register_and_get_started_in_minutes'))

@section('auth_content')
    <div class="w-100 text-center" data-auth-view="legacy-register-redirect">
        <h1 class="text-gray-900 fw-bolder mb-4">{{ __('business.register_now') }}</h1>
        <div class="text-gray-500 fw-semibold fs-6 mb-8">
            {{ __('business.register_and_get_started_in_minutes') }}
        </div>

        <div class="d-grid gap-3">
            <a href="{{ route('business.getRegister', ['lang' => request()->query('lang')]) }}" class="btn btn-primary">
                {{ __('business.register_now') }}
            </a>
            <a href="{{ route('login', ['lang' => request()->query('lang')]) }}" class="btn btn-light-primary">
                {{ __('business.sign_in') }}
            </a>
        </div>
    </div>
@endsection
