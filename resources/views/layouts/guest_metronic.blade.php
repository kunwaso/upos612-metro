<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php($asset_v = $asset_v ?? config('app.asset_version', '1'))
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vendor.css?v=' . $asset_v) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css?v=' . $asset_v) }}">
    @stack('styles')
</head>
<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root min-vh-100">
        <div class="d-flex flex-column flex-column-fluid">
            <div class="container-xxl py-10">
                @yield('content')
            </div>
        </div>
    </div>

    <script>var hostUrl = "/assets/";</script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
    @yield('javascript')
    @stack('scripts')
</body>
</html>
