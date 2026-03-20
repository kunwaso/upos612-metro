<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('product.public_link_password') }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <script>
        if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
</head>
<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <style>
        body { background-image: url('{{ asset('assets/media/auth/bg4.jpg') }}'); }
        [data-bs-theme="dark"] body { background-image: url('{{ asset('assets/media/auth/bg4-dark.jpg') }}'); }
    </style>
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-column-fluid flex-lg-row">
            <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                <div class="d-flex flex-center flex-lg-start flex-column">
                    <a href="{{ url('/') }}" class="mb-7">
                        <img alt="{{ config('app.name') }}" src="{{ asset('assets/media/logos/custom-3.svg') }}" />
                    </a>
                    <h2 class="text-white fw-normal m-0">{{ __('product.public_quote_unlock_aside_tagline') }}</h2>
                </div>
            </div>
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                        <form method="POST" action="{{ $unlockFormAction }}" id="kt_public_quote_unlock_form" class="form w-100 mb-13">
                            @csrf
                            <div class="text-center mb-10">
                                <img alt="" class="mh-125px" src="{{ asset('assets/media/svg/misc/smartphone-2.svg') }}" />
                            </div>
                            <div class="text-center mb-10">
                                <h1 class="text-gray-900 mb-3">{{ __('product.public_quote_unlock_title') }}</h1>
                                <div class="text-muted fw-semibold fs-5 mb-5">{{ __('product.enter_password_to_view_quote') }}</div>
                            </div>

                            @if($isLocked)
                                <div class="alert alert-danger mb-10">{{ $lockedMessage ?? __('product.quote_unlock_blocked_10min') }}</div>
                            @endif

                            @if(session('status'))
                                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-10">
                                    {{ session('status.msg') }}
                                </div>
                            @endif

                            @if(isset($errors) && $errors->has('password'))
                                <div class="alert alert-danger mb-10">{{ $errors->first('password') }}</div>
                            @endif

                            @if($unlockInputMode === 'otp')
                                <div class="mb-10">
                                    <div class="fw-bold text-start text-gray-900 fs-6 mb-1 ms-1">
                                        @if($unlockOtpDigitsOnly)
                                            {{ __('product.public_quote_unlock_otp_label_digits', ['count' => $unlockOtpLength]) }}
                                        @else
                                            {{ __('product.public_quote_unlock_otp_label_chars', ['count' => $unlockOtpLength]) }}
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap flex-stack">
                                        @for($i = 1; $i <= $unlockOtpLength; $i++)
                                            <input
                                                type="text"
                                                name="code_{{ $i }}"
                                                id="quote_unlock_code_{{ $i }}"
                                                autocomplete="one-time-code"
                                                maxlength="1"
                                                class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2"
                                                data-quote-unlock-otp="1"
                                                @if($unlockOtpDigitsOnly)
                                                    data-inputmask="'mask': '9', 'placeholder': ''"
                                                    inputmode="numeric"
                                                @endif
                                                value="{{ old('code_'.$i) }}"
                                                {{ $isLocked ? 'disabled' : '' }}
                                            />
                                        @endfor
                                    </div>
                                </div>
                            @else
                                <div class="fv-row mb-10" data-kt-password-meter="true">
                                    <label class="form-label">{{ __('product.password') }}</label>
                                    <div class="mb-1">
                                        <div class="position-relative mb-3">
                                            <input
                                                type="password"
                                                name="password"
                                                id="quote_unlock_password"
                                                class="form-control form-control-solid"
                                                autocomplete="current-password"
                                                required
                                                {{ $isLocked ? 'disabled' : '' }}
                                            />
                                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility" role="button" tabindex="0">
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
                            @endif

                            <div class="d-flex flex-center">
                                <button type="submit" class="btn btn-lg btn-primary fw-bold" {{ $isLocked ? 'disabled' : '' }}>
                                    {{ __('product.unlock_link') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex flex-stack px-lg-10">
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <span class="text-muted fs-6">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    @if($unlockInputMode === 'otp')
        <script>
            (function () {
                var form = document.getElementById('kt_public_quote_unlock_form');
                if (!form) return;
                var inputs = [].slice.call(form.querySelectorAll('[data-quote-unlock-otp]'));
                if (inputs.length === 0) return;

                function focusAt(index) {
                    if (index >= 0 && index < inputs.length) {
                        inputs[index].focus();
                        try { inputs[index].select(); } catch (e) {}
                    }
                }

                inputs.forEach(function (input, index) {
                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !input.value && index > 0) {
                            e.preventDefault();
                            focusAt(index - 1);
                        }
                    });
                    input.addEventListener('input', function () {
                        var v = input.value;
                        if (v.length > 1) {
                            input.value = v.slice(-1);
                        }
                        if (input.value.length === 1 && index < inputs.length - 1) {
                            focusAt(index + 1);
                        }
                    });
                    input.addEventListener('keyup', function (e) {
                        if (e.key === 'ArrowLeft' && index > 0) {
                            focusAt(index - 1);
                        }
                        if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                            focusAt(index + 1);
                        }
                    });
                    input.addEventListener('paste', function (e) {
                        e.preventDefault();
                        var text = (e.clipboardData || window.clipboardData).getData('text') || '';
                        var chars = text.replace(/\s+/g, '').split('');
                        var j = index;
                        for (var k = 0; k < chars.length && j < inputs.length; k++, j++) {
                            inputs[j].value = chars[k];
                        }
                        focusAt(Math.min(j, inputs.length - 1));
                    });
                });

                var firstEmpty = inputs.findIndex(function (i) { return !i.value; });
                if (!document.activeElement || !form.contains(document.activeElement)) {
                    focusAt(firstEmpty === -1 ? 0 : firstEmpty);
                }
            })();
        </script>
    @endif
</body>
</html>
