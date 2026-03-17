@extends('projectx::site_manager.auth.layout')

@section('title', __('lang_v1.login'))
@section('aside_title', __('lang_v1.welcome_back'))
@section('aside_subtitle', __('lang_v1.login_to_your') . ' ' . config('app.name', 'ultimatePOS'))

@section('auth_content')
    <div class="w-100" data-auth-view="projectx-login">
        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('lang_v1.login')</h1>
            <div class="text-gray-500 fw-semibold fs-6">{{ config('app.name', 'ultimatePOS') }}</div>
        </div>

        @if (session('status'))
            @php
                $status = session('status');
                $isSuccess = is_array($status) ? !empty($status['success']) : true;
                $statusMsg = is_array($status) ? ($status['msg'] ?? '') : (string) $status;
            @endphp
            @if ($statusMsg !== '')
                <div class="alert alert-{{ $isSuccess ? 'success' : 'danger' }} mb-8">{{ $statusMsg }}</div>
            @endif
        @endif

        <form class="form w-100" method="POST" action="{{ route('login') }}" id="login-form">
            @csrf

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.username')</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                    class="form-control bg-transparent {{ $errors->has('username') ? 'is-invalid' : '' }}" />
                @if ($errors->has('username'))
                    <div class="invalid-feedback d-block">{{ $errors->first('username') }}</div>
                @endif
            </div>

            <div class="fv-row mb-3">
                <div class="d-flex flex-stack mb-2">
                    <label class="form-label fw-semibold text-gray-900 mb-0">@lang('lang_v1.password')</label>
                    @if (config('app.env') != 'demo')
                        <a href="{{ route('password.request') }}" class="link-primary fs-7 fw-bold">
                            @lang('lang_v1.forgot_your_password')
                        </a>
                    @endif
                </div>
                <input type="password" name="password" required
                    class="form-control bg-transparent {{ $errors->has('password') ? 'is-invalid' : '' }}" />
                @if ($errors->has('password'))
                    <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <div class="fv-row mb-6 mt-6">
                <label class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} />
                    <span class="form-check-label fw-semibold text-gray-700 fs-6">@lang('lang_v1.remember_me')</span>
                </label>
            </div>

            @if (config('constants.enable_recaptcha'))
                <div class="mb-8">
                    <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                    @if ($errors->has('g-recaptcha-response'))
                        <div class="text-danger fs-7 mt-2">{{ $errors->first('g-recaptcha-response') }}</div>
                    @endif
                </div>
            @endif

            <div class="d-grid mb-10">
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label">@lang('lang_v1.login')</span>
                </button>
            </div>
        </form>

        @if (config('constants.allow_registration') && \Route::has('register'))
            <div class="text-gray-500 text-center fw-semibold fs-6">
                {{ __('business.not_yet_registered') }}
                <a href="{{ route('register') }}" class="link-primary fw-semibold">{{ __('business.register_now') }}</a>
            </div>
        @endif
    </div>
@endsection

@section('javascript')
    @if (config('constants.enable_recaptcha'))
        <script src="https://www.google.com/recaptcha/api.js"></script>
    @endif
@endsection
