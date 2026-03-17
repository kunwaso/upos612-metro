<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>
        @if (trim($__env->yieldContent('title')))
            @yield('title') - {{ config('app.name', 'POS') }}
        @else
            {{ config('app.name', 'POS') }}
        @endif
    </title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
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
            body {
                background-image: url('{{ asset('assets/media/auth/bg4.jpg') }}');
            }

            [data-bs-theme="dark"] body {
                background-image: url('{{ asset('assets/media/auth/bg4-dark.jpg') }}');
            }
        </style>

        <div class="d-flex flex-column flex-column-fluid flex-lg-row">
            <div class="d-flex flex-center w-lg-50 pt-10 pt-lg-0 px-10 px-lg-20">
                <div class="d-flex flex-center flex-lg-start flex-column text-center text-lg-start">
                    <a href="{{ url('/') }}" class="mb-8">
                        <img alt="{{ config('app.name', 'POS') }}" src="{{ asset('assets/media/logos/custom-3.svg') }}" class="h-55px" />
                    </a>

                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-5 mb-8">
                        @if (config('constants.SHOW_REPAIR_STATUS_LOGIN_SCREEN') && Route::has('repair-status'))
                            <a href="{{ action([\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index']) }}"
                                class="link-light fw-semibold fs-6">
                                @lang('repair::lang.repair_status')
                            </a>
                        @endif

                        @if (Route::has('member_scanner'))
                            <a href="{{ action([\Modules\Gym\Http\Controllers\MemberController::class, 'member_scanner']) }}"
                                class="link-light fw-semibold fs-6">
                                @lang('gym::lang.gym_member_profile')
                            </a>
                        @endif
                    </div>

                    <h1 class="text-white fw-bold fs-2hx fs-lg-3x mb-5">
                        @yield('aside_title', config('app.name', 'POS'))
                    </h1>

                    <div class="text-white opacity-75 fs-5 fw-semibold mw-lg-450px">
                        @yield('aside_subtitle', __('lang_v1.login_to_your') . ' ' . config('app.name', 'POS'))
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-6 p-md-10 p-lg-20">
                <div class="d-flex flex-column w-100 mw-md-600px">
                    <div class="d-flex flex-stack flex-wrap justify-content-end gap-4 mb-8">
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            @if (Route::has('pricing') && config('app.env') !== 'demo' && ! request()->routeIs('pricing'))
                                <a href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}"
                                    class="link-light fw-semibold fs-6">
                                    @lang('superadmin::lang.pricing')
                                </a>
                            @endif

                            @if (! request()->routeIs('login'))
                                <a href="{{ route('login', ['lang' => request()->query('lang')]) }}" class="link-light fw-semibold fs-6">
                                    {{ __('business.sign_in') }}
                                </a>
                            @endif

                            @if (config('constants.allow_registration') && ! request()->routeIs('business.getRegister'))
                                <a href="{{ route('business.getRegister', ['lang' => request()->query('lang')]) }}"
                                    class="btn btn-sm btn-light-primary">
                                    {{ __('business.register') }}
                                </a>
                            @endif

                            @if (! empty(config('constants.langs')))
                                <div class="me-0">
                                    <button class="btn btn-sm btn-flex btn-color-white btn-active-light-primary rotate"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                        <span class="me-1">
                                            {{ config('constants.langs.' . request()->query('lang', config('app.locale')) . '.full_name', strtoupper(config('app.locale'))) }}
                                        </span>
                                        <i class="ki-duotone ki-down fs-5 text-white m-0"></i>
                                    </button>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-225px py-4 fs-7"
                                        data-kt-menu="true">
                                        @foreach (config('constants.langs', []) as $key => $language)
                                            <div class="menu-item px-3">
                                                <a href="{{ request()->fullUrlWithQuery(['lang' => $key]) }}"
                                                    class="menu-link px-5 {{ request()->query('lang', config('app.locale')) === $key ? 'active' : '' }}">
                                                    {{ $language['full_name'] }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 shadow-sm p-10 p-lg-15">
                        <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20 w-100">
                            @yield('auth_content')
                        </div>

                        <div class="d-flex flex-stack flex-wrap gap-4 px-lg-10 w-100">
                            <div class="text-muted fs-7">{{ date('Y') }} {{ config('app.name', 'POS') }}</div>
                            <div class="d-flex fw-semibold text-primary fs-7 gap-5">
                                @if (! request()->routeIs('login'))
                                    <a href="{{ route('login', ['lang' => request()->query('lang')]) }}">{{ __('business.sign_in') }}</a>
                                @endif
                                @if (config('constants.allow_registration') && ! request()->routeIs('business.getRegister'))
                                    <a href="{{ route('business.getRegister', ['lang' => request()->query('lang')]) }}">
                                        {{ __('business.register_now') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var hostUrl = "{{ asset('assets/') }}/";
    </script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    @stack('scripts')
    @yield('javascript')
</body>
</html>
