@extends('projectx::layouts.main')

@section('title', __('essentials::lang.my_payrolls'))

@section('content')
<div class="card card-flush mb-7">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.pay_components')</h3>
    </div>
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('lang_v1.description')</th>
                        <th>@lang('lang_v1.type')</th>
                        <th>@lang('sale.amount')</th>
                        <th>@lang('essentials::lang.applicable_date')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pay_components as $pay_component)
                        <tr>
                            <td>{{ $pay_component->description }}</td>
                            <td>{{ __('essentials::lang.' . $pay_component->type) }}</td>
                            <td>{{ @num_format($pay_component->amount) }}{{ $pay_component->amount_type === 'percent' ? '%' : '' }}</td>
                            <td>{{ !empty($pay_component->applicable_date) ? @format_date($pay_component->applicable_date) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">@lang('essentials::lang.no_data_found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.all_payrolls')</h3>
    </div>
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_my_payrolls_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('essentials::lang.month_year')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('sale.payment_status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade view_modal" tabindex="-1" aria-hidden="true"></div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('#projectx_my_payrolls_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.hrm.payroll.my-payrolls') }}',
        columns: [
            {data: 'transaction_date', name: 'transaction_date'},
            {data: 'ref_no', name: 'ref_no'},
            {data: 'final_total', name: 'final_total'},
            {data: 'payment_status', name: 'payment_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $(document).on('click', '.btn-modal', function (event) {
        event.preventDefault();
        var href = $(this).data('href') || $(this).attr('href');
        var container = $(this).data('container') || '.view_modal';
        $.get(href, function (response) {
            $(container).html(response);
            bootstrap.Modal.getOrCreateInstance(document.querySelector(container)).show();
        });
    });
})();
</script>
@endsection
