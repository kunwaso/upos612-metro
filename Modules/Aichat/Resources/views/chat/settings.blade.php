@extends('layouts.app')

@section('title', __('aichat::lang.ai_chat_settings'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('aichat::lang.ai_chat_settings') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('aichat::lang.ai_chat_api_keys') }}</div>
    </div>
    <a href="{{ route('aichat.chat.index') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('aichat::lang.ai_chat') }}
    </a>
</div>

@if(session('status') && is_array(session('status')))
    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show d-flex align-items-center mb-6" role="alert">
        <i class="ki-duotone fs-2hx me-4 {{ session('status.success') ? 'ki-check-circle text-success' : 'ki-information-5 text-danger' }}"><span class="path1"></span><span class="path2"></span></i>
        <div class="d-flex flex-column pe-7">
            <span>{{ session('status.msg') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-flush mb-6">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.ai_chat_api_keys') }}</h3>
    </div>
    <div class="card-body pt-5">
        @foreach($credentialStatuses as $status)
            <div class="{{ ! $loop->last ? 'mb-8 pb-8 border-bottom border-gray-200' : '' }}">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-gray-900 mb-0">{{ $status['label'] }}</h4>
                    <div class="d-flex gap-2">
                        <span class="badge {{ $status['has_user_key'] ? 'badge-light-success' : 'badge-light-warning' }}">
                            {{ __('aichat::lang.chat_user_key') }}: {{ $status['has_user_key'] ? __('aichat::lang.chat_key_set') : __('aichat::lang.chat_key_not_set') }}
                        </span>
                        <span class="badge {{ $status['has_business_key'] ? 'badge-light-success' : 'badge-light-warning' }}">
                            {{ __('aichat::lang.chat_business_key') }}: {{ $status['has_business_key'] ? __('aichat::lang.chat_key_set') : __('aichat::lang.chat_key_not_set') }}
                        </span>
                    </div>
                </div>

                <div class="row g-5">
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('aichat.chat.settings.credential.store') }}">
                            @csrf
                            <input type="hidden" name="provider" value="{{ $status['provider'] }}">
                            <input type="hidden" name="scope" value="user">
                            <label class="form-label">{{ __('aichat::lang.chat_user_key') }}</label>
                            <div class="input-group">
                                <input type="password" name="api_key" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.chat_enter_api_key') }}" required>
                                <button type="submit" class="btn btn-primary">{{ __('aichat::lang.chat_save_key') }}</button>
                            </div>
                        </form>
                    </div>

                    @if(auth()->user()->can('aichat.chat.settings'))
                        <div class="col-lg-6">
                            <form method="POST" action="{{ route('aichat.chat.settings.credential.store') }}">
                                @csrf
                                <input type="hidden" name="provider" value="{{ $status['provider'] }}">
                                <input type="hidden" name="scope" value="business">
                                <label class="form-label">{{ __('aichat::lang.chat_business_key') }}</label>
                                <div class="input-group">
                                    <input type="password" name="api_key" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.chat_enter_api_key') }}" required>
                                    <button type="submit" class="btn btn-primary">{{ __('aichat::lang.chat_save_key') }}</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

