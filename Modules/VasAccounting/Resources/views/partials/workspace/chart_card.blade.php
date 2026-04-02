<div class="card card-flush h-100">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title d-flex flex-column">
            <span class="fw-bold text-gray-900 fs-4">{{ $title ?? __('vasaccounting::lang.views.shared.chart_title') }}</span>
            @if (!empty($subtitle))
                <span class="text-muted fw-semibold fs-8 mt-1">{{ $subtitle }}</span>
            @endif
        </div>
        @if (!empty($toolbar))
            <div class="card-toolbar">
                {!! $toolbar !!}
            </div>
        @endif
    </div>
    <div class="card-body pt-2">
        <div id="{{ $chartId ?? 'vas-chart-card' }}" class="min-h-auto" style="height: {{ $chartHeight ?? '320px' }}"></div>
    </div>
</div>
