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
     data-aichat-chat-container="drawer">
    @include('aichat::chat.partials.chat-card', ['chatConfig' => $config])
</div>
