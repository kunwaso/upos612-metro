@extends('layouts.app')

@section('title', __('aichat::lang.chat_memory_admin_title'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('aichat::lang.chat_memory_admin_title') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('aichat::lang.chat_memory_admin_description') }}</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->can('aichat.chat.settings'))
            <a href="{{ route('aichat.chat.settings') }}" class="btn btn-light-primary btn-sm">
                {{ __('aichat::lang.ai_chat_settings') }}
            </a>
        @endif
        @if(auth()->user()->can('aichat.chat.view'))
            <a href="{{ route('aichat.chat.index') }}" class="btn btn-light btn-sm">
                {{ __('aichat::lang.ai_chat') }}
            </a>
        @endif
    </div>
</div>

<div class="card card-flush">
    <div class="card-header pt-7">
        <div class="card-title">
            <h3 class="fw-bold text-gray-900 mb-0">{{ __('aichat::lang.chat_memory_admin_list_title') }}</h3>
        </div>
        <div class="card-toolbar">
            <form method="GET" action="{{ route('aichat.chat.settings.memories.admin') }}" class="d-flex align-items-center gap-3">
                <label class="form-label mb-0">{{ __('aichat::lang.chat_memory_admin_per_page') }}</label>
                <select name="per_page" class="form-select form-select-sm form-select-solid w-120px" onchange="this.form.submit()">
                    <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ (int) $perPage === 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body pt-5">
        <div class="text-muted fw-semibold fs-7 mb-6">
            {{ __('aichat::lang.chat_memory_admin_total_orgs', ['count' => $persistentMemories->total()]) }}
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-row-dashed gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>{{ __('aichat::lang.chat_memory_admin_business') }}</th>
                        <th>{{ __('aichat::lang.chat_memory_admin_container') }}</th>
                        <th>{{ __('aichat::lang.chat_memory_admin_fact_count') }}</th>
                        <th>{{ __('aichat::lang.chat_memory_admin_facts') }}</th>
                        <th class="text-end">{{ __('aichat::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-700">
                    @forelse($persistentMemories as $persistentMemory)
                        <tr>
                            <td class="align-top">
                                <div class="fw-bold text-gray-900">
                                    {{ $persistentMemory->business->name ?? __('aichat::lang.chat_memory_admin_unknown_business') }}
                                </div>
                                <div class="text-muted fs-7">ID: {{ $persistentMemory->business_id }}</div>
                            </td>
                            <td class="align-top">
                                <form method="POST" action="{{ route('aichat.chat.settings.memories.admin.updateName', ['business' => $persistentMemory->business_id]) }}" class="d-flex flex-column gap-3">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="form-label">{{ __('aichat::lang.chat_memory_container_name') }}</label>
                                        <input
                                            type="text"
                                            name="display_name"
                                            maxlength="150"
                                            class="form-control form-control-solid"
                                            value="{{ $persistentMemory->display_name }}"
                                            placeholder="{{ __('aichat::lang.chat_memory_default_display_name') }}"
                                        >
                                    </div>
                                    <div class="text-muted fs-7">
                                        {{ __('aichat::lang.chat_memory_container_slug') }}:
                                        <code>{{ $persistentMemory->slug }}</code>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-light-primary btn-sm">
                                            {{ __('aichat::lang.chat_memory_admin_update_name') }}
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td class="align-top">
                                <span class="badge badge-light-primary">{{ $persistentMemory->memory_facts_count }}</span>
                            </td>
                            <td class="align-top">
                                @if($persistentMemory->memoryFacts->isEmpty())
                                    <div class="text-muted fs-7">{{ __('aichat::lang.chat_memory_empty') }}</div>
                                @else
                                    <div class="d-flex flex-column gap-4">
                                        @foreach($persistentMemory->memoryFacts as $memoryFact)
                                            <div class="border border-gray-200 rounded p-4">
                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                    <div class="fw-bold text-gray-900">{{ $memoryFact->memory_key }}</div>
                                                    <span class="badge badge-light-secondary">
                                                        {{ $memoryFact->user_id === null ? __('aichat::lang.chat_scope_business') : __('aichat::lang.chat_scope_user') }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-700 mt-2" style="white-space: pre-wrap;">{{ $memoryFact->memory_value }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="align-top text-end">
                                <form method="POST" action="{{ route('aichat.chat.settings.memories.admin.wipe', ['business' => $persistentMemory->business_id]) }}" onsubmit="return confirm('{{ __('aichat::lang.chat_memory_admin_wipe_confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light-danger btn-sm">
                                        {{ __('aichat::lang.chat_memory_admin_wipe') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-10">
                                {{ __('aichat::lang.chat_memory_admin_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($persistentMemories->hasPages())
            <div class="d-flex justify-content-end mt-6">
                {{ $persistentMemories->appends(['per_page' => $perPage])->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
