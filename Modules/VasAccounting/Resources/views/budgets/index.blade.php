@extends('layouts.app')

@section('title', __('vasaccounting::lang.budgets'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.budgets'),
        'subtitle' => 'Manage budget headers, planning lines, actual sync, and variance control in one workspace.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Budgets</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['budget_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">Registered budget headers.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Active</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['active_budgets']) }}</div>
                    <div class="text-muted fs-8 mt-1">Budgets in active or revised state.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Committed</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $varianceTotals['committed_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }} committed across all lines.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Over Budget</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['over_budget_lines']) }}</div>
                    <div class="text-muted fs-8 mt-1">Lines currently exceeding budget limits.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Create Budget Header</span>
                        <span class="text-muted fs-7">Define scope, dimensions, and planning period.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.budgets.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Budget code</label>
                                <input type="text" name="budget_code" class="form-control" placeholder="BUD-2026" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Budget name</label>
                                <input type="text" name="name" class="form-control" placeholder="Factory operations 2026" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">Select department</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                        <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cost center</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">Select cost center</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">Select project</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->startOfYear()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ now()->endOfYear()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">Save budget</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Create Budget Line</span>
                        <span class="text-muted fs-7">Map accounts and dimensions to budget and commitment amounts.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.budgets.lines.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Budget</label>
                                <select name="budget_id" class="form-select" required data-control="select2">
                                    <option value="">Select budget</option>
                                    @foreach ($budgetOptions as $budgetId => $budgetLabel)
                                        <option value="{{ $budgetId }}">{{ $budgetLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Account</label>
                                <select name="account_id" class="form-select" data-control="select2">
                                    <option value="">Select account</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">Use budget default</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                        <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cost center</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">Use budget default</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">Use budget default</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Budget amount</label>
                                <input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Committed amount</label>
                                <input type="number" step="0.0001" min="0" name="committed_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">Save budget line</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">Budget Register</span>
                <span class="text-muted fs-7">Header-level totals and quick actual-sync controls.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Budget</th>
                            <th>Dimension</th>
                            <th>Lines</th>
                            <th>Budget</th>
                            <th>Actual</th>
                            <th>Remaining</th>
                            <th>Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($budgetRows as $row)
                            @php($budget = $row['budget'])
                            <tr>
                                <td>
                                    <div class="fw-semibold text-gray-900">{{ $budget->budget_code }}</div>
                                    <div class="text-muted fs-8">{{ $budget->name }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($budget->department)->name ?: optional($budget->costCenter)->name ?: optional($budget->project)->name ?: 'General budget' }}</div>
                                    <div class="text-muted fs-8">{{ optional($budget->start_date)->format('Y-m-d') }} to {{ optional($budget->end_date)->format('Y-m-d') }}</div>
                                </td>
                                <td>{{ number_format((int) $row['line_count']) }}</td>
                                <td>{{ number_format((float) $row['budget_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_total'], 2) }} {{ $currency }}</td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.budgets.sync_actuals', $budget->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-light-primary btn-sm">Sync actuals</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No budgets have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">Variance Line Monitor</span>
                <span class="text-muted fs-7">
                    Remaining total: {{ number_format((float) $varianceTotals['remaining_total'], 2) }} {{ $currency }} |
                    Within budget lines: {{ number_format((int) $varianceTotals['within_budget_lines']) }}
                </span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Budget</th>
                            <th>Account / Dimension</th>
                            <th>Budget</th>
                            <th>Committed</th>
                            <th>Actual</th>
                            <th>Remaining</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($varianceRows as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-gray-900">{{ $row['budget_code'] }}</div>
                                    <div class="text-muted fs-8">{{ $row['budget_name'] }}</div>
                                </td>
                                <td>
                                    <div>{{ trim(($row['account_code'] ?: '') . ' ' . ($row['account_name'] ?: '')) ?: 'No account filter' }}</div>
                                    <div class="text-muted fs-8">{{ $row['department_name'] ?: $row['cost_center_name'] ?: $row['project_name'] ?: 'General' }}</div>
                                </td>
                                <td>{{ number_format((float) $row['budget_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['committed_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['actual_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_amount'], 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $row['is_over_budget'] ? 'badge-light-danger' : 'badge-light-success' }}">
                                        {{ $row['is_over_budget'] ? 'Over budget' : 'Within budget' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No budget lines are available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
