<div class="mb-3">
    <div class="fw-semibold text-gray-900">{{ $todo->task }} ({{ $todo->task_id }})</div>
</div>

@if(empty($sheets))
    <div class="alert alert-light-warning mb-0">@lang('essentials::lang.no_docs_found')</div>
@else
    <ul class="list-group">
        @foreach($sheets as $sheet)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $sheet['name'] ?? __('essentials::lang.spreadsheets') }}</span>
                @if(!empty($sheet['url']))
                    <a href="{{ $sheet['url'] }}" target="_blank" class="btn btn-sm btn-light-primary">@lang('messages.view')</a>
                @endif
            </li>
        @endforeach
    </ul>
@endif
