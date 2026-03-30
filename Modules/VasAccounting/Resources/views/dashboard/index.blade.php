@extends('layouts.app')

@section('title', __('vasaccounting::lang.dashboard'))

@section('content')
    @php
        $dashboardActions = '<div class="d-flex flex-wrap gap-3">'
            . '<a href="' . route('vasaccounting.vouchers.create') . '" class="btn btn-primary btn-sm">' . $vasAccountingUtil->actionLabel('new_voucher') . '</a>'
            . '<a href="' . route('vasaccounting.closing.index') . '" class="btn btn-light-warning btn-sm">' . $vasAccountingUtil->actionLabel('period_close') . '</a>'
            . '</div>';
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.dashboard'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
        'actions' => $dashboardActions,
    ])

    @if (!empty($autoBootstrapped))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>
                <div class="fw-bold">{{ __('vasaccounting::lang.auto_bootstrap_title') }}</div>
                <div class="text-muted">{{ __('vasaccounting::lang.auto_bootstrap_body') }}</div>
            </div>
        </div>
    @endif

    <div class="card card-flush mb-8">
        <div class="card-body py-6">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <div class="text-gray-900 fw-bold fs-4 mb-1">Ảnh chụp sức khỏe kế toán</div>
                    <div class="text-muted fs-7">Chỉ số KPI ở cấp doanh nghiệp luôn hiển thị tổng thể. Khu vực chứng từ gần đây và hoạt động sẽ áp dụng bộ lọc chi nhánh đang chọn.</div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_reports') }}</a>
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light btn-sm">Hàng đợi chứng từ</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('open_periods') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['openPeriods'] }}</div>
                    <div class="text-muted fs-8 mt-1">Các kỳ hiện đang cho phép ghi sổ.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('posting_failures') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postingFailures'] }}</div>
                    <div class="text-muted fs-8 mt-1">Các lỗi ghi sổ chưa được xử lý trong hàng đợi.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('inventory_value') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ number_format($inventoryTotals['inventory_value'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">Giá trị được lấy từ dịch vụ định giá tồn kho.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('posted_this_month') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postedThisMonth'] }}</div>
                    <div class="text-muted fs-8 mt-1">Sản lượng chứng từ đã ghi sổ trong tháng hiện tại.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span>Chứng từ gần đây</span>
                        @if (!empty($selectedLocationId))
                            <span class="text-muted fw-semibold fs-8 mt-1">Đang lọc theo chi nhánh đã chọn</span>
                        @endif
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_register') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Chứng từ</th>
                                    <th>Loại</th>
                                    <th>Phân hệ</th>
                                    <th>Ngày ghi sổ</th>
                                    <th class="text-end">Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $voucher)
                                    <tr>
                                        <td><a href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}" class="text-gray-900 fw-semibold">{{ $voucher->voucher_no }}</a></td>
                                        <td>{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</td>
                                        <td>{{ $vasAccountingUtil->moduleAreaLabel((string) ($voucher->module_area ?: 'accounting')) }}</td>
                                        <td>{{ $voucher->posting_date }}</td>
                                        <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                        <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">Chưa có chứng từ nào được ghi sổ.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Bảng tin vận hành</div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-5">
                        <div class="p-5 rounded bg-light-warning">
                            <div class="text-gray-900 fw-bold fs-6 mb-1">Điểm nghẽn trước khóa sổ</div>
                            <div class="text-muted fs-8">Cần xử lý dứt điểm các lỗi trước khi khóa sổ kỳ.</div>
                        </div>
                        @forelse ($failures as $failure)
                            <div class="d-flex align-items-start gap-4 p-4 border border-gray-200 rounded">
                                <span class="bullet bullet-vertical h-40px bg-warning"></span>
                                <div class="flex-grow-1">
                                    <div class="text-gray-900 fw-semibold fs-7">{{ \Illuminate\Support\Str::limit($failure->error_message, 90) }}</div>
                                    <div class="text-muted fs-8 mt-1">{{ $failure->source_type }}:{{ $failure->source_id }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted fs-7">Không có lỗi ghi sổ tồn đọng.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-8">
        <div class="card-header">
            <div class="card-title">Danh sách theo dõi kỳ kế toán</div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.periods.index') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('manage_periods') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Kỳ</th>
                            <th>Khoảng thời gian</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Trung tâm khóa sổ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            <tr>
                                <td class="text-gray-900 fw-semibold">{{ $vasAccountingUtil->localizedPeriodName($period->name) }}</td>
                                <td>{{ $period->start_date }} - {{ $period->end_date }}</td>
                                <td>
                                    <span class="badge {{ $period->status === 'closed' ? 'badge-light-danger' : ($period->status === 'soft_locked' ? 'badge-light-warning' : 'badge-light-success') }}">
                                        {{ $vasAccountingUtil->periodStatusLabel((string) $period->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vasaccounting.closing.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Chưa có kỳ kế toán nào được thiết lập.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
