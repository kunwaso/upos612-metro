@php
    $siteName = config('app.name', 'ultimatePOS');
    $asset = fn ($path) => asset('modules/projectx/' . ltrim($path, '/'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', $siteName)</title>
    <link rel="shortcut icon" href="{{ $asset('media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ $asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ $asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root">
        <style>
            body { background-image: url('{{ $asset('media/auth/bg4.jpg') }}'); }
            [data-bs-theme="dark"] body { background-image: url('{{ $asset('media/auth/bg4-dark.jpg') }}'); }
        </style>

        <div class="d-flex flex-column flex-column-fluid flex-lg-row">
            <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                <div class="d-flex flex-center flex-lg-start flex-column">
                    <a href="{{ url('/') }}" class="mb-7">
                        <img alt="{{ $siteName }}" src="{{ $asset('media/logos/custom-3.svg') }}" />
                    </a>
                    <h2 class="text-white fw-normal m-0">@yield('aside_title', $siteName)</h2>
                    <div class="text-white opacity-75 mt-3">
                        @yield('aside_subtitle', __('lang_v1.login_to_your') . ' ' . $siteName)
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-10 p-lg-15">
                    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20 w-100">
                        @yield('auth_content')
                    </div>

                    <div class="d-flex flex-stack px-lg-10 w-100">
                        <div class="text-muted fs-7">{{ date('Y') }} {{ $siteName }}</div>
                        <div class="d-flex fw-semibold text-primary fs-7 gap-5">
                            <a href="{{ route('login') }}">{{ __('business.sign_in') }}</a>
                            @if (\Route::has('register') && config('constants.allow_registration'))
                                <a href="{{ route('register') }}">{{ __('business.register_now') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>var hostUrl = "{{ $asset('') }}";</script>
    <script src="{{ $asset('plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ $asset('js/scripts.bundle.js') }}"></script>
    @yield('javascript')
</body>
</html>
