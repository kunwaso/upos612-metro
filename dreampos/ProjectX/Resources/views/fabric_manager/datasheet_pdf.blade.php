<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('projectx::lang.fds_pdf_title') }}</title>
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
    @include('projectx::fabric_manager._datasheet_content', ['fds' => $fds])
</body>
</html>
