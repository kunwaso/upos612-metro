@extends('projectx::layouts.main')

@section('title', __('projectx::lang.ai_chat'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.ai_chat') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.ai_assistant') }}</div>
    </div>
    <a href="{{ route('projectx.chat.settings') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-setting-2 fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.chat_settings') }}
    </a>
</div>

<div class="d-flex flex-column flex-lg-row" data-projectx-chat-container="main">
    <div class="flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0 me-lg-7 me-xl-10">
        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.chat_conversations') }}</h3>
                <div class="card-toolbar">
                    <button class="btn btn-icon btn-color-success bg-body w-35px h-35px w-lg-40px h-lg-40px" type="button" data-chat-new-conversation title="{{ __('projectx::lang.new_chat') }}">
                        <i class="ki-duotone ki-add-files fs-2 fs-md-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </div>
            </div>
            <div class="card-body pt-5">
                <div class="scroll-y h-500px pe-4" data-chat-conversations-list>
                    @forelse($conversations as $conversation)
                        <div class="d-flex align-items-center justify-content-between py-3 border-bottom border-gray-200 cursor-pointer"
                             data-chat-conversation-item
                             data-conversation-id="{{ $conversation->id }}">
                            <div class="d-flex align-items-center flex-grow-1 min-w-0 me-3">
                                <div class="symbol symbol-35px symbol-circle flex-shrink-0">
                                    <span class="symbol-label bg-light-info text-info fw-bold">A</span>
                                </div>
                                <div class="ms-3 min-w-0 flex-grow-1">
                                    <div class="fw-bold text-gray-900 fs-6 text-truncate">{{ $conversation->title ?: __('projectx::lang.new_chat') }}</div>
                                    <div class="text-muted fs-7 text-truncate">
                                        {{ \Illuminate\Support\Str::words($conversation->last_message_preview ?: '-', 6, '...') }}
                                    </div>
                                    <div class="text-muted fs-8">
                                        @if($conversation->updated_at)
                                            {{ optional($conversation->updated_at)->format('M j, Y') }} ({{ optional($conversation->updated_at)->diffForHumans() }})
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @can('projectx.chat.edit')
                                <button type="button"
                                        class="btn btn-sm btn-icon btn-active-color-danger"
                                        data-chat-delete-conversation
                                        data-conversation-id="{{ $conversation->id }}"
                                        title="{{ __('projectx::lang.chat_delete_conversation') }}"
                                        aria-label="{{ __('projectx::lang.chat_delete_conversation') }}">
                                    <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </button>
                            @endcan
                        </div>
                    @empty
                        <div class="text-muted text-center py-10" data-chat-empty-state>{{ __('projectx::lang.chat_no_conversations') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="flex-lg-row-fluid">
        <div class="card" id="projectx_chat_main_messenger">
            <div class="card-header" id="projectx_chat_main_header">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900" data-chat-conversation-title>{{ $activeConversation?->title ?: __('projectx::lang.new_chat') }}</h3>
                </div>
                <div class="card-toolbar gap-2">
                    <select class="form-select form-select-solid form-select-sm w-110px" data-chat-provider-select>
                        @foreach(($aiChatConfig['enabled_providers'] ?? []) as $providerCode)
                            <option value="{{ $providerCode }}" {{ ($providerCode === ($aiChatConfig['default_provider'] ?? '')) ? 'selected' : '' }}>
                                {{ ucfirst($providerCode) }}
                            </option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-solid form-select-sm w-160px" data-chat-model-select>
                        @foreach(($aiChatConfig['model_options'] ?? []) as $modelOption)
                            <option value="{{ $modelOption['model_id'] }}"
                                    data-provider="{{ $modelOption['provider'] }}"
                                    {{ ($modelOption['model_id'] === ($aiChatConfig['default_model'] ?? '')) ? 'selected' : '' }}>
                                {{ $modelOption['model_id'] }}
                            </option>
                        @endforeach
                    </select>
                    <label class="form-check form-switch form-check-sm form-check-custom form-check-solid d-none mb-0" data-chat-fabric-toggle-wrap>
                        <input class="form-check-input" type="checkbox" value="1" data-chat-fabric-toggle />
                        <span class="form-check-label fs-8 text-gray-700">{{ __('projectx::lang.fabric_insight') }}</span>
                    </label>
                    <button class="btn btn-light-primary btn-sm" type="button" data-chat-share>
                        {{ __('projectx::lang.chat_share') }}
                    </button>
                    <button class="btn btn-light btn-sm" type="button" data-chat-export data-format="markdown">
                        {{ __('projectx::lang.chat_export_markdown') }}
                    </button>
                    <button class="btn btn-light btn-sm" type="button" data-chat-export data-format="pdf">
                        {{ __('projectx::lang.chat_export_pdf') }}
                    </button>
                </div>
            </div>

            <div class="card-body" id="projectx_chat_main_body">
                <div class="scroll-y h-500px pe-5"
                     data-kt-element="messages"
                     data-kt-scroll="true"
                     data-kt-scroll-activate="true"
                     data-kt-scroll-height="auto"
                     data-kt-scroll-dependencies="#projectx_chat_main_header, #projectx_chat_main_footer"
                     data-kt-scroll-wrappers="#projectx_chat_main_body"
                     data-kt-scroll-offset="0px">
                    @if($messages->isEmpty())
                        <div class="text-center text-muted py-10" data-chat-empty-state>{{ __('projectx::lang.chat_no_conversations') }}</div>
                    @endif

                    @foreach($messages as $message)
                        @php
                            $isUserMessage = ($message['role'] ?? '') === 'user';
                            $isAssistantMessage = ($message['role'] ?? '') === 'assistant';
                        @endphp
                        <div class="d-flex {{ $isUserMessage ? 'justify-content-end' : 'justify-content-start' }} mb-10"
                             data-chat-message-id="{{ (int) ($message['id'] ?? 0) }}"
                             data-chat-message-role="{{ $message['role'] ?? '' }}"
                             data-chat-feedback-value="{{ $message['feedback_value'] ?? '' }}"
                             data-chat-can-regenerate="{{ !empty($message['can_regenerate']) ? '1' : '0' }}">
                            <div class="d-flex flex-column {{ $isUserMessage ? 'align-items-end' : 'align-items-start' }}">
                                <div class="d-flex align-items-center mb-2">
                                    @if($isUserMessage)
                                        <div class="me-3">
                                            <span class="text-muted fs-7 mb-1">Just now</span>
                                            <span class="fs-6 fw-bold text-gray-900 ms-1">{{ __('projectx::lang.you') ?? 'You' }}</span>
                                        </div>
                                        <div class="symbol symbol-35px symbol-circle">
                                            <span class="symbol-label bg-light-primary text-primary fw-bold">{{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}</span>
                                        </div>
                                    @else
                                        <div class="symbol symbol-35px symbol-circle">
                                            <span class="symbol-label bg-light-info text-info fw-bold">AI</span>
                                        </div>
                                        <div class="ms-3">
                                            <span class="fs-6 fw-bold text-gray-900 me-1">{{ __('projectx::lang.ai_assistant') }}</span>
                                            <span class="text-muted fs-7 mb-1">{{ $message['created_at'] ? \Carbon\Carbon::parse($message['created_at'])->diffForHumans() : '' }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-5 rounded {{ $isUserMessage ? 'bg-light-primary text-end' : 'bg-light-info text-start' }} text-gray-900 fw-semibold mw-lg-600px" data-kt-element="message-text">
                                    @if($isAssistantMessage)
                                        {!! $message['content_html'] ?? '' !!}
                                    @else
                                        {{ $message['content'] ?? '' }}
                                    @endif
                                </div>
                                @if($isAssistantMessage)
                                    <div class="d-flex align-items-center gap-2 mt-3" data-chat-assistant-actions>
                                        <button type="button" class="btn btn-sm btn-light" data-chat-action="copy">{{ __('projectx::lang.chat_action_copy') }}</button>
                                        <button type="button" class="btn btn-sm btn-light" data-chat-action="regenerate">{{ __('projectx::lang.chat_action_regenerate') }}</button>
                                        <button type="button" class="btn btn-sm btn-light {{ ($message['feedback_value'] ?? '') === 'up' ? 'active' : '' }}" data-chat-action="feedback-up" title="{{ __('projectx::lang.chat_action_feedback_up') }}">
                                            <i class="ki-duotone ki-like fs-5"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light {{ ($message['feedback_value'] ?? '') === 'down' ? 'active' : '' }}" data-chat-action="feedback-down" title="{{ __('projectx::lang.chat_action_feedback_down') }}">
                                            <i class="ki-duotone ki-dislike fs-5"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end mb-10 d-none" data-kt-element="template-out">
                        <div class="d-flex flex-column align-items-end">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">Just now</span>
                                    <span class="fs-6 fw-bold text-gray-900 ms-1">{{ __('projectx::lang.you') ?? 'You' }}</span>
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
                                    <span class="fs-6 fw-bold text-gray-900 me-1">{{ __('projectx::lang.ai_assistant') }}</span>
                                    <span class="text-muted fs-7 mb-1">Just now</span>
                                </div>
                            </div>
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-600px text-start" data-kt-element="message-text"></div>
                            <div class="d-flex align-items-center gap-2 mt-3" data-chat-assistant-actions>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="copy">{{ __('projectx::lang.chat_action_copy') }}</button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="regenerate">{{ __('projectx::lang.chat_action_regenerate') }}</button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="feedback-up" title="{{ __('projectx::lang.chat_action_feedback_up') }}">
                                    <i class="ki-duotone ki-like fs-5"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light" data-chat-action="feedback-down" title="{{ __('projectx::lang.chat_action_feedback_down') }}">
                                    <i class="ki-duotone ki-dislike fs-5"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer pt-4" id="projectx_chat_main_footer">
                <input type="hidden" data-chat-active-conversation value="{{ $activeConversation?->id }}" />
                <textarea class="form-control form-control-flush mb-3"
                          rows="1"
                          data-kt-element="input"
                          placeholder="{{ __('projectx::lang.type_message') }}"></textarea>
                <div class="d-flex flex-stack">
                    <div class="d-flex align-items-center me-2">
                        <span class="text-muted fs-8" data-chat-warning-inline></span>
                    </div>
                    <button class="btn btn-primary" type="button" data-kt-element="send">{{ __('projectx::lang.send_message') }}</button>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2" data-chat-suggested-replies></div>
            </div>
        </div>
    </div>
</div>
@endsection
