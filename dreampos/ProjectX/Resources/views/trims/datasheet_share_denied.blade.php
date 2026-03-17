<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('projectx::lang.share_access_denied') }}</title>
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-light">
    <div class="d-flex flex-column flex-root min-vh-100 align-items-center justify-content-center p-4">
        <div class="card card-flush w-100" style="max-width: 520px;">
            <div class="card-body p-12 text-center">
                <h3 class="fw-bold mb-4">{{ __('projectx::lang.share_access_denied') }}</h3>
                <p class="text-muted mb-0">{{ $message ?? __('projectx::lang.share_link_disabled') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
