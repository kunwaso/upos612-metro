@extends('projectx::layouts.main')

@section('title', __('projectx::lang.ai_chat_settings'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.ai_chat_settings') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.ai_chat_api_keys') }}</div>
    </div>
    <a href="{{ route('projectx.chat.index') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.ai_chat') }}
    </a>
</div>

<div class="card card-flush mb-6">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.ai_chat_api_keys') }}</h3>
    </div>
    <div class="card-body pt-5">
        @foreach($credentialStatuses as $status)
            <div class="{{ ! $loop->last ? 'mb-8 pb-8 border-bottom border-gray-200' : '' }}">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-gray-900 mb-0">{{ $status['label'] }}</h4>
                    <div class="d-flex gap-2">
                        <span class="badge {{ $status['has_user_key'] ? 'badge-light-success' : 'badge-light-warning' }}">
                            {{ __('projectx::lang.chat_user_key') }}: {{ $status['has_user_key'] ? __('projectx::lang.chat_key_set') : __('projectx::lang.chat_key_not_set') }}
                        </span>
                        <span class="badge {{ $status['has_business_key'] ? 'badge-light-success' : 'badge-light-warning' }}">
                            {{ __('projectx::lang.chat_business_key') }}: {{ $status['has_business_key'] ? __('projectx::lang.chat_key_set') : __('projectx::lang.chat_key_not_set') }}
                        </span>
                    </div>
                </div>

                <div class="row g-5">
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('projectx.chat.settings.credential.store') }}">
                            @csrf
                            <input type="hidden" name="provider" value="{{ $status['provider'] }}">
                            <input type="hidden" name="scope" value="user">
                            <label class="form-label">{{ __('projectx::lang.chat_user_key') }}</label>
                            <div class="input-group">
                                <input type="password" name="api_key" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.chat_enter_api_key') }}" required>
                                <button type="submit" class="btn btn-primary">{{ __('projectx::lang.chat_save_key') }}</button>
                            </div>
                        </form>
                    </div>

                    @if(auth()->user()->can('projectx.chat.settings'))
                        <div class="col-lg-6">
                            <form method="POST" action="{{ route('projectx.chat.settings.credential.store') }}">
                                @csrf
                                <input type="hidden" name="provider" value="{{ $status['provider'] }}">
                                <input type="hidden" name="scope" value="business">
                                <label class="form-label">{{ __('projectx::lang.chat_business_key') }}</label>
                                <div class="input-group">
                                    <input type="password" name="api_key" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.chat_enter_api_key') }}" required>
                                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.chat_save_key') }}</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

