<div class="card card-flush mb-8">
    <div class="card-header pt-7">
        <div class="card-title d-flex flex-column">
            <span class="text-gray-900 fw-bold fs-2">{{ $title }}</span>
            @if (!empty($subtitle))
                <span class="text-muted fw-semibold fs-7">{{ $subtitle }}</span>
            @endif
        </div>
        @isset($actions)
            <div class="card-toolbar">
                {!! $actions !!}
            </div>
        @endisset
    </div>
    <div class="card-body pt-0">
        @include('vasaccounting::partials.nav')
    </div>
</div>
