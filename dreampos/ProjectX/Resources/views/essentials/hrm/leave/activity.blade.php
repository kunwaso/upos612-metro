@extends('projectx::layouts.main')

@section('title', __('essentials::lang.activity'))

@section('content')
<div class="card card-flush">
    <div class="card-header"><h3 class="card-title">@lang('essentials::lang.activity')</h3></div>
    <div class="card-body">
        <div class="timeline">
            @foreach($activities as $activity)
                <div class="mb-5">
                    <div class="fw-bold">{{ optional($activity->causer)->user_full_name }}</div>
                    <div class="text-muted fs-7">{{ $activity->description }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
