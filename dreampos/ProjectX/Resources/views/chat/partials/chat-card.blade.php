<div class="card w-100 border-0 rounded-0 h-100" id="kt_drawer_chat_messenger">
    <div class="card-header pe-5" id="kt_drawer_chat_messenger_header">
        <div class="card-title">
            <div class="d-flex justify-content-center flex-column me-3">
                <span class="fs-4 fw-bold text-gray-900 mb-1 lh-1">{{ __('projectx::lang.ai_assistant') }}</span>
                <span class="fs-7 fw-semibold text-muted">{{ __('projectx::lang.ai_chat') }}</span>
            </div>
        </div>
        <div class="card-toolbar d-flex align-items-center gap-2 flex-wrap">
            <select class="form-select form-select-solid form-select-sm w-125px" data-chat-provider-select>
                @foreach(($chatConfig['enabled_providers'] ?? []) as $providerCode)
                    <option value="{{ $providerCode }}" {{ ($providerCode === ($chatConfig['default_provider'] ?? '')) ? 'selected' : '' }}>
                        {{ ucfirst($providerCode) }}
                    </option>
                @endforeach
            </select>
            <select class="form-select form-select-solid form-select-sm w-150px" data-chat-model-select>
                @foreach(($chatConfig['model_options'] ?? []) as $modelOption)
                    <option value="{{ $modelOption['model_id'] }}"
                            data-provider="{{ $modelOption['provider'] }}"
                            {{ ($modelOption['model_id'] === ($chatConfig['default_model'] ?? '')) ? 'selected' : '' }}>
                        {{ $modelOption['model_id'] }}
                    </option>
                @endforeach
            </select>
            <div class="me-0">
                <button type="button"
                        class="btn btn-sm btn-icon btn-active-color-primary"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-dots-square fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </button>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-250px py-3" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">{{ __('projectx::lang.ai_chat') }}</div>
                    </div>
                    <div class="menu-item px-3">
                        <button type="button" class="menu-link px-3 w-100 border-0 bg-transparent text-start" data-chat-new-conversation>
                            {{ __('projectx::lang.chat_start_new_chat') }}
                        </button>
                    </div>
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">{{ __('projectx::lang.chat_conversations') }}</div>
                        <div class="scroll-y mh-200px px-3 pb-2" data-chat-conversations-list></div>
                    </div>
                    <div class="menu-item px-3 my-1">
                        <div class="separator"></div>
                    </div>
                    <div class="menu-item px-3">
                        <a href="{{ route('projectx.chat.settings') }}" class="menu-link px-3">{{ __('projectx::lang.chat_settings') }}</a>
                    </div>
                    <div class="menu-item px-3">
                        <div class="menu-content px-3 py-2">
                            <label class="form-check form-switch form-check-sm form-check-custom form-check-solid d-none mb-0" data-chat-fabric-toggle-wrap>
                                <span class="form-check-label fs-8 text-gray-700 me-2">{{ __('projectx::lang.fabric_insight') }}</span>
                                <input class="form-check-input" type="checkbox" value="1" data-chat-fabric-toggle />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn btn-sm btn-icon btn-active-color-primary" id="kt_drawer_chat_close">
                <i class="ki-duotone ki-cross-square fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
        </div>
    </div>

    <div class="card-body" id="kt_drawer_chat_messenger_body">
        <div class="scroll-y me-n5 pe-5"
             data-kt-element="messages"
             data-kt-scroll="true"
             data-kt-scroll-activate="true"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_drawer_chat_messenger_header, #kt_drawer_chat_messenger_footer"
             data-kt-scroll-wrappers="#kt_drawer_chat_messenger_body"
             data-kt-scroll-offset="0px">
            <div class="text-center text-muted py-10" data-chat-empty-state>
                {{ __('projectx::lang.chat_no_conversations') }}
            </div>

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
                    <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text"></div>
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
                    <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text"></div>
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

    <div class="card-footer pt-4" id="kt_drawer_chat_messenger_footer">
        <input type="hidden" data-chat-active-conversation value="" />
        <textarea class="form-control form-control-flush mb-3"
                  rows="1"
                  data-kt-element="input"
                  placeholder="{{ __('projectx::lang.type_message') }}"></textarea>
        <div class="d-flex flex-stack">
            <div class="d-flex align-items-center me-2">
                <span class="text-muted fs-8 me-3" data-chat-warning-inline></span>
                <button class="btn btn-sm btn-icon btn-active-light-primary me-1"
                        type="button"
                        data-bs-toggle="tooltip"
                        title="{{ __('projectx::lang.coming_soon') }}">
                    <i class="ki-duotone ki-paper-clip fs-3"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-active-light-primary me-1"
                        type="button"
                        data-bs-toggle="tooltip"
                        title="{{ __('projectx::lang.coming_soon') }}">
                    <i class="ki-duotone ki-cloud-add fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>
            <button class="btn btn-primary" type="button" data-kt-element="send">{{ __('projectx::lang.send_message') }}</button>
        </div>
    </div>
</div>
