@extends('projectx::layouts.main')

@section('title', __('essentials::lang.sales_target'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.sales_target')</h1>
    </div>
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_sales_target_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('report.user')</th>
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
    $('#projectx_sales_target_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.hrm.sales-target.index') }}',
        columns: [
            {data: 'full_name', name: 'full_name'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
})();
</script>
@endsection
