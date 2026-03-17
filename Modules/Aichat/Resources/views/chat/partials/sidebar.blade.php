@if(!empty($config) && !empty($config['enabled']))
    <div class="app-sidebar-secondary d-flex flex-column flex-lg-row">
        <div id="kt_drawer_chat" class="bg-body d-flex flex-column flex-row-fluid h-100" data-aichat-chat-container="drawer">
            @include('aichat::chat.partials.chat-card', ['chatConfig' => $config])
        </div>
    </div>
@endif
