@extends('projectx::site_manager.auth.layout')

@section('title', __('lang_v1.reset_password'))
@section('aside_title', __('lang_v1.forgot_your_password'))
@section('aside_subtitle', __('lang_v1.send_password_reset_link'))

@section('auth_content')
    <div class="w-100" data-auth-view="projectx-forgot">
        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('lang_v1.forgot_your_password')</h1>
            <div class="text-gray-500 fw-semibold fs-6">@lang('lang_v1.send_password_reset_link')</div>
        </div>

        @if (session('status') && is_string(session('status')))
            <div class="alert alert-success mb-8">{{ session('status') }}</div>
        @endif

        <form class="form w-100" method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.email_address')</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="form-control bg-transparent {{ $errors->has('email') ? 'is-invalid' : '' }}" />
                @if ($errors->has('email'))
                    <div class="invalid-feedback d-block">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <div class="d-grid mb-8">
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label">@lang('lang_v1.send_password_reset_link')</span>
                </button>
            </div>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" class="link-primary fw-semibold">@lang('messages.go_back')</a>
        </div>
    </div>
@endsection
