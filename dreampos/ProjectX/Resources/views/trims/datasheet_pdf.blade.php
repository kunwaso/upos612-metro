<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('projectx::lang.trim_datasheet') }}</title>
    <style>
        body {
            margin: 0;
            font-size: 12px;
            line-height: 1.45;
            color: #181c32;
        }
    </style>
</head>
<body>
    @include('projectx::trims._trim_datasheet_content_readonly', ['fds' => $fds ?? []])
</body>
</html>
