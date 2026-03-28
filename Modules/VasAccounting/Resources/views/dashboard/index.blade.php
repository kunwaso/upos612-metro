@extends('layouts.app')

@section('title', __('vasaccounting::lang.dashboard'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.dashboard'),
        'subtitle' => 'Posting health, close readiness, and statutory ledger activity at a glance.',
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

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Open periods</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['openPeriods'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Posting failures</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postingFailures'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Inventory value</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ number_format($inventoryTotals['inventory_value'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Posted this month</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postedThisMonth'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Recent vouchers</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Type</th>
                                    <th>Posting date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $voucher)
                                    <tr>
                                        <td><a href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}" class="text-gray-900 fw-semibold">{{ $voucher->voucher_no }}</a></td>
                                        <td>{{ $voucher->voucher_type }}</td>
                                        <td>{{ $voucher->posting_date }}</td>
                                        <td><span class="badge badge-light-primary">{{ $voucher->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No vouchers posted yet.</td></tr>
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
                    <div class="card-title">Close blockers</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Failure</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($failures as $failure)
                                    <tr>
                                        <td class="text-gray-900">{{ \Illuminate\Support\Str::limit($failure->error_message, 80) }}</td>
                                        <td>{{ $failure->source_type }}:{{ $failure->source_id }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">No unresolved posting failures.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
