<div class="card card-flush {{ $class ?? '' }}" @if(!empty($id)) id="{{ $id }}" @endif>
    @if(empty($header))
        @if(!empty($title) || !empty($tool))
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <h3 class="card-title">
                {!! $icon ?? '' !!}
                {{ $title ?? '' }}
            </h3>
            @if(!empty($help_text))
                <small class="text-muted ms-2">{!! $help_text !!}</small>
            @endif
            @if(!empty($tool))
            <div class="card-toolbar">{!! $tool !!}</div>
            @endif
        </div>
        @endif
    @else
        <div class="card-header align-items-center py-5">
            {!! $header !!}
        </div>
    @endif
    <div class="card-body pt-0">{{ $slot }}</div>
</div>
