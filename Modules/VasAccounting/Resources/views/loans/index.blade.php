@extends('layouts.app')

@section('title', __('vasaccounting::lang.loans'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.loans'),
        'subtitle' => 'Register loan agreements, post disbursements, and settle repayment schedules through VAS cash and liability accounts.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Loans</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['loan_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Active loans</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['active_loans'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Outstanding principal</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($summary['outstanding_principal'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Due schedules</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['due_schedules'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Register loan</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.loans.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4"><label class="form-label">Loan no</label><input type="text" name="loan_no" class="form-control" placeholder="LOAN-001" required></div>
                            <div class="col-md-8"><label class="form-label">Lender</label><input type="text" name="lender_name" class="form-control" placeholder="BIDV" required></div>
                            <div class="col-md-6"><label class="form-label">Bank account</label><select name="bank_account_id" class="form-select"><option value="">Select bank account</option>@foreach ($bankAccountOptions as $bankAccountId => $bankAccountLabel)<option value="{{ $bankAccountId }}">{{ $bankAccountLabel }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Related contract</label><select name="contract_id" class="form-select"><option value="">Select contract</option>@foreach ($contractOptions as $contractId => $contractLabel)<option value="{{ $contractId }}">{{ $contractLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Principal amount</label><input type="number" step="0.0001" min="0" name="principal_amount" class="form-control" value="0" required></div>
                            <div class="col-md-4"><label class="form-label">Interest rate (%)</label><input type="number" step="0.0001" min="0" name="interest_rate" class="form-control" value="0"></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="active">Active</option></select></div>
                            <div class="col-md-6"><label class="form-label">Disbursement date</label><input type="date" name="disbursement_date" class="form-control" value="{{ now()->format('Y-m-d') }}"></div>
                            <div class="col-md-6"><label class="form-label">Maturity date</label><input type="date" name="maturity_date" class="form-control"></div>
                        </div>
                        <div class="mt-6"><button type="submit" class="btn btn-primary btn-sm">Save loan</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Add repayment schedule</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.loans.schedules.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4"><label class="form-label">Loan</label><select name="loan_id" class="form-select" required><option value="">Select loan</option>@foreach ($loanOptions as $loanId => $loanLabel)<option value="{{ $loanId }}">{{ $loanLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Due date</label><input type="date" name="due_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="planned">Planned</option><option value="due">Due</option></select></div>
                            <div class="col-md-6"><label class="form-label">Principal due</label><input type="number" step="0.0001" min="0" name="principal_due" class="form-control" value="0" required></div>
                            <div class="col-md-6"><label class="form-label">Interest due</label><input type="number" step="0.0001" min="0" name="interest_due" class="form-control" value="0"></div>
                        </div>
                        <div class="mt-6"><button type="submit" class="btn btn-primary btn-sm">Save schedule</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header"><div class="card-title">Loan register</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>Loan</th><th>Bank / contract</th><th>Principal</th><th>Paid</th><th>Outstanding</th><th>Status</th><th>Disburse</th></tr></thead>
                    <tbody>
                        @forelse ($loanRows as $row)
                            @php($loan = $row['loan'])
                            <tr>
                                <td><div class="text-gray-900 fw-semibold">{{ $loan->loan_no }}</div><div class="text-muted fs-8">{{ $loan->lender_name }}</div></td>
                                <td><div>{{ optional($loan->bankAccount)->bank_name ?: 'No bank account linked' }}</div><div class="text-muted fs-8">{{ optional($loan->contract)->contract_no ?: '-' }}</div></td>
                                <td>{{ number_format((float) $loan->principal_amount, 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['principal_paid'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['outstanding_principal'], 2) }} {{ $currency }}</td>
                                <td><span class="badge badge-light-primary">{{ ucfirst($loan->status) }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.loans.disburse', $loan->id) }}" class="d-flex flex-column gap-2">
                                        @csrf
                                        <input type="date" name="disbursed_at" class="form-control form-control-sm" value="{{ optional($loan->disbursement_date)->format('Y-m-d') ?: now()->format('Y-m-d') }}">
                                        <button type="submit" class="btn btn-light-primary btn-sm">Post disbursement</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">No loans have been registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header"><div class="card-title">Repayment schedules</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>Schedule</th><th>Loan</th><th>Amounts</th><th>Status</th><th>Settle</th></tr></thead>
                    <tbody>
                        @forelse ($scheduleRows as $schedule)
                            <tr>
                                <td><div class="text-gray-900 fw-semibold">{{ optional($schedule->due_date)->format('Y-m-d') ?: '-' }}</div><div class="text-muted fs-8">Schedule #{{ $schedule->id }}</div></td>
                                <td><div>{{ optional($schedule->loan)->loan_no ?: '-' }}</div><div class="text-muted fs-8">{{ optional(optional($schedule->loan)->bankAccount)->bank_name ?: '-' }}</div></td>
                                <td><div>Principal: {{ number_format((float) $schedule->principal_due, 2) }}</div><div class="text-muted fs-8">Interest: {{ number_format((float) $schedule->interest_due, 2) }}</div></td>
                                <td><span class="badge {{ $schedule->status === 'paid' ? 'badge-light-success' : 'badge-light-primary' }}">{{ ucfirst($schedule->status) }}</span></td>
                                <td>
                                    @if ($schedule->status !== 'paid')
                                        <form method="POST" action="{{ route('vasaccounting.loans.schedules.settle', $schedule->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="date" name="settled_at" class="form-control form-control-sm" value="{{ optional($schedule->due_date)->format('Y-m-d') ?: now()->format('Y-m-d') }}">
                                            <button type="submit" class="btn btn-light-success btn-sm">Settle schedule</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">Voucher #{{ $schedule->settled_voucher_id ?: '-' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No repayment schedules have been added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
