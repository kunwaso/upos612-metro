@extends('layouts.app')

@section('title', __('aichat::lang.ai_chat'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('aichat::lang.ai_chat') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('aichat::lang.ai_assistant') }}</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @can('aichat.chat.settings')
            <a href="{{ route('aichat.chat.settings') }}" class="btn btn-light-primary btn-sm">
                <i class="ki-duotone ki-setting-2 fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('aichat::lang.chat_settings') }}
            </a>
        @endcan
    </div>
</div>

<div class="row g-7" data-aichat-chat-container="page" data-chat-initial-conversation="{{ $initialConversationId }}">
    <div class="col-12 col-xl-4 col-xxl-3">
        <div class="card card-flush h-100">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.chat_conversations') }}</h3>
                <div class="card-toolbar">
                    <button class="btn btn-icon btn-light-primary w-35px h-35px" type="button" data-chat-new-conversation title="{{ __('aichat::lang.new_chat') }}">
                        <i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
            </div>
            <div class="card-body pt-5">
                <div class="scroll-y h-500px pe-4" data-chat-conversations-list></div>
                <div class="text-muted text-center py-10" data-chat-empty-state>{{ __('aichat::lang.chat_no_conversations') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8 col-xxl-9">
        <div class="card h-100">
            <div class="card-header" id="aichat_chat_main_header">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900 mb-0" data-chat-conversation-title>{{ __('aichat::lang.new_chat') }}</h3>
                </div>
                <div class="card-toolbar gap-2 flex-wrap justify-content-start justify-content-lg-end">
                    <select class="form-select form-select-solid form-select-sm w-110px" data-chat-provider-select>
                        @foreach(($aiChatConfig['enabled_providers'] ?? []) as $providerCode)
                            <option value="{{ $providerCode }}" {{ $providerCode === ($aiChatConfig['default_provider'] ?? '') ? 'selected' : '' }}>
                                {{ ucfirst($providerCode) }}
                            </option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-solid form-select-sm w-160px" data-chat-model-select>
                        @foreach(($aiChatConfig['model_options'] ?? []) as $modelOption)
                            <option value="{{ $modelOption['model_id'] }}"
                                    data-provider="{{ $modelOption['provider'] }}"
                                    {{ $modelOption['model_id'] === ($aiChatConfig['default_model'] ?? '') ? 'selected' : '' }}>
                                {{ $modelOption['model_id'] }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-light-primary btn-sm" type="button" data-chat-share>{{ __('aichat::lang.chat_share') }}</button>
                    <button class="btn btn-light btn-sm" type="button" data-chat-export data-format="markdown">{{ __('aichat::lang.chat_export_markdown') }}</button>
                    <button class="btn btn-light btn-sm" type="button" data-chat-export data-format="pdf">{{ __('aichat::lang.chat_export_pdf') }}</button>
                </div>
            </div>

            <div class="card-body" id="aichat_chat_main_body">
                <div class="scroll-y h-500px pe-5"
                     data-kt-element="messages"
                     data-kt-scroll="true"
                     data-kt-scroll-activate="true"
                     data-kt-scroll-height="auto"
                     data-kt-scroll-dependencies="#aichat_chat_main_header, #aichat_chat_main_footer"
                     data-kt-scroll-wrappers="#aichat_chat_main_body"
                     data-kt-scroll-offset="0px">
                    <div class="text-center text-muted py-10" data-chat-empty-state>{{ __('aichat::lang.chat_no_conversations') }}</div>

                    <div class="d-flex justify-content-end mb-10 d-none" data-kt-element="template-out">
                        <div class="d-flex flex-column align-items-end">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">Just now</span>
                                    <span class="fs-6 fw-bold text-gray-900 ms-1">{{ __('aichat::lang.you') ?? 'You' }}</span>
                                </div>
                                <div class="symbol symbol-35px symbol-circle">
                                    <span class="symbol-label bg-light-primary text-primary fw-bold">{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}</span>
                                </div>
                            </div>
                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-600px text-end" data-kt-element="message-text"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start mb-10 d-none" data-kt-element="template-in">
                        <div class="d-flex flex-column align-items-start">
                            <div class="d-flex align-items-center mb-2">
                                <div class="symbol symbol-35px symbol-circle">
                                    <span class="symbol-label bg-light-info text-info fw-bold">AI</span>
                                </div>
                                <div class="ms-3">
                                    <span class="fs-6 fw-bold text-gray-900 me-1">{{ __('aichat::lang.ai_assistant') }}</span>
                                    <span class="text-muted fs-7 mb-1">Just now</span>
                                </div>
                            </div>
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-600px text-start" data-kt-element="message-text"></div>
                            <div class="d-flex align-items-center gap-2 mt-3" data-chat-assistant-actions>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="copy">{{ __('aichat::lang.chat_action_copy') }}</button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="regenerate">{{ __('aichat::lang.chat_action_regenerate') }}</button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="feedback-up" title="{{ __('aichat::lang.chat_action_feedback_up') }}">
                                    <i class="ki-duotone ki-like fs-5"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="feedback-down" title="{{ __('aichat::lang.chat_action_feedback_down') }}">
                                    <i class="ki-duotone ki-dislike fs-5"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer pt-4" id="aichat_chat_main_footer">
                <input type="hidden" data-chat-active-conversation value="{{ $initialConversationId }}" />
                <textarea class="form-control form-control-flush mb-3" rows="1" data-kt-element="input" placeholder="{{ __('aichat::lang.type_message') }}"></textarea>
                <div class="d-flex flex-stack">
                    <div class="d-flex align-items-center me-2">
                        <span class="text-muted fs-8" data-chat-warning-inline></span>
                    </div>
                    <button class="btn btn-primary" type="button" data-kt-element="send">{{ __('aichat::lang.send_message') }}</button>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2" data-chat-suggested-replies></div>
            </div>
        </div>
    </div>
</div>
@endsection
