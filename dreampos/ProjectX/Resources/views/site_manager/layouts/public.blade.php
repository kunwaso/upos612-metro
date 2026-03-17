<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title', $siteName)</title>
    <meta charset="utf-8" />
    <meta name="description" content="{{ $siteName }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="{{ $siteName }}" />
    <link rel="shortcut icon" href="{{ asset('modules/projectx/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @stack('head')
</head>
<body id="kt_body" data-bs-spy="scroll" data-bs-target="#kt_landing_menu" class="bg-body position-relative">
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
        <div class="mb-0">
            <div class="landing-dark-bg">
                <div class="landing-header" data-kt-sticky="true" data-kt-sticky-name="landing-header" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <div class="container">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-equal">
                                <button class="btn btn-icon btn-active-color-primary me-3 d-flex d-lg-none" id="kt_landing_menu_toggle" type="button" aria-label="Toggle menu">
                                    <i class="ki-duotone ki-abstract-14 fs-2hx">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </button>
                                <a href="{{ url('/') }}">
                                    <img alt="Logo" src="{{ $logoUrl }}" class="logo-default h-25px h-lg-30px" />
                                    <img alt="Logo" src="{{ asset('modules/projectx/media/logos/landing-dark.svg') }}" class="logo-sticky h-20px h-lg-25px" />
                                </a>
                            </div>
                            <div class="d-lg-block" id="kt_header_nav_wrapper">
                                <div class="d-lg-block p-5 p-lg-0" data-kt-drawer="true" data-kt-drawer-name="landing-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="200px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_landing_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="prepend" data-kt-swapper-parent="{default: '#kt_body', lg: '#kt_header_nav_wrapper'}">
                                    <div class="menu menu-column flex-nowrap menu-rounded menu-lg-row menu-title-gray-600 menu-state-title-primary nav nav-flush fs-5 fw-semibold" id="kt_landing_menu">
                                        <div class="menu-item">
                                            <a class="menu-link nav-link py-3 px-4 px-xxl-6" href="{{ url('/') }}" data-kt-drawer-dismiss="true">Home</a>
                                        </div>
                                        @foreach ($navItems ?? [] as $navItem)
                                            <div class="menu-item">
                                                <a class="menu-link nav-link py-3 px-4 px-xxl-6" href="{{ $navItem['url'] }}" data-kt-drawer-dismiss="true">{{ $navItem['label'] }}</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="flex-equal text-end ms-1">
                                <a href="{{ $ctaUrl }}" class="btn btn-success">{{ $ctaLabel }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main>
            @yield('content')
        </main>

        <div class="mb-0">
            <div class="landing-dark-bg pt-20">
                <div class="landing-dark-separator"></div>
                <div class="container">
                    <div class="d-flex flex-column flex-md-row flex-stack py-7 py-lg-10">
                        <div class="d-flex align-items-center order-2 order-md-1">
                            <a href="{{ url('/') }}">
                                <img alt="Logo" src="{{ $logoUrl }}" class="h-15px h-md-20px" />
                            </a>
                            <span class="mx-5 fs-6 fw-semibold text-gray-600 pt-1">{{ $footerCopyright }}</span>
                        </div>
                        <ul class="menu menu-gray-600 menu-hover-primary fw-semibold fs-6 fs-md-5 order-1 mb-5 mb-md-0">
                            @foreach ($navItems ?? [] as $navItem)
                                <li class="menu-item{{ $loop->last ? '' : ' mx-5' }}">
                                    <a href="{{ $navItem['url'] }}" class="menu-link px-2">{{ $navItem['label'] }}</a>
                                </li>
                            @endforeach
                            <li class="menu-item{{ empty($navItems) ? '' : ' mx-5' }}">
                                <a href="{{ $ctaUrl }}" class="menu-link px-2">{{ $ctaLabel }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-duotone ki-arrow-up">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </div>
    </div>

    <script>var hostUrl = "{{ asset('modules/projectx') }}/";</script>
    <script src="{{ asset('modules/projectx/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('modules/projectx/js/scripts.bundle.js') }}"></script>
    @stack('scripts')
</body>
</html>
