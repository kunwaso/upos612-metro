<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <title>{{ $conversation->title ?: __('aichat::lang.new_chat') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        .message { margin-bottom: 16px; page-break-inside: avoid; }
        .role { font-weight: bold; margin-bottom: 4px; }
        .content { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>{{ $conversation->title ?: __('aichat::lang.new_chat') }}</h1>
    <div class="meta">
        Conversation ID: {{ $conversation->id }}<br>
        Updated At: {{ optional($conversation->updated_at)->toDateTimeString() }}
    </div>

    @foreach($messages as $message)
        <div class="message">
            <div class="role">
                {{ ucfirst($message->role) }}
                <span style="font-weight: normal; color: #6b7280; margin-left: 8px;">{{ optional($message->created_at)->toDateTimeString() }}</span>
            </div>
            <div class="content">{{ $message->content }}</div>
        </div>
    @endforeach
</body>
</html>