@if(auth()->user()->can('aichat.chat.settings'))
    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.chat_settings') }}</h3>
        </div>
        <div class="card-body pt-5">
            <form method="POST" action="{{ route('aichat.chat.settings.business.update') }}">
                @csrf
                @method('PATCH')
                <div class="row g-5">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_enabled') }}</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" {{ $businessSettings->enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_provider') }}</label>
                        <select class="form-select form-select-solid" name="default_provider">
                            @foreach(($aiChatConfig['enabled_providers'] ?? []) as $providerCode)
                                <option value="{{ $providerCode }}" {{ $providerCode === ($businessSettings->default_provider ?: $aiChatConfig['default_provider']) ? 'selected' : '' }}>
                                    {{ ucfirst($providerCode) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_model') }}</label>
                        <select class="form-select form-select-solid" name="default_model">
                            @foreach(($aiChatConfig['model_options'] ?? []) as $modelOption)
                                <option value="{{ $modelOption['model_id'] }}" {{ $modelOption['model_id'] === ($businessSettings->default_model ?: $aiChatConfig['default_model']) ? 'selected' : '' }}>
                                    {{ $modelOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('aichat::lang.chat_system_prompt') }}</label>
                        <textarea class="form-control form-control-solid" rows="3" name="system_prompt">{{ old('system_prompt', $businessSettings->system_prompt) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('aichat::lang.chat_reasoning_rules') }}</label>
                        <textarea class="form-control form-control-solid" rows="4" name="reasoning_rules" placeholder="{{ __('aichat::lang.chat_reasoning_rules_placeholder') }}">{{ old('reasoning_rules', $businessSettings->reasoning_rules) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_model_allowlist') }}</label>
                        <textarea class="form-control form-control-solid" rows="5" name="model_allowlist">{{ old('model_allowlist', $modelAllowlistJson) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_suggested_replies') }}</label>
                        <textarea class="form-control form-control-solid" rows="5" name="suggested_replies">{{ old('suggested_replies', $suggestedRepliesText) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_retention_days') }}</label>
                        <input type="number" name="retention_days" min="1" max="3650" class="form-control form-control-solid" value="{{ old('retention_days', $businessSettings->retention_days) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_pii_policy') }}</label>
                        <select class="form-select form-select-solid" name="pii_policy">
                            <option value="off" {{ $businessSettings->pii_policy === 'off' ? 'selected' : '' }}>{{ __('aichat::lang.chat_pii_off') }}</option>
                            <option value="warn" {{ $businessSettings->pii_policy === 'warn' ? 'selected' : '' }}>{{ __('aichat::lang.chat_pii_warn') }}</option>
                            <option value="block" {{ $businessSettings->pii_policy === 'block' ? 'selected' : '' }}>{{ __('aichat::lang.chat_pii_block') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_idle_timeout_minutes') }}</label>
                        <input type="number" name="idle_timeout_minutes" min="1" max="720" class="form-control form-control-solid" value="{{ old('idle_timeout_minutes', $businessSettings->idle_timeout_minutes) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_share_ttl_hours') }}</label>
                        <input type="number" name="share_ttl_hours" min="1" max="8760" class="form-control form-control-solid" value="{{ old('share_ttl_hours', $businessSettings->share_ttl_hours) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('aichat::lang.chat_moderation_enabled') }}</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="moderation_enabled" value="1" {{ $businessSettings->moderation_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('aichat::lang.chat_moderation_terms') }}</label>
                        <textarea class="form-control form-control-solid" rows="3" name="moderation_terms">{{ old('moderation_terms', $moderationTermsText) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-8">
                    <button type="submit" class="btn btn-primary">{{ __('aichat::lang.chat_save_settings') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.telegram_settings_title') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="mb-8 pb-8 border-bottom border-gray-200">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-5">
                    <h4 class="fw-bold text-gray-900 mb-0">{{ __('aichat::lang.telegram_verify_bot_title') }}</h4>
                    @if($telegramBot)
                        <span class="badge badge-light-success">{{ __('aichat::lang.telegram_bot_connected') }}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('aichat.chat.settings.telegram.store') }}">
                    @csrf
                    <div class="row g-4 align-items-end">
                        <div class="col-lg-8">
                            <label class="form-label" for="telegram_bot_token">{{ __('aichat::lang.telegram_bot_token_label') }}</label>
                            <input type="password" name="bot_token" id="telegram_bot_token" class="form-control form-control-solid {{ $errors->has('bot_token') ? 'is-invalid' : '' }}" placeholder="{{ __('aichat::lang.telegram_bot_token_placeholder') }}" required>
                            @error('bot_token')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="text-muted fs-7 mt-2">{{ __('aichat::lang.telegram_bot_token_help') }}</div>
                        </div>
                        <div class="col-lg-4 d-grid">
                            <button type="submit" class="btn btn-primary">{{ __('aichat::lang.telegram_save_verify_button') }}</button>
                        </div>
                    </div>
                </form>

                @if($telegramBot)
                    <div class="row g-4 mt-4">
                        <div class="col-lg-8">
                            <label class="form-label">{{ __('aichat::lang.telegram_webhook_url_label') }}</label>
                            <input type="text" class="form-control form-control-solid" value="{{ $telegramWebhookUrl }}" readonly>
                        </div>
                        <div class="col-lg-4 d-grid">
                            <form method="POST" action="{{ route('aichat.chat.settings.telegram.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger">{{ __('aichat::lang.telegram_disconnect_button') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mb-8 pb-8 border-bottom border-gray-200">
                <h4 class="fw-bold text-gray-900 mb-4">{{ __('aichat::lang.telegram_allowed_users_title') }}</h4>
                <form method="POST" action="{{ route('aichat.chat.settings.telegram.allowed-users.update') }}">
                    @csrf
                    @method('PATCH')
                    @php
                        $selectedTelegramUserIds = collect(old('user_ids', $telegramAllowedUserIds ?? []))
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    @endphp
                    <div class="row g-4">
                        <div class="col-lg-10">
                            <label class="form-label d-block mb-3">{{ __('aichat::lang.telegram_allowed_users_label') }}</label>
                            @if(collect($businessUsersForDropdown)->isEmpty())
                                <div class="border border-dashed border-gray-300 rounded p-4 text-muted fs-7">
                                    {{ __('aichat::lang.telegram_business_users_empty') }}
                                </div>
                            @else
                                <div class="border border-gray-300 rounded p-4 d-flex flex-column gap-3" style="max-height: 320px; overflow-y: auto;">
                                    @foreach($businessUsersForDropdown as $businessUser)
                                        @php
                                            $businessUserDisplayName = trim(($businessUser->surname ? $businessUser->surname . ' ' : '') . ($businessUser->first_name ?? '') . ' ' . ($businessUser->last_name ?? ''))
                                                ?: ($businessUser->username ?? ('#' . $businessUser->id));
                                        @endphp
                                        <label class="form-check form-check-custom form-check-solid align-items-start">
                                            <input
                                                class="form-check-input mt-1"
                                                type="checkbox"
                                                name="user_ids[]"
                                                value="{{ (int) $businessUser->id }}"
                                                {{ in_array((int) $businessUser->id, $selectedTelegramUserIds, true) ? 'checked' : '' }}
                                            >
                                            <span class="form-check-label d-flex flex-column ms-3">
                                                <span class="fw-semibold text-gray-900">{{ $businessUserDisplayName }}</span>
                                                <span class="text-muted fs-7">{{ $businessUser->username ? '@' . $businessUser->username : '#' . (int) $businessUser->id }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            <div class="text-muted fs-7 mt-2">{{ __('aichat::lang.telegram_allowed_users_help') }}</div>
                        </div>
                        <div class="col-lg-2 d-grid align-self-start">
                            <button type="submit" class="btn btn-primary">{{ __('aichat::lang.telegram_allowed_users_save') }}</button>
                        </div>
                    </div>
                </form>

                <div class="mt-5 d-flex flex-column gap-3">
                    @if(($telegramAllowedUsers ?? collect())->isEmpty())
                        <div class="text-muted fs-7">{{ __('aichat::lang.telegram_allowed_users_empty') }}</div>
                    @else
                        @if($telegramBot)
                            <div class="alert alert-primary d-flex flex-column gap-2 mb-0">
                                <div class="fw-semibold text-gray-900">{{ __('aichat::lang.telegram_connect_title') }}</div>
                                <div class="text-muted fs-7">{{ __('aichat::lang.telegram_allowed_users_connect_help') }}</div>
                            </div>
                        @endif
                        @foreach($telegramAllowedUsers as $allowedUser)
                            <div class="d-flex align-items-center justify-content-between border border-gray-200 rounded p-3">
                                <div>
                                    <div class="fw-semibold text-gray-800">
                                        {{ trim((($allowedUser->user->surname ?? '') ? ($allowedUser->user->surname . ' ') : '') . ($allowedUser->user->first_name ?? '') . ' ' . ($allowedUser->user->last_name ?? '')) ?: ($allowedUser->user->username ?? ('#' . $allowedUser->user_id)) }}
                                    </div>
                                    @if($telegramBot && !empty($telegramAllowedUserLinkCodes[(int) $allowedUser->user_id]))
                                        <div class="text-muted fs-7 mt-2">{{ __('aichat::lang.telegram_connect_instruction', ['code' => $telegramAllowedUserLinkCodes[(int) $allowedUser->user_id]]) }}</div>
                                        <div class="mt-1"><code>/start {{ $telegramAllowedUserLinkCodes[(int) $allowedUser->user_id] }}</code></div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <span class="badge badge-light-primary">#{{ (int) $allowedUser->user_id }}</span>
                                    @if($telegramBot)
                                        <form method="POST" action="{{ route('aichat.chat.settings.telegram.allowed-users.regenerate-code', ['user_id' => (int) $allowedUser->user_id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-light-primary btn-sm">{{ __('aichat::lang.telegram_link_code_regenerate_button') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mb-8 pb-8 border-bottom border-gray-200">
                <h4 class="fw-bold text-gray-900 mb-4">{{ __('aichat::lang.telegram_allowed_groups_title') }}</h4>
                <div class="text-muted fs-7 mb-4">{{ __('aichat::lang.telegram_allowed_groups_help') }}</div>

                <form method="POST" action="{{ route('aichat.chat.settings.telegram.allowed-groups.store') }}" class="mb-5">
                    @csrf
                    <div class="row g-4 align-items-end">
                        <div class="col-lg-5">
                            <label class="form-label">{{ __('aichat::lang.telegram_group_chat_id_label') }}</label>
                            <input type="text" name="telegram_chat_id" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.telegram_group_chat_id_placeholder') }}" required>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label">{{ __('aichat::lang.telegram_group_title_label') }}</label>
                            <input type="text" name="title" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.telegram_group_title_placeholder') }}">
                        </div>
                        <div class="col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary">{{ __('aichat::lang.telegram_group_add_button') }}</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex flex-column gap-3">
                    @if(empty($telegramAllowedGroups))
                        <div class="text-muted fs-7">{{ __('aichat::lang.telegram_allowed_groups_empty') }}</div>
                    @else
                        @foreach($telegramAllowedGroups as $allowedGroup)
                            <div class="d-flex align-items-center justify-content-between border border-gray-200 rounded p-3">
                                <div>
                                    <div class="fw-semibold text-gray-800">{{ $allowedGroup['title'] ?: __('aichat::lang.telegram_group_default_title') }}</div>
                                    <div class="text-muted fs-7">{{ $allowedGroup['telegram_chat_id'] }}</div>
                                </div>
                                <form method="POST" action="{{ route('aichat.chat.settings.telegram.allowed-groups.destroy', ['telegram_chat_id' => $allowedGroup['telegram_chat_id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light-danger btn-sm">{{ __('aichat::lang.telegram_group_remove_button') }}</button>
                                </form>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.chat_profile_title') }}</h3>
        </div>
        <div class="card-body pt-5">
            <form method="POST" action="{{ route('aichat.chat.settings.profile.update') }}">
                @csrf
                @method('PATCH')
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_profile_display_name') }}</label>
                        <input
                            type="text"
                            name="display_name"
                            maxlength="120"
                            class="form-control form-control-solid"
                            value="{{ old('display_name', $userChatProfile->display_name) }}"
                            placeholder="{{ __('aichat::lang.chat_profile_display_name_placeholder') }}"
                        >
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_profile_timezone') }}</label>
                        <input
                            type="text"
                            name="timezone"
                            maxlength="64"
                            class="form-control form-control-solid"
                            value="{{ old('timezone', $userChatProfile->timezone) }}"
                            placeholder="{{ __('aichat::lang.chat_profile_timezone_placeholder') }}"
                        >
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_profile_concerns_topics') }}</label>
                        <textarea
                            name="concerns_topics"
                            rows="4"
                            class="form-control form-control-solid"
                            placeholder="{{ __('aichat::lang.chat_profile_concerns_topics_placeholder') }}"
                        >{{ old('concerns_topics', $userChatProfile->concerns_topics) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_profile_preferences') }}</label>
                        <textarea
                            name="preferences"
                            rows="4"
                            class="form-control form-control-solid"
                            placeholder="{{ __('aichat::lang.chat_profile_preferences_placeholder') }}"
                        >{{ old('preferences', $userChatProfile->preferences) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-8">
                    <button type="submit" class="btn btn-primary">{{ __('aichat::lang.chat_profile_save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header pt-7">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 mb-0">{{ __('aichat::lang.chat_memory_title') }}</h3>
            </div>
        </div>
        <div class="card-body pt-5">
            <p class="text-muted fw-semibold fs-7 mb-7">{{ __('aichat::lang.chat_memory_description') }}</p>
            <div class="alert alert-info d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-8">
                <div class="mb-4 mb-lg-0">
                    <div class="fw-bold text-gray-800">
                        {{ __('aichat::lang.chat_memory_container_name') }}:
                        {{ $persistentMemory->display_name ?: __('aichat::lang.chat_memory_default_display_name') }}
                    </div>
                    <div class="text-muted fs-7">
                        {{ __('aichat::lang.chat_memory_container_slug') }}:
                        <code>{{ $persistentMemory->slug }}</code>
                    </div>
                </div>
                @if($canManageAllMemories)
                    <a href="{{ route('aichat.chat.settings.memories.admin') }}" class="btn btn-light-primary btn-sm">
                        {{ __('aichat::lang.chat_memory_admin_open') }}
                    </a>
                @endif
            </div>

            <form method="POST" action="{{ route('aichat.chat.settings.memory.store') }}" class="mb-8 pb-8 border-bottom border-gray-200">
                @csrf
                <div class="row g-5 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('aichat::lang.chat_memory_key') }}</label>
                        <input type="text" name="memory_key" maxlength="150" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.chat_memory_key_placeholder') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('aichat::lang.chat_memory_value') }}</label>
                        <textarea name="memory_value" rows="2" class="form-control form-control-solid" placeholder="{{ __('aichat::lang.chat_memory_value_placeholder') }}" required></textarea>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('aichat::lang.chat_memory_add') }}</button>
                    </div>
                </div>
            </form>

            @if(($memoryFacts ?? collect())->isEmpty())
                <div class="text-muted fw-semibold fs-7">{{ __('aichat::lang.chat_memory_empty') }}</div>
            @else
                <div class="d-flex flex-column gap-5">
                    @foreach($memoryFacts as $memoryFact)
                        <div class="border border-gray-200 rounded p-5">
                            <div class="row g-5">
                                @if((int) ($memoryFact->user_id ?? 0) === (int) auth()->id())
                                    <div class="col-md-10">
                                        <form method="POST" action="{{ route('aichat.chat.settings.memory.update', ['memory' => $memoryFact->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row g-4 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('aichat::lang.chat_memory_key') }}</label>
                                                    <input type="text" name="memory_key" maxlength="150" class="form-control form-control-solid" value="{{ $memoryFact->memory_key }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('aichat::lang.chat_memory_value') }}</label>
                                                    <textarea name="memory_value" rows="2" class="form-control form-control-solid" required>{{ $memoryFact->memory_value }}</textarea>
                                                </div>
                                                <div class="col-md-2 d-grid">
                                                    <button type="submit" class="btn btn-light-primary">{{ __('aichat::lang.chat_memory_update') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-md-end">
                                        <form method="POST" action="{{ route('aichat.chat.settings.memory.destroy', ['memory' => $memoryFact->id]) }}" onsubmit="return confirm('{{ __('aichat::lang.chat_memory_delete_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light-danger">{{ __('aichat::lang.chat_memory_delete') }}</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="col-md-10">
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('aichat::lang.chat_memory_key') }}</label>
                                                <input type="text" class="form-control form-control-solid" value="{{ $memoryFact->memory_key }}" readonly>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">{{ __('aichat::lang.chat_memory_value') }}</label>
                                                <textarea rows="2" class="form-control form-control-solid" readonly>{{ $memoryFact->memory_value }}</textarea>
                                            </div>
                                        </div>
                                        <div class="text-muted fw-semibold fs-7 mt-3">{{ __('aichat::lang.chat_memory_legacy_read_only') }}</div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-md-end">
                                        @if($canManageAllMemories && $memoryFact->user_id === null)
                                            <form method="POST" action="{{ route('aichat.chat.settings.memory.destroy', ['memory' => $memoryFact->id]) }}" onsubmit="return confirm('{{ __('aichat::lang.chat_memory_delete_confirm') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light-danger">{{ __('aichat::lang.chat_memory_delete') }}</button>
                                            </form>
                                        @else
                                            <span class="badge badge-light-secondary">{{ __('aichat::lang.chat_memory_read_only') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

@if($telegramBot && !empty($telegramLinkCode))
    <div class="card card-flush mt-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('aichat::lang.telegram_connect_title') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="alert alert-primary d-flex flex-column gap-2 mb-0">
                <div class="text-muted">{{ __('aichat::lang.telegram_connect_instruction', ['code' => $telegramLinkCode]) }}</div>
                <div><code>/start {{ $telegramLinkCode }}</code></div>
            </div>
        </div>
    </div>
@endif
@endsection
