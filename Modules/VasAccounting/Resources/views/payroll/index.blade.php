@extends('layouts.app')

@section('title', __('vasaccounting::lang.payroll'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.payroll'),
        'subtitle' => 'Reuse Essentials payroll groups, bridge accruals into VAS, and bring payroll payments onto the enterprise ledger.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Payroll groups</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['payroll_groups'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Bridged batches</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['bridged_batches'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Accrued batches</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['accrued_batches'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Payroll payment vouchers</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['payment_vouchers'] }}</div></div></div></div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Essentials payroll groups</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Payroll group</th>
                            <th>Branch / month</th>
                            <th>Employees</th>
                            <th>Gross</th>
                            <th>Net</th>
                            <th>Paid</th>
                            <th>VAS batch</th>
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
                                <td>{{ $row['employee_count'] }}</td>
                                <td>{{ number_format((float) $row['gross_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['net_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['paid_total'], 2) }} {{ $currency }}</td>
                                <td>
                                    <div>{{ ucfirst((string) ($row['batch_status'] ?: 'Not bridged')) }}</div>
                                    <div class="text-muted fs-8">{{ optional($row['batch'])->reference_no ?: '-' }}</div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.payroll.bridge') }}" class="d-flex flex-column gap-2">
                                        @csrf
                                        <input type="hidden" name="payroll_group_id" value="{{ $row['payroll_group_id'] }}">
                                        <button type="submit" class="btn btn-light-primary btn-sm">Bridge accrual</button>
                                        <span class="text-muted fs-8">Voucher: {{ $row['accrual_voucher_id'] ?: '-' }}</span>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.payroll.bridge_payments') }}">
                                        @csrf
                                        <input type="hidden" name="payroll_group_id" value="{{ $row['payroll_group_id'] }}">
                                        <button type="submit" class="btn btn-light-success btn-sm">Bridge payments</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No Essentials payroll groups were found for this business yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">VAS payroll batches</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Batch</th>
                            <th>Month / branch</th>
                            <th>Gross / net</th>
                            <th>Status</th>
                            <th>Accrual voucher</th>
                            <th>Payment vouchers</th>
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
                                <td><span class="badge badge-light-primary">{{ ucfirst($batch->status) }}</span></td>
                                <td>{{ $row['accrual_voucher_id'] ?: '-' }}</td>
                                <td>{{ $row['payment_voucher_ids']->isNotEmpty() ? $row['payment_voucher_ids']->implode(', ') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No payroll batches have been bridged into VAS yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
