<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('projectx::lang.public_link_password') }}</title>
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-light">
    @php($isLocked = !empty($locked))
    <div class="d-flex flex-column flex-root min-vh-100 align-items-center justify-content-center p-4">
        <div class="card card-flush w-100" style="max-width: 460px;">
            <div class="card-body p-10">
                <h3 class="fw-bold mb-3">{{ __('projectx::lang.public_link_password') }}</h3>
                <p class="text-muted mb-6">{{ __('projectx::lang.enter_password_to_view_quote') }}</p>

                @if($isLocked)
                    <div class="alert alert-danger mb-6">{{ $lockedMessage ?? __('projectx::lang.quote_unlock_blocked_10min') }}</div>
                @endif

                @if(session('status'))
                    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">
                        {{ session('status.msg') }}
                    </div>
                @endif

                @if(isset($errors) && $errors->has('password') && !$isLocked)
                    <div class="alert alert-danger mb-6">{{ $errors->first('password') }}</div>
                @endif

                <form method="POST" action="{{ route('projectx.quotes.public.unlock', ['publicToken' => $publicToken]) }}">
                    @csrf

                    <div class="fv-row mb-6" data-kt-password-meter="true">
                        <label class="form-label">{{ __('projectx::lang.password') }}</label>
                        <div class="mb-1">
                            <div class="position-relative mb-3">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control form-control-solid"
                                    required
                                    {{ $isLocked ? 'disabled' : '' }}
                                />
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                    <i class="ki-duotone ki-eye-slash fs-2"></i>
                                    <i class="ki-duotone ki-eye fs-2 d-none"></i>
                                </span>
                            </div>

                            <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                                <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" {{ $isLocked ? 'disabled' : '' }}>
                        {{ __('projectx::lang.unlock_link') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('modules/projectx/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('modules/projectx/js/scripts.bundle.js') }}"></script>
</body>
</html>
