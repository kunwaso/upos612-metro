@extends('projectx::layouts.main')

@section('title', __('essentials::lang.add_to_do'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.add_to_do')</h1>
    </div>
    <a href="{{ route('projectx.essentials.todo.index') }}" class="btn btn-light-primary btn-sm">@lang('business.back')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <form method="POST" action="{{ route('projectx.essentials.todo.store') }}">
            @csrf
            @include('projectx::essentials.todo.partials._form')
            <div class="d-flex justify-content-end mt-8">
                <button type="submit" class="btn btn-primary">@lang('essentials::lang.submit')</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-datetime').flatpickr({
        enableTime: true,
        dateFormat: 'Y-m-d H:i'
    });
})();
</script>
@endsection
