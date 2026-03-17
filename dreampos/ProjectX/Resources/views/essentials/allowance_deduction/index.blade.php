@extends('projectx::layouts.main')

@section('title', __('essentials::lang.pay_components'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.pay_components')</h1>
    </div>
    @if(auth()->user()->can('essentials.add_allowance_and_deduction'))
        <a href="{{ route('projectx.essentials.allowance-deduction.create') }}" class="btn btn-primary btn-sm">@lang('messages.add')</a>
    @endif
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_allowance_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('lang_v1.description')</th>
                        <th>@lang('lang_v1.type')</th>
                        <th>@lang('sale.amount')</th>
                        <th>@lang('essentials::lang.applicable_date')</th>
                        <th>@lang('essentials::lang.employee')</th>
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
    var table = $('#projectx_allowance_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.allowance-deduction.index') }}',
        columns: [
            {data: 'description', name: 'description'},
            {data: 'type', name: 'type'},
            {data: 'amount', name: 'amount'},
            {data: 'applicable_date', name: 'applicable_date'},
            {data: 'employees', name: 'employees', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $(document).on('click', '.projectx-delete-allowance', function (event) {
        event.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.allowance-deduction.destroy', ['allowance_deduction' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
