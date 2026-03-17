@extends('projectx::site_manager.auth.layout')

@section('title', __('lang_v1.lock_screen'))
@section('aside_title', __('lang_v1.lock_screen'))
@section('aside_subtitle', __('lang_v1.lock_screen_enter_pin'))

@section('auth_content')
    @php
        $profilePhoto = !empty($user->media) ? $user->media->display_url : asset('modules/projectx/media/avatars/blank.png');
    @endphp
    <div class="w-100" data-auth-view="projectx-lock-screen">
        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">@lang('lang_v1.lock_screen')</h1>
            <div class="text-gray-500 fw-semibold fs-6">@lang('lang_v1.lock_screen_enter_pin')</div>
        </div>

        <div class="d-flex align-items-center mb-8">
            <img src="{{ $profilePhoto }}" alt="profile-photo" class="rounded-circle w-50px h-50px me-3" />
            <span class="fw-semibold text-gray-900">{{ trim($user->first_name . ' ' . $user->last_name) }}</span>
        </div>

        <form class="form w-100" method="POST" action="{{ route('lock-screen.unlock') }}">
            @csrf

            <div class="fv-row mb-6">
                <label class="form-label fw-semibold text-gray-900">@lang('lang_v1.lock_screen_pin')</label>
                <input type="password" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]*" required
                    class="form-control bg-transparent {{ $errors->has('pin') ? 'is-invalid' : '' }}" />
                @if ($errors->has('pin'))
                    <div class="invalid-feedback d-block">{{ $errors->first('pin') }}</div>
                @endif
            </div>

            <div class="text-muted fs-7 mb-6">
                @lang('lang_v1.lock_screen_attempts_remaining', ['count' => $remaining_attempts])
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <span class="indicator-label">@lang('lang_v1.unlock')</span>
                </button>
            </div>
        </form>
    </div>
@endsection
