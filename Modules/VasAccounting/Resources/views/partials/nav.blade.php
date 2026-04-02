@if (!empty(data_get($vasAccountingNavConfig ?? [], 'navigation_groups', [])))
    <div class="d-flex flex-column gap-4">
        @foreach (data_get($vasAccountingNavConfig ?? [], 'navigation_groups', []) as $group)
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 py-3 border-bottom border-gray-100">
                <div class="min-w-lg-150px">
                    <span class="badge badge-{{ $group['badge_variant'] ?? 'light-primary' }}">{{ $group['label'] }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($group['items'] as $item)
                        @if (empty($item['permission']) || auth()->user()->can($item['permission']))
                            <a class="btn btn-sm {{ request()->routeIs($item['active']) ? 'btn-primary' : 'btn-light-primary' }} btn-active-color-white" href="{{ route($item['route']) }}">
                                <i class="{{ $item['icon'] ?? 'ki-outline ki-chart-simple-2' }} fs-5 me-1"></i>{{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    @php
        $navItems = data_get($vasAccountingNavConfig ?? [], 'navigation_items', []);
    @endphp

    <div class="d-flex flex-wrap gap-2 py-2">
        @foreach ($navItems as $item)
            @if (empty($item['permission']) || auth()->user()->can($item['permission']))
                <a class="btn btn-sm {{ request()->routeIs($item['active']) ? 'btn-primary' : 'btn-light-primary' }} btn-active-color-white" href="{{ route($item['route']) }}">
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </div>
@endif
