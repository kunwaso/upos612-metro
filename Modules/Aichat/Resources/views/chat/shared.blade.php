<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('aichat::lang.chat_shared_title') }}</title>
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-body">
    <div class="container py-10">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">{{ $chatConversation->title ?: __('aichat::lang.new_chat') }}</h3>
                <div class="card-toolbar text-muted fs-7">{{ __('aichat::lang.chat_shared_read_only') }}</div>
            </div>
            <div class="card-body">
                @foreach($messages as $message)
                    <div class="mb-7">
                        <div class="fw-bold text-gray-900 mb-2">
                            {{ ucfirst($message->role) }}
                            <span class="text-muted fw-normal fs-8 ms-2">{{ optional($message->created_at)->toDayDateTimeString() }}</span>
                        </div>
                        <div class="p-5 rounded bg-light-{{ $message->role === 'user' ? 'primary' : 'info' }} text-gray-900 fw-semibold">
                            {{ $message->content }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>


