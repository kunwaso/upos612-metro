@extends('layouts.auth2')

@section('title', __('lang_v1.reset_password'))
@section('aside_title', __('lang_v1.reset_password'))
@section('aside_subtitle', __('lang_v1.send_password_reset_link'))

@section('auth_content')
    <div class="w-100" data-auth-view="password-reset">
        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('lang_v1.reset_password')</h1>
            <div class="text-gray-500 fw-semibold fs-6">@lang('lang_v1.send_password_reset_link')</div>
        </div>

        <form class="form w-100" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.email_address')</label>
                <input type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus
                    class="form-control bg-transparent {{ $errors->has('email') ? 'is-invalid' : '' }}" />
                @if ($errors->has('email'))
                    <div class="invalid-feedback d-block">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.password')</label>
                <input type="password" name="password" required
                    class="form-control bg-transparent {{ $errors->has('password') ? 'is-invalid' : '' }}" />
                @if ($errors->has('password'))
                    <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-semibold text-gray-900">@lang('business.confirm_password')</label>
                <input type="password" name="password_confirmation" required
                    class="form-control bg-transparent {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}" />
                @if ($errors->has('password_confirmation'))
                    <div class="invalid-feedback d-block">{{ $errors->first('password_confirmation') }}</div>
                @endif
            </div>

            <div class="d-grid mb-8">
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label">@lang('lang_v1.reset_password')</span>
                </button>
            </div>
        </form>

        <div class="text-center">
            <a href="{{ route('login', ['lang' => request()->query('lang')]) }}" class="link-primary fw-semibold">
                @lang('messages.go_back')
            </a>
        </div>
    </div>
@endsection
