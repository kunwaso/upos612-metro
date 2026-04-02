<div class="card card-flush h-100">
    <div class="card-header">
        <div class="card-title d-flex flex-column">
            <span class="fw-bold text-gray-900 fs-4">{{ $title ?? __('vasaccounting::lang.views.shared.quick_insights') }}</span>
            @if (!empty($subtitle))
                <span class="text-muted fw-semibold fs-8 mt-1">{{ $subtitle }}</span>
            @endif
        </div>
    </div>
    <div class="card-body pt-2">
        <div class="d-flex flex-column gap-4" @if(!empty($listId)) id="{{ $listId }}" @endif>
            @forelse (($items ?? []) as $item)
                <div class="d-flex align-items-start gap-3 p-4 rounded border border-gray-200">
                    <span class="symbol symbol-30px">
                        <span class="symbol-label bg-{{ data_get($item, 'badgeVariant', 'light-primary') }}">
                            <i class="{{ data_get($item, 'icon', 'ki-outline ki-information-4') }} fs-6 text-{{ str_replace('light-', '', (string) data_get($item, 'badgeVariant', 'primary')) }}"></i>
                        </span>
                    </span>
                    <div class="flex-grow-1">
                        <div class="text-gray-900 fw-semibold fs-7">{{ data_get($item, 'title') }}</div>
                        @if (data_get($item, 'description'))
                            <div class="text-muted fs-8 mt-1">{{ data_get($item, 'description') }}</div>
                        @endif
                    </div>
                </div>
            @empty
                @include('vasaccounting::partials.workspace.empty_state', [
                    'title' => __('vasaccounting::lang.views.shared.no_data_title'),
                    'body' => __('vasaccounting::lang.views.shared.no_data_body'),
                ])
            @endforelse
        </div>
    </div>
</div>
