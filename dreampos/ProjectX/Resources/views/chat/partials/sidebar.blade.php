@php
    $chatConfig = $config ?? null;
@endphp

@if(!empty($chatConfig) && !empty($chatConfig['enabled']))
    <div class="app-sidebar-secondary d-flex flex-column flex-lg-row">
        <div id="kt_drawer_chat" class="bg-body d-flex flex-column flex-row-fluid h-100" data-projectx-chat-container="drawer">
            @include('projectx::chat.partials.chat-card')
        </div>
    </div>
@endif
