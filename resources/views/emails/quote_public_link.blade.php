<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('product.quote_email_title') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>{{ __('product.quote_email_heading') }}</h2>
    <p>{{ __('product.quote_email_intro', ['quote' => $quoteNo]) }}</p>
    <p>
        <a href="{{ $publicUrl }}">{{ $publicUrl }}</a>
    </p>
    <p>{{ __('product.quote_email_footer') }}</p>
</body>
</html>


