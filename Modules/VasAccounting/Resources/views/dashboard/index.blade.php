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
                    <div class="text-gray-900 fw-bold fs-4 mb-1">{{ __('vasaccounting::lang.views.dashboard.snapshot.title') }}</div>
                    <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.dashboard.snapshot.subtitle') }}</div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_reports') }}</a>
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.dashboard.snapshot.voucher_queue') }}</a>
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
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.metrics.open_periods') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('posting_failures') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postingFailures'] }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.metrics.posting_failures') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('inventory_value') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ number_format($inventoryTotals['inventory_value'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.metrics.inventory_value') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('posted_this_month') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postedThisMonth'] }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.metrics.posted_this_month') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.title') }}</span>
                        @if (!empty($selectedLocationId))
                            <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.filtered_branch') }}</span>
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
                                    <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.module_area') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.posting_date') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.total_amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.status') }}</th>
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
                                    <tr><td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.empty') }}</td></tr>
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
                    <div class="card-title">{{ __('vasaccounting::lang.views.dashboard.operations_board.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-5">
                        <div class="p-5 rounded bg-light-warning">
                            <div class="text-gray-900 fw-bold fs-6 mb-1">{{ __('vasaccounting::lang.views.dashboard.operations_board.blockers_title') }}</div>
                            <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.dashboard.operations_board.blockers_body') }}</div>
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
                            <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.dashboard.operations_board.empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-8">
        <div class="card-header">
            <div class="card-title">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.title') }}</div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.periods.index') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('manage_periods') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.period') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.range') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.status') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.close_center') }}</th>
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
                                <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
