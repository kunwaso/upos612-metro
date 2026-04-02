@php
    $cards = collect($cards ?? []);
@endphp

<div class="row g-5 g-xl-8" data-vas-kpi-strip>
    @forelse ($cards as $card)
        @php
            $direction = (string) data_get($card, 'direction', 'flat');
            $delta = data_get($card, 'delta');
            $isPositive = $direction === 'up';
            $isNegative = $direction === 'down';
            $deltaBadge = $isPositive ? 'badge-light-success' : ($isNegative ? 'badge-light-danger' : 'badge-light-secondary');
            $deltaPrefix = $isPositive ? '+' : '';
        @endphp
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <span class="symbol symbol-35px">
                            <span class="symbol-label bg-{{ data_get($card, 'badgeVariant', 'light-primary') }}">
                                <i class="{{ data_get($card, 'icon', 'ki-outline ki-chart-line-up-2') }} fs-4 text-{{ str_replace('light-', '', (string) data_get($card, 'badgeVariant', 'primary')) }}"></i>
                            </span>
                        </span>
                        @if ($delta !== null && $delta !== '')
                            <span class="badge {{ $deltaBadge }}">
                                {{ $deltaPrefix }}{{ $delta }}%
                            </span>
                        @endif
                    </div>
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ data_get($card, 'label') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ data_get($card, 'value') }}</div>
                    @if (data_get($card, 'hint'))
                        <div class="text-muted fs-8 mt-2">{{ data_get($card, 'hint') }}</div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            @include('vasaccounting::partials.workspace.empty_state', [
                'title' => __('vasaccounting::lang.views.shared.no_data_title'),
                'body' => __('vasaccounting::lang.views.shared.no_data_body'),
            ])
        </div>
    @endforelse
</div>
