@extends('projectx::layouts.main')

@section('title', __('essentials::lang.shifts'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.shifts')</h1>
    </div>
    <a href="{{ route('projectx.essentials.hrm.shift.create') }}" class="btn btn-primary btn-sm">@lang('messages.add')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_shift_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('essentials::lang.shift_type')</th>
                        <th>@lang('restaurant.start_time')</th>
                        <th>@lang('restaurant.end_time')</th>
                        <th>@lang('essentials::lang.holiday')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    var shiftTable = $('#projectx_shift_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.hrm.shift.index') }}',
        columns: [
            {data: 'name', name: 'name'},
            {data: 'type', name: 'type'},
            {data: 'start_time', name: 'start_time'},
            {data: 'end_time', name: 'end_time'},
            {data: 'holidays', name: 'holidays'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $(document).on('click', '.projectx-delete-shift', function (event) {
        event.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.hrm.shift.destroy', ['shift' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    shiftTable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
