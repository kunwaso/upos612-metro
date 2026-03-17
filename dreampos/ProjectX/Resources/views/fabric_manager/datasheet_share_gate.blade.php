<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('projectx::lang.share_password_gate_title') }}</title>
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-light">
    <div class="d-flex flex-column flex-root min-vh-100 align-items-center justify-content-center p-4">
        <div class="card card-flush w-100" style="max-width: 460px;">
            <div class="card-body p-10">
                <h3 class="fw-bold mb-3">{{ __('projectx::lang.share_password_gate_title') }}</h3>
                <p class="text-muted mb-6">{{ __('projectx::lang.share_password_gate_message') }}</p>

                @if(!empty($errorMessage))
                    <div class="alert alert-danger mb-6">{{ $errorMessage }}</div>
                @endif

                <form method="POST" action="{{ route('projectx.fabric_manager.datasheet.share.verify', ['token' => $token]) }}">
                    @csrf
                    <div class="mb-6">
                        <label class="form-label">{{ __('projectx::lang.password') }}</label>
                        <input type="password" name="password" class="form-control form-control-solid" {{ !empty($locked) ? 'disabled' : '' }} required />
                        @error('password')
                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100" {{ !empty($locked) ? 'disabled' : '' }}>{{ __('projectx::lang.unlock_link') }}</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
