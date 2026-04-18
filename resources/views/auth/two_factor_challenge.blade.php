@extends('layouts.auth2')

@section('title', __('two_factor.challenge_title'))
@section('aside_title', __('two_factor.challenge_title'))
@section('aside_subtitle', __('two_factor.challenge_description'))

@section('auth_content')
    <div class="w-100" data-auth-view="two-factor-challenge">
        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('two_factor.challenge_title')</h1>
            <div class="text-gray-500 fw-semibold fs-6">@lang('two_factor.challenge_description')</div>
        </div>

        @if (session('status') && is_array(session('status')) && ! empty(session('status.msg')))
            <div class="alert alert-{{ ! empty(session('status.success')) ? 'success' : 'danger' }} mb-8">
                {{ session('status.msg') }}
            </div>
        @endif

        @if ($isLocked)
            <div class="alert alert-danger mb-8">
                @lang('two_factor.challenge_locked')
            </div>
        @endif

        <form class="form w-100 mb-8" method="POST" action="{{ route('two-factor.challenge.verify') }}">
            @csrf

            <div class="fv-row mb-4">
                <label class="form-label fw-semibold text-gray-900">@lang('two_factor.enter_code_label')</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    value="{{ old('code') }}"
                    class="form-control bg-transparent @error('code') is-invalid @enderror"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    required
                    {{ $isLocked ? 'disabled' : '' }}
                />
                @error('code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="text-muted fs-7 mt-2">
                    {{ __('two_factor.remaining_attempts', ['count' => max(0, $remainingAttempts)]) }}
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : '' }}>
                    @lang('two_factor.challenge_verify_action')
                </button>
            </div>
        </form>

        <div class="separator separator-content my-10">
            <span class="text-gray-500 fw-semibold fs-7">@lang('two_factor.recovery_code_label')</span>
        </div>

        <form class="form w-100" method="POST" action="{{ route('two-factor.challenge.recovery') }}">
            @csrf

            <div class="fv-row mb-4">
                <label class="form-label fw-semibold text-gray-900">@lang('two_factor.recovery_code_label')</label>
                <input
                    type="text"
                    name="recovery_code"
                    value="{{ old('recovery_code') }}"
                    class="form-control bg-transparent @error('recovery_code') is-invalid @enderror"
                    autocomplete="one-time-code"
                    required
                    {{ $isLocked ? 'disabled' : '' }}
                />
                @error('recovery_code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-light-primary" {{ $isLocked ? 'disabled' : '' }}>
                    @lang('two_factor.recovery_verify_action')
                </button>
            </div>
        </form>
    </div>
@endsection
