<div class="card mb-5 mb-xl-10" id="two_factor_settings_card">
    <div class="card-header cursor-pointer">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0">@lang('two_factor.auth_app_title')</h3>
        </div>
    </div>

    <div class="card-body p-9">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-6">
            <span class="badge {{ $user->two_factor_enabled ? 'badge-light-success' : 'badge-light-secondary' }}">
                {{ $user->two_factor_enabled ? __('two_factor.status_enabled') : __('two_factor.status_disabled') }}
            </span>
            <span class="text-muted fs-7">@lang('two_factor.auth_app_description')</span>
        </div>

        @if ($user->two_factor_enabled && ! empty($user->two_factor_confirmed_at))
            <div class="text-muted fs-7 mb-2">
                {{ __('two_factor.confirmed_at', ['datetime' => $user->two_factor_confirmed_at->format('Y-m-d H:i:s')]) }}
            </div>
            <div class="text-muted fs-7 mb-6">
                {{ __('two_factor.remaining_recovery_codes', ['count' => $two_factor_recovery_remaining]) }}
            </div>
        @endif

        @if (! empty($two_factor_recovery_codes))
            <div class="alert alert-warning mb-7">
                <div class="fw-bold mb-2">@lang('two_factor.recovery_codes_heading')</div>
                <div class="mb-3">@lang('two_factor.recovery_codes_notice')</div>
                <div class="mb-3">@lang('two_factor.recovery_codes_description')</div>

                <div class="row g-2 mb-3">
                    @foreach ($two_factor_recovery_codes as $recovery_code)
                        <div class="col-sm-4">
                            <code class="fs-6">{{ $recovery_code }}</code>
                        </div>
                    @endforeach
                </div>

                @if (! empty($two_factor_recovery_download_token))
                    <a
                        href="{{ route('users.two-factor.recovery.download', ['user' => $user->id, 'token' => $two_factor_recovery_download_token]) }}"
                        class="btn btn-sm btn-light-primary"
                    >
                        @lang('two_factor.recovery_download_action')
                    </a>
                    <div class="text-muted fs-8 mt-2">@lang('two_factor.recovery_download_once')</div>
                @endif
            </div>
        @endif

        @if (! $user->two_factor_enabled)
            @if ($can_manage_two_factor_self)
                @if (empty($two_factor_setup_payload))
                    <form method="POST" action="{{ route('users.two-factor.setup.start', ['user' => $user->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            @lang('two_factor.setup_action')
                        </button>
                    </form>
                @else
                    <div class="mb-4 text-muted">@lang('two_factor.setup_instructions')</div>

                    <div class="row g-6 mb-6">
                        <div class="col-lg-4">
                            <div class="fw-semibold mb-2">@lang('two_factor.scan_qr_label')</div>
                            <img src="{{ $two_factor_setup_qr_data_uri }}" class="img-fluid border rounded p-2 bg-white" alt="2FA QR Code">
                        </div>
                        <div class="col-lg-8">
                            <div class="fw-semibold mb-2">@lang('two_factor.manual_key_label')</div>
                            <code class="d-inline-block p-3 fs-5">{{ $two_factor_setup_payload['secret'] }}</code>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('users.two-factor.setup.confirm', ['user' => $user->id]) }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">@lang('two_factor.enter_code_label')</label>
                            <input
                                type="text"
                                name="code"
                                class="form-control @error('code') is-invalid @enderror"
                                autocomplete="one-time-code"
                                inputmode="numeric"
                                maxlength="6"
                                required
                            >
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success">@lang('two_factor.confirm_setup')</button>
                        </div>
                    </form>
                @endif
            @endif
        @else
            @if ($can_manage_two_factor_self)
                <form method="POST" action="{{ route('users.two-factor.disable', ['user' => $user->id]) }}" class="row g-3 align-items-end mb-6">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">@lang('two_factor.disable_confirm_label')</label>
                        <input
                            type="password"
                            name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            required
                        >
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-light-danger">@lang('two_factor.disable_action')</button>
                    </div>
                </form>
            @endif

            @if ($can_reset_two_factor)
                <form method="POST" action="{{ route('users.two-factor.reset', ['user' => $user->id]) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">@lang('two_factor.reset_action')</button>
                </form>
            @endif
        @endif
    </div>
</div>
