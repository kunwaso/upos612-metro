@extends('layouts.app')

@section('title', __('vasaccounting::lang.budgets'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.budgets'),
        'subtitle' => 'Maintain budget headers and line plans, sync actuals from posted journals, and review budget versus committed and actual balances.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Budgets</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['budget_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Active budgets</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['active_budgets'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Total budget</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($summary['total_budget'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Over-budget lines</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['over_budget_lines'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Create budget</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.budgets.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4"><label class="form-label">Budget code</label><input type="text" name="budget_code" class="form-control" placeholder="BUD-2026" required></div>
                            <div class="col-md-8"><label class="form-label">Budget name</label><input type="text" name="name" class="form-control" placeholder="Factory operations 2026" required></div>
                            <div class="col-md-4"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">Select department</option>@foreach ($departmentOptions as $departmentId => $departmentLabel)<option value="{{ $departmentId }}">{{ $departmentLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Cost center</label><select name="cost_center_id" class="form-select"><option value="">Select cost center</option>@foreach ($costCenterOptions as $costCenterId => $costCenterLabel)<option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" class="form-select"><option value="">Select project</option>@foreach ($projectOptions as $projectId => $projectLabel)<option value="{{ $projectId }}">{{ $projectLabel }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ now()->startOfYear()->format('Y-m-d') }}" required></div>
                            <div class="col-md-6"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="{{ now()->endOfYear()->format('Y-m-d') }}" required></div>
                        </div>
                        <div class="mt-6"><button type="submit" class="btn btn-primary btn-sm">Save budget</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Add budget line</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.budgets.lines.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4"><label class="form-label">Budget</label><select name="budget_id" class="form-select" required><option value="">Select budget</option>@foreach ($budgetOptions as $budgetId => $budgetLabel)<option value="{{ $budgetId }}">{{ $budgetLabel }}</option>@endforeach</select></div>
                            <div class="col-md-8"><label class="form-label">Account</label><select name="account_id" class="form-select"><option value="">Select account</option>@foreach ($chartOptions as $account)<option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">Use budget default</option>@foreach ($departmentOptions as $departmentId => $departmentLabel)<option value="{{ $departmentId }}">{{ $departmentLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Cost center</label><select name="cost_center_id" class="form-select"><option value="">Use budget default</option>@foreach ($costCenterOptions as $costCenterId => $costCenterLabel)<option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" class="form-select"><option value="">Use budget default</option>@foreach ($projectOptions as $projectId => $projectLabel)<option value="{{ $projectId }}">{{ $projectLabel }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Budget amount</label><input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0" required></div>
                            <div class="col-md-6"><label class="form-label">Committed amount</label><input type="number" step="0.0001" min="0" name="committed_amount" class="form-control" value="0"></div>
                        </div>
                        <div class="mt-6"><button type="submit" class="btn btn-primary btn-sm">Save budget line</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header"><div class="card-title">Budget register</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>Budget</th><th>Dimension</th><th>Line count</th><th>Budget</th><th>Actual</th><th>Remaining</th><th>Sync</th></tr></thead>
                    <tbody>
                        @forelse ($budgetRows as $row)
                            @php($budget = $row['budget'])
                            <tr>
                                <td><div class="text-gray-900 fw-semibold">{{ $budget->budget_code }}</div><div class="text-muted fs-8">{{ $budget->name }}</div></td>
                                <td><div>{{ optional($budget->department)->name ?: optional($budget->costCenter)->name ?: optional($budget->project)->name ?: 'General budget' }}</div><div class="text-muted fs-8">{{ optional($budget->start_date)->format('Y-m-d') }} to {{ optional($budget->end_date)->format('Y-m-d') }}</div></td>
                                <td>{{ $row['line_count'] }}</td>
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
                            <tr><td colspan="7" class="text-muted">No budgets have been created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header"><div class="card-title">Budget variance lines</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>Budget</th><th>Account / dimension</th><th>Budget</th><th>Committed</th><th>Actual</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($varianceRows as $row)
                            <tr>
                                <td><div class="text-gray-900 fw-semibold">{{ $row['budget_code'] }}</div><div class="text-muted fs-8">{{ $row['budget_name'] }}</div></td>
                                <td><div>{{ trim(($row['account_code'] ?: '') . ' ' . ($row['account_name'] ?: '')) ?: 'No account filter' }}</div><div class="text-muted fs-8">{{ $row['department_name'] ?: $row['cost_center_name'] ?: $row['project_name'] ?: 'General' }}</div></td>
                                <td>{{ number_format((float) $row['budget_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['committed_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['actual_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_amount'], 2) }} {{ $currency }}</td>
                                <td><span class="badge {{ $row['is_over_budget'] ? 'badge-light-danger' : 'badge-light-success' }}">{{ $row['is_over_budget'] ? 'Over budget' : 'Within budget' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">No budget lines are available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
