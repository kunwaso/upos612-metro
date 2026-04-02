<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-5">
    <div class="d-flex flex-wrap gap-2">
        @foreach (($filters ?? []) as $filterLabel)
            <span class="badge badge-light-primary">{{ $filterLabel }}</span>
        @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center">
        @foreach (($actions ?? []) as $action)
            @if (($action['method'] ?? 'GET') === 'POST')
                @php($confirmMessage = $action['confirm'] ?? null)
                <form
                    method="POST"
                    action="{{ $action['url'] }}"
                    @if (!empty($confirmMessage))
                        onsubmit="return confirm({{ \Illuminate\Support\Js::from($confirmMessage) }});"
                    @endif
                >
                    @csrf
                    <button type="submit" class="btn btn-sm btn-{{ $action['style'] ?? 'light-primary' }}">
                        {{ $action['label'] }}
                    </button>
                </form>
            @else
                <a href="{{ $action['url'] }}" class="btn btn-sm btn-{{ $action['style'] ?? 'light-primary' }}">
                    {{ $action['label'] }}
                </a>
            @endif
        @endforeach

        <div class="position-relative">
            <i class="ki-outline ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"></i>
            <input
                type="text"
                class="form-control form-control-solid w-250px ps-12"
                id="{{ $searchId ?? 'vas-table-search' }}"
                placeholder="{{ __('vasaccounting::lang.views.shared.search') }}"
            />
        </div>
    </div>
</div>