@if(auth()->user()->can('projectx.chat.settings'))
    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.chat_settings') }}</h3>
        </div>
        <div class="card-body pt-5">
            <form method="POST" action="{{ route('projectx.chat.settings.business.update') }}">
                @csrf
                @method('PATCH')
                <div class="row g-5">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_enabled') }}</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" {{ $businessSettings->enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_fabric_insight_enabled') }}</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="fabric_insight_enabled" value="1" {{ $businessSettings->fabric_insight_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_provider') }}</label>
                        <select class="form-select form-select-solid" name="default_provider">
                            @foreach(($aiChatConfig['enabled_providers'] ?? []) as $providerCode)
                                <option value="{{ $providerCode }}" {{ $providerCode === ($businessSettings->default_provider ?: $aiChatConfig['default_provider']) ? 'selected' : '' }}>
                                    {{ ucfirst($providerCode) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_model') }}</label>
                        <select class="form-select form-select-solid" name="default_model">
                            @foreach(($aiChatConfig['model_options'] ?? []) as $modelOption)
                                <option value="{{ $modelOption['model_id'] }}" {{ $modelOption['model_id'] === ($businessSettings->default_model ?: $aiChatConfig['default_model']) ? 'selected' : '' }}>
                                    {{ $modelOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('projectx::lang.chat_system_prompt') }}</label>
                        <textarea class="form-control form-control-solid" rows="3" name="system_prompt">{{ old('system_prompt', $businessSettings->system_prompt) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('projectx::lang.chat_model_allowlist') }}</label>
                        <textarea class="form-control form-control-solid" rows="5" name="model_allowlist">{{ old('model_allowlist', $businessSettings->model_allowlist ? json_encode($businessSettings->model_allowlist, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('projectx::lang.chat_suggested_replies') }}</label>
                        <textarea class="form-control form-control-solid" rows="5" name="suggested_replies">{{ old('suggested_replies', is_array($businessSettings->suggested_replies) ? implode("\n", $businessSettings->suggested_replies) : '') }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_retention_days') }}</label>
                        <input type="number" name="retention_days" min="1" max="3650" class="form-control form-control-solid" value="{{ old('retention_days', $businessSettings->retention_days) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_pii_policy') }}</label>
                        <select class="form-select form-select-solid" name="pii_policy">
                            <option value="off" {{ $businessSettings->pii_policy === 'off' ? 'selected' : '' }}>{{ __('projectx::lang.chat_pii_off') }}</option>
                            <option value="warn" {{ $businessSettings->pii_policy === 'warn' ? 'selected' : '' }}>{{ __('projectx::lang.chat_pii_warn') }}</option>
                            <option value="block" {{ $businessSettings->pii_policy === 'block' ? 'selected' : '' }}>{{ __('projectx::lang.chat_pii_block') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_idle_timeout_minutes') }}</label>
                        <input type="number" name="idle_timeout_minutes" min="1" max="720" class="form-control form-control-solid" value="{{ old('idle_timeout_minutes', $businessSettings->idle_timeout_minutes) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_share_ttl_hours') }}</label>
                        <input type="number" name="share_ttl_hours" min="1" max="8760" class="form-control form-control-solid" value="{{ old('share_ttl_hours', $businessSettings->share_ttl_hours) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('projectx::lang.chat_moderation_enabled') }}</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="moderation_enabled" value="1" {{ $businessSettings->moderation_enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('projectx::lang.chat_moderation_terms') }}</label>
                        <textarea class="form-control form-control-solid" rows="3" name="moderation_terms">{{ old('moderation_terms', $businessSettings->moderation_terms) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-8">
                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.chat_save_settings') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header pt-7">
            <div class="card-title">
                <h3 class="fw-bold text-gray-900 mb-0">{{ __('projectx::lang.chat_memory_title') }}</h3>
            </div>
        </div>
        <div class="card-body pt-5">
            <p class="text-muted fw-semibold fs-7 mb-7">{{ __('projectx::lang.chat_memory_description') }}</p>

            <form method="POST" action="{{ route('projectx.chat.settings.memory.store') }}" class="mb-8 pb-8 border-bottom border-gray-200">
                @csrf
                <div class="row g-5 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('projectx::lang.chat_memory_key') }}</label>
                        <input
                            type="text"
                            name="memory_key"
                            maxlength="150"
                            class="form-control form-control-solid"
                            placeholder="{{ __('projectx::lang.chat_memory_key_placeholder') }}"
                            required
                        >
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('projectx::lang.chat_memory_value') }}</label>
                        <textarea
                            name="memory_value"
                            rows="2"
                            class="form-control form-control-solid"
                            placeholder="{{ __('projectx::lang.chat_memory_value_placeholder') }}"
                            required
                        ></textarea>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('projectx::lang.chat_memory_add') }}</button>
                    </div>
                </div>
            </form>

            @if(($memoryFacts ?? collect())->isEmpty())
                <div class="text-muted fw-semibold fs-7">{{ __('projectx::lang.chat_memory_empty') }}</div>
            @else
                <div class="d-flex flex-column gap-5">
                    @foreach($memoryFacts as $memoryFact)
                        <div class="border border-gray-200 rounded p-5">
                            <div class="row g-5">
                                @if((int) ($memoryFact->user_id ?? 0) === (int) auth()->id())
                                    <div class="col-md-10">
                                        <form method="POST" action="{{ route('projectx.chat.settings.memory.update', ['memory' => $memoryFact->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row g-4 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('projectx::lang.chat_memory_key') }}</label>
                                                    <input
                                                        type="text"
                                                        name="memory_key"
                                                        maxlength="150"
                                                        class="form-control form-control-solid"
                                                        value="{{ $memoryFact->memory_key }}"
                                                        required
                                                    >
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">{{ __('projectx::lang.chat_memory_value') }}</label>
                                                    <textarea
                                                        name="memory_value"
                                                        rows="2"
                                                        class="form-control form-control-solid"
                                                        required
                                                    >{{ $memoryFact->memory_value }}</textarea>
                                                </div>
                                                <div class="col-md-2 d-grid">
                                                    <button type="submit" class="btn btn-light-primary">{{ __('projectx::lang.chat_memory_update') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-md-end">
                                        <form method="POST" action="{{ route('projectx.chat.settings.memory.destroy', ['memory' => $memoryFact->id]) }}" onsubmit="return confirm('{{ __('projectx::lang.chat_memory_delete_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light-danger">{{ __('projectx::lang.chat_memory_delete') }}</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="col-md-10">
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <label class="form-label">{{ __('projectx::lang.chat_memory_key') }}</label>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-solid"
                                                    value="{{ $memoryFact->memory_key }}"
                                                    readonly
                                                >
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">{{ __('projectx::lang.chat_memory_value') }}</label>
                                                <textarea
                                                    rows="2"
                                                    class="form-control form-control-solid"
                                                    readonly
                                                >{{ $memoryFact->memory_value }}</textarea>
                                            </div>
                                        </div>
                                        <div class="text-muted fw-semibold fs-7 mt-3">{{ __('projectx::lang.chat_memory_legacy_read_only') }}</div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-md-end">
                                        <span class="badge badge-light-secondary">{{ __('projectx::lang.chat_memory_read_only') }}</span>
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
@endsection
