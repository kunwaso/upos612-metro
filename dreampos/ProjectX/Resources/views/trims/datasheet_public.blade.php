<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('projectx::lang.trim_datasheet_public_title') }}</title>
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-light">
    <div class="container py-8">
        <div class="card card-flush">
            <div class="card-header align-items-center">
                <div class="card-title fw-bold">{{ __('projectx::lang.trim_datasheet_public_title') }}</div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary btn-sm" onclick="window.print()">{{ __('projectx::lang.print') }}</button>
                </div>
            </div>
            <div class="card-body">
                @include('projectx::trims._trim_datasheet_content_readonly', ['fds' => $fds ?? []])
            </div>
        </div>
    </div>
</body>
</html>
