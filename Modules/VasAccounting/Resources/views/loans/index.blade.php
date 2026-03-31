@extends('layouts.app')

@section('title', __('vasaccounting::lang.loans'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.loans'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.loans.cards.loans') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['loan_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.loans.cards.loans_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.loans.cards.active_loans') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['active_loans']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.loans.cards.active_loans_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.loans.cards.outstanding_principal') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((float) $summary['outstanding_principal'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.loans.cards.due_schedules') }}</div>
                    <div class="text-gray-900 fw-bolder fs-2">{{ number_format((int) $summary['due_schedules']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.loans.cards.due_schedules_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header align-items-center py-5">
                    <div class="card-title d-flex flex-column">
                        <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.loans.register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.loans.register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.loans.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.loan_no') }}</label>
                                <input type="text" name="loan_no" class="form-control" placeholder="LOAN-001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.lender') }}</label>
                                <input type="text" name="lender_name" class="form-control" placeholder="BIDV" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.bank_account') }}</label>
                                <select name="bank_account_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_bank_account') }}</option>
                                    @foreach ($bankAccountOptions as $bankAccountId => $bankAccountLabel)
                                        <option value="{{ $bankAccountId }}">{{ $bankAccountLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.related_contract') }}</label>
                                <select name="contract_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_contract') }}</option>
                                    @foreach ($contractOptions as $contractId => $contractLabel)
                                        <option value="{{ $contractId }}">{{ $contractLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.principal_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="principal_amount" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.interest_rate') }}</label>
                                <input type="number" step="0.0001" min="0" name="interest_rate" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="draft">{{ __('vasaccounting::lang.generic_statuses.draft') }}</option>
                                    <option value="active">{{ __('vasaccounting::lang.generic_statuses.active') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.disbursement_date') }}</label>
                                <input type="date" name="disbursement_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.register.fields.maturity_date') }}</label>
                                <input type="date" name="maturity_date" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.loans.register.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header align-items-center py-5">
                    <div class="card-title d-flex flex-column">
                        <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.loans.schedule_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.loans.schedule_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.loans.schedules.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.schedule_form.fields.loan') }}</label>
                                <select name="loan_id" class="form-select" data-control="select2" required>
                                    <option value="">{{ __('vasaccounting::lang.views.loans.schedule_form.select_loan') }}</option>
                                    @foreach ($loanOptions as $loanId => $loanLabel)
                                        <option value="{{ $loanId }}">{{ $loanLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.schedule_form.fields.due_date') }}</label>
                                <input type="date" name="due_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="planned">{{ __('vasaccounting::lang.generic_statuses.planned') }}</option>
                                    <option value="due">{{ __('vasaccounting::lang.generic_statuses.due') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.schedule_form.fields.principal_due') }}</label>
                                <input type="number" step="0.0001" min="0" name="principal_due" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.loans.schedule_form.fields.interest_due') }}</label>
                                <input type="number" step="0.0001" min="0" name="interest_due" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.loans.schedule_form.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center py-5">
            <div class="card-title d-flex flex-column">
                <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.loans.loan_register.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.loans.loan_register.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.loan') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.bank_contract') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.principal') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.paid') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.outstanding') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.loan_register.table.disburse') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loanRows as $row)
                            @php($loan = $row['loan'])
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $loan->loan_no }}</div>
                                    <div class="text-muted fs-8">{{ $loan->lender_name }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($loan->bankAccount)->bank_name ?: __('vasaccounting::lang.views.loans.loan_register.no_bank_account') }}</div>
                                    <div class="text-muted fs-8">{{ optional($loan->contract)->contract_no ?: '-' }}</div>
                                </td>
                                <td>{{ number_format((float) $loan->principal_amount, 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['principal_paid'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['outstanding_principal'], 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $loan->status === 'active' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $loan->status) }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.loans.disburse', $loan->id) }}" class="d-flex flex-column gap-2">
                                        @csrf
                                        <input type="date" name="disbursed_at" class="form-control form-control-sm" value="{{ optional($loan->disbursement_date)->format('Y-m-d') ?: now()->format('Y-m-d') }}">
                                        <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.loans.loan_register.actions.post_disbursement') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.loans.loan_register.empty') }}</td>
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
                <span class="text-gray-900 fw-bold">{{ __('vasaccounting::lang.views.loans.schedules.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.loans.schedules.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.loans.schedules.table.schedule') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.schedules.table.loan') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.schedules.table.amounts') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.loans.schedules.table.settle') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scheduleRows as $schedule)
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ optional($schedule->due_date)->format('Y-m-d') ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.loans.schedules.schedule_label', ['id' => $schedule->id]) }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($schedule->loan)->loan_no ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ optional(optional($schedule->loan)->bankAccount)->bank_name ?: '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ __('vasaccounting::lang.views.loans.schedules.principal', ['amount' => number_format((float) $schedule->principal_due, 2)]) }}</div>
                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.loans.schedules.interest', ['amount' => number_format((float) $schedule->interest_due, 2)]) }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $schedule->status === 'paid' ? 'badge-light-success' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $schedule->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($schedule->status !== 'paid')
                                        <form method="POST" action="{{ route('vasaccounting.loans.schedules.settle', $schedule->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="date" name="settled_at" class="form-control form-control-sm" value="{{ optional($schedule->due_date)->format('Y-m-d') ?: now()->format('Y-m-d') }}">
                                            <button type="submit" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.loans.schedules.actions.settle_schedule') }}</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.loans.schedules.voucher_label', ['id' => $schedule->settled_voucher_id ?: '-']) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.loans.schedules.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
