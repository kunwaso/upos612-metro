@php
    $navItems = data_get($vasAccountingNavConfig ?? [], 'navigation_items', []);
@endphp

<ul class="nav nav-pills flex-wrap gap-2 fs-7 fw-semibold">
    @foreach ($navItems as $item)
        @if (empty($item['permission']) || auth()->user()->can($item['permission']))
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    {{ $item['label'] }}
                </a>
            </li>
        @endif
    @endforeach
</ul>
