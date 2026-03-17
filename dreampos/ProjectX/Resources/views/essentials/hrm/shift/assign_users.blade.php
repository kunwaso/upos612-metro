@extends('projectx::layouts.main')

@section('title', __('essentials::lang.assign_users'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">
            @lang('essentials::lang.assign_users')
            <span class="text-muted fs-7 ms-2">({{ $shift->name }})</span>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.shift.assign-users.store') }}" id="projectx_assign_shift_form">
            @csrf
            <input type="hidden" name="shift_id" value="{{ $shift->id }}">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>&nbsp;</th>
                            <th>@lang('report.user')</th>
                            <th>@lang('business.start_date')</th>
                            <th>@lang('essentials::lang.end_date')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user_id => $user_name)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input projectx-shift-user-check"
                                        data-user-id="{{ $user_id }}"
                                        name="user_shift[{{ $user_id }}][is_added]"
                                        value="1"
                                        {{ array_key_exists($user_id, $user_shifts) ? 'checked' : '' }}>
                                </td>
                                <td>{{ $user_name }}</td>
                                <td>
                                    <input type="text"
                                        name="user_shift[{{ $user_id }}][start_date]"
                                        class="form-control form-control-solid projectx-flatpickr-date"
                                        value="{{ array_key_exists($user_id, $user_shifts) ? $user_shifts[$user_id]['start_date'] : '' }}">
                                </td>
                                <td>
                                    <input type="text"
                                        name="user_shift[{{ $user_id }}][end_date]"
                                        class="form-control form-control-solid projectx-flatpickr-date"
                                        value="{{ array_key_exists($user_id, $user_shifts) ? $user_shifts[$user_id]['end_date'] : '' }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                <a href="{{ route('projectx.essentials.hrm.shift.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-date').flatpickr({dateFormat: 'Y-m-d'});
})();
</script>
@endsection
