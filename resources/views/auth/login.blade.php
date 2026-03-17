@extends('layouts.auth2')

@section('title', __('lang_v1.login'))
@section('aside_title', __('lang_v1.welcome_back'))
@section('aside_subtitle', __('lang_v1.login_to_your') . ' ' . config('app.name', 'ultimatePOS'))

@section('auth_content')
    <div class="w-100" data-auth-view="core-login">
        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('lang_v1.login')</h1>
            <div class="text-gray-500 fw-semibold fs-6">{{ config('app.name', 'ultimatePOS') }}</div>
        </div>

        @if (session('status'))
            @if (is_array(session('status')) && !empty(session('status.msg')))
                <div class="alert alert-{{ !empty(session('status.success')) ? 'success' : 'danger' }} mb-8">
                    {{ session('status.msg') }}
                </div>
            @elseif (is_string(session('status')) && session('status') !== '')
                <div class="alert alert-success mb-8">{{ session('status') }}</div>
            @endif
        @endif

        <form class="form w-100" method="POST" action="{{ route('login') }}" id="login-form">
            @csrf

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.username')</label>
                <input id="username" type="text" name="username" value="{{ old('username', $username) }}" required autofocus
                    class="form-control bg-transparent {{ $errors->has('username') ? 'is-invalid' : '' }}" />
                @if ($errors->has('username'))
                    <div class="invalid-feedback d-block">{{ $errors->first('username') }}</div>
                @endif
            </div>

            <div class="fv-row mb-3">
                <div class="d-flex flex-stack mb-2">
                    <label class="form-label fw-semibold text-gray-900 mb-0">@lang('lang_v1.password')</label>
                    @if (config('app.env') !== 'demo')
                        <a href="{{ route('password.request', ['lang' => request()->query('lang')]) }}" class="link-primary fs-7 fw-bold">
                            @lang('lang_v1.forgot_your_password')
                        </a>
                    @endif
                </div>

                <div class="position-relative">
                    <input id="password" type="password" name="password" value="{{ $password }}" required
                        class="form-control bg-transparent pe-12 {{ $errors->has('password') ? 'is-invalid' : '' }}" />
                    <button type="button" id="kt_login_password_toggle"
                        class="btn btn-sm btn-icon position-absolute translate-middle-y top-50 end-0 me-2">
                        <i class="ki-duotone ki-eye-slash fs-2" data-password-icon="hidden"></i>
                        <i class="ki-duotone ki-eye fs-2 d-none" data-password-icon="visible"></i>
                    </button>
                </div>

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

        @if (config('constants.allow_registration'))
            <div class="text-gray-500 text-center fw-semibold fs-6">
                {{ __('business.not_yet_registered') }}
                <a href="{{ route('business.getRegister', ['lang' => request()->query('lang')]) }}" class="link-primary fw-semibold">
                    {{ __('business.register_now') }}
                </a>
            </div>
        @endif

        @if (!empty($demo_types))
            <div class="separator separator-content my-10">
                <span class="text-gray-500 fw-semibold fs-7">Demo</span>
            </div>

            <div class="card card-flush border border-gray-200">
                <div class="card-header">
                    <div class="card-title flex-column align-items-start">
                        <span class="text-gray-900 fw-bolder fs-4">Demo Shops</span>
                        <span class="text-gray-500 fw-semibold fs-7">
                            Demos are for example purposes only. Choose a business profile to sign in instantly.
                        </span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        @foreach ($demo_types as $key => $demo_type)
                            <div class="col-sm-6">
                                <a href="{{ route('login', ['demo_type' => $key, 'lang' => request()->query('lang')]) }}"
                                    class="btn btn-light-primary w-100 h-100 demo-login"
                                    data-admin="{{ $demo_type['username'] }}"
                                    data-password="{{ $password }}"
                                    title="{{ $demo_type['description'] }}">
                                    {{ $demo_type['label'] }}
                                </a>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <a href="{{ url('docs') }}" target="_blank" rel="noopener" class="btn btn-light w-100">
                                Connector Module / API Documentation
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('javascript')
    @if (config('constants.enable_recaptcha'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var toggleButton = document.getElementById('kt_login_password_toggle');
            var hiddenIcon = toggleButton ? toggleButton.querySelector('[data-password-icon="hidden"]') : null;
            var visibleIcon = toggleButton ? toggleButton.querySelector('[data-password-icon="visible"]') : null;

            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    var showPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', showPassword ? 'text' : 'password');
                    if (hiddenIcon) {
                        hiddenIcon.classList.toggle('d-none', showPassword);
                    }
                    if (visibleIcon) {
                        visibleIcon.classList.toggle('d-none', !showPassword);
                    }
                });
            }

            document.querySelectorAll('.demo-login').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (!passwordInput) {
                        return;
                    }

                    var usernameInput = document.getElementById('username');
                    if (usernameInput) {
                        usernameInput.value = button.getAttribute('data-admin') || '';
                    }
                    passwordInput.value = button.getAttribute('data-password') || '';

                    var form = document.getElementById('login-form');
                    if (form) {
                        form.submit();
                    }
                });
            });
        })();
    </script>
@endsection
