@extends('projectx::layouts.main')

@section('title', __('essentials::lang.holiday'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.holiday')</h1>
    </div>
    @if($is_admin)
        <a href="{{ route('projectx.essentials.hrm.holiday.create') }}" class="btn btn-primary btn-sm">@lang('messages.add')</a>
    @endif
</div>

<div class="card card-flush mb-7">
    <div class="card-body">
        <div class="row g-5">
            <div class="col-md-4">
                <label class="form-label">@lang('purchase.business_location')</label>
                <select id="projectx_holiday_location_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">@lang('lang_v1.all')</option>
                    @foreach($locations as $location_id => $location_name)
                        <option value="{{ $location_id }}">{{ $location_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('report.start_date')</label>
                <input type="text" id="projectx_holiday_start_date" class="form-control form-control-solid projectx-flatpickr-date">
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('report.end_date')</label>
                <input type="text" id="projectx_holiday_end_date" class="form-control form-control-solid projectx-flatpickr-date">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-light-primary w-100" id="projectx_holiday_apply_filter">@lang('report.apply_filters')</button>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_holidays_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('lang_v1.date')</th>
                        <th>@lang('business.business_location')</th>
                        <th>@lang('brand.note')</th>
                        @if($is_admin)
                            <th>@lang('messages.action')</th>
                        @endif
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
    $('.projectx-flatpickr-date').flatpickr({dateFormat: 'Y-m-d'});

    var columns = [
        {data: 'name', name: 'essentials_holidays.name'},
        {data: 'start_date', name: 'start_date'},
        {data: 'location', name: 'bl.name'},
        {data: 'note', name: 'note'}
    ];

    if ({{ $is_admin ? 'true' : 'false' }}) {
        columns.push({data: 'action', name: 'action', orderable: false, searchable: false});
    }

    var holidaysTable = $('#projectx_holidays_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projectx.essentials.hrm.holiday.index') }}',
            data: function (d) {
                d.location_id = $('#projectx_holiday_location_id').val();
                d.start_date = $('#projectx_holiday_start_date').val();
                d.end_date = $('#projectx_holiday_end_date').val();
            }
        },
        columns: columns
    });

    $('#projectx_holiday_apply_filter, #projectx_holiday_location_id, #projectx_holiday_start_date, #projectx_holiday_end_date').on('change click', function () {
        holidaysTable.ajax.reload();
    });

    $(document).on('click', '.projectx-delete-holiday', function (event) {
        event.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.hrm.holiday.destroy', ['holiday' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    holidaysTable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
