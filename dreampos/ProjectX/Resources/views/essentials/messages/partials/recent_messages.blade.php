@foreach($messages as $message)
    @include('projectx::essentials.messages.partials._message_div', ['message' => $message])
@endforeach
