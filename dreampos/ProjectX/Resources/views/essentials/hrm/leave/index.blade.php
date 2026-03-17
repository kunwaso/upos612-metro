@extends('projectx::layouts.main')

@section('title', __('essentials::lang.leave'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.leave')</h1>
    </div>
    @if(auth()->user()->can('essentials.crud_all_leave') || auth()->user()->can('essentials.crud_own_leave'))
        <a href="{{ route('projectx.essentials.hrm.leave.create') }}" class="btn btn-primary btn-sm">@lang('messages.add')</a>
    @endif
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_leave_table">
            <thead>
                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                    <th>@lang('essentials::lang.user')</th>
                    <th>@lang('essentials::lang.leave_type')</th>
                    <th>@lang('essentials::lang.leave_from')</th>
                    <th>@lang('essentials::lang.ref_no')</th>
                    <th>@lang('essentials::lang.status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('projectx::essentials.hrm.leave.change_status_modal')
@endsection

@section('page_javascript')
<script>
(function () {
    var table = $('#projectx_leave_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.hrm.leave.index') }}',
        columns: [
            {data: 'user', name: 'user'},
            {data: 'leave_type', name: 'leave_type'},
            {data: 'start_date', name: 'start_date'},
            {data: 'ref_no', name: 'ref_no'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $(document).on('click', '.projectx-delete-leave', function (e) {
        e.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var id = $(this).data('id');
        $.ajax({
            method: 'DELETE',
            url: @json(route('projectx.essentials.hrm.leave.destroy', ['leave' => '__ID__'])).replace('__ID__', id),
            data: {_token: @json(csrf_token())},
            success: function () { table.ajax.reload(); }
        });
    });

    $(document).on('click', '.projectx-change-leave-status', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#projectx_leave_change_status_leave_id').val(id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_leave_change_status_modal')).show();
    });

    $('#projectx_leave_change_status_submit').on('click', function () {
        $.post(@json(route('projectx.essentials.hrm.leave.change-status')), $('#projectx_leave_change_status_form').serialize(), function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_leave_change_status_modal')).hide();
            table.ajax.reload();
        });
    });
})();
</script>
@endsection
