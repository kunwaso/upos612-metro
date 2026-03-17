@php
    $chatConfig = $config ?? null;
@endphp

@if(!empty($chatConfig) && !empty($chatConfig['enabled']))
    <div id="kt_drawer_chat"
         class="bg-body"
         data-kt-drawer="true"
         data-kt-drawer-name="chat"
         data-kt-drawer-activate="true"
         data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'300px', 'md': '500px'}"
         data-kt-drawer-direction="end"
         data-kt-drawer-toggle="#kt_drawer_chat_toggle"
         data-kt-drawer-close="#kt_drawer_chat_close"
         data-projectx-chat-container="drawer">
        @include('projectx::chat.partials.chat-card')
    </div>
@endif
