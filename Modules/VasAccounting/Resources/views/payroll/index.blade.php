@extends('layouts.app')

@section('title', __('vasaccounting::lang.payroll'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.payroll'),
        'subtitle' => 'Bridge Essentials payroll groups into accrual and payment vouchers without leaving the VAS workflow.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Payroll Groups</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['payroll_groups']) }}</div>
                    <div class="text-muted fs-8 mt-1">Detected from Essentials payroll source data</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Bridged Batches</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['bridged_batches']) }}</div>
                    <div class="text-muted fs-8 mt-1">Batches already created in VAS</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Accrued Batches</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['accrued_batches']) }}</div>
                    <div class="text-muted fs-8 mt-1">Accrual vouchers posted from payroll groups</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Payment Vouchers</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['payment_vouchers']) }}</div>
                    <div class="text-muted fs-8 mt-1">Payroll payment postings in the ledger</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">Payroll Group Bridge Queue</span>
                <span class="text-muted fs-7">Bridge accruals and payments per payroll group.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Payroll Group</th>
                            <th>Branch / Month</th>
                            <th>Employees</th>
                            <th>Gross</th>
                            <th>Net</th>
                            <th>Paid</th>
                            <th>VAS Batch</th>
                            <th>Accrual</th>
                            <th>Payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payrollGroups as $row)
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $row['group_name'] }}</div>
                                    <div class="text-muted fs-8">Group #{{ $row['payroll_group_id'] }}</div>
                                </td>
                                <td>
                                    <div>{{ $row['location_name'] }}</div>
                                    <div class="text-muted fs-8">{{ $row['payroll_month'] ?: '-' }}</div>
                                </td>
                                <td>{{ number_format((int) $row['employee_count']) }}</td>
                                <td>{{ number_format((float) $row['gross_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['net_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['paid_total'], 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $row['batch_status'] === 'posted' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) ($row['batch_status'] ?: 'not_bridged')) }}
                                    </span>
                                    <div class="text-muted fs-8 mt-1">{{ optional($row['batch'])->reference_no ?: '-' }}</div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.payroll.bridge') }}" class="d-flex flex-column gap-2">
                                        @csrf
                                        <input type="hidden" name="payroll_group_id" value="{{ $row['payroll_group_id'] }}">
                                        <button type="submit" class="btn btn-light-primary btn-sm">Bridge Accrual</button>
                                        <span class="text-muted fs-8">Voucher: {{ $row['accrual_voucher_id'] ?: '-' }}</span>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.payroll.bridge_payments') }}" class="d-flex flex-column gap-2">
                                        @csrf
                                        <input type="hidden" name="payroll_group_id" value="{{ $row['payroll_group_id'] }}">
                                        <button type="submit" class="btn btn-light-success btn-sm">Bridge Payments</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted">No Essentials payroll groups were found for this business.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">VAS Payroll Batches</span>
                <span class="text-muted fs-7">Monitor bridged batch status and voucher references.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Batch</th>
                            <th>Month / Branch</th>
                            <th>Gross / Net</th>
                            <th>Status</th>
                            <th>Accrual Voucher</th>
                            <th>Payment Vouchers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payrollBatches as $row)
                            @php($batch = $row['batch'])
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $batch->reference_no ?: 'Batch #' . $batch->id }}</div>
                                    <div class="text-muted fs-8">Payroll group #{{ $batch->payroll_group_id ?: '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($batch->payroll_month)->format('Y-m') ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ optional($batch->businessLocation)->name ?: 'No branch linked' }}</div>
                                </td>
                                <td>{{ number_format((float) $batch->gross_total, 2) }} / {{ number_format((float) $batch->net_total, 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $batch->status === 'posted' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $batch->status) }}
                                    </span>
                                </td>
                                <td>{{ $row['accrual_voucher_id'] ?: '-' }}</td>
                                <td>{{ $row['payment_voucher_ids']->isNotEmpty() ? $row['payment_voucher_ids']->implode(', ') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No payroll batches have been bridged into VAS yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
