@extends('layouts.app')

@section('title', __('vasaccounting::lang.budgets'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.budgets'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.budgets.cards.budgets') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['budget_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.budgets.cards.budgets_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.budgets.cards.active') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['active_budgets']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.budgets.cards.active_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.budgets.cards.committed') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $varianceTotals['committed_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.budgets.cards.committed_help', ['currency' => $currency]) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.budgets.cards.over_budget') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['over_budget_lines']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.budgets.cards.over_budget_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.budgets.header_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.budgets.header_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.budgets.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.budget_code') }}</label>
                                <input type="text" name="budget_code" class="form-control" placeholder="BUD-2026" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.budget_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="Factory operations 2026" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.department') }}</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.header_form.selects.select_department') }}</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                        <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.cost_center') }}</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.header_form.selects.select_cost_center') }}</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.project') }}</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.header_form.selects.select_project') }}</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.start_date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->startOfYear()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.header_form.fields.end_date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ now()->endOfYear()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.budgets.header_form.actions.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.budgets.line_form.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.budgets.line_form.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.budgets.lines.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.budget') }}</label>
                                <select name="budget_id" class="form-select" required data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.line_form.selects.select_budget') }}</option>
                                    @foreach ($budgetOptions as $budgetId => $budgetLabel)
                                        <option value="{{ $budgetId }}">{{ $budgetLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.account') }}</label>
                                <select name="account_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.line_form.selects.select_account') }}</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.department') }}</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.line_form.selects.use_budget_default') }}</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                        <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.cost_center') }}</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.line_form.selects.use_budget_default') }}</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.project') }}</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.budgets.line_form.selects.use_budget_default') }}</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.budget_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.budgets.line_form.fields.committed_amount') }}</label>
                                <input type="number" step="0.0001" min="0" name="committed_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.budgets.line_form.actions.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.budgets.register.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.budgets.register.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-budgets-register-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-budgets-register-table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.budget') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.dimension') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.lines') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.budget_amount') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.actual') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.remaining') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.register.table.sync') }}</th>
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
                                    <div>{{ optional($budget->department)->name ?: optional($budget->costCenter)->name ?: optional($budget->project)->name ?: __('vasaccounting::lang.views.budgets.register.general_budget') }}</div>
                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.budgets.register.date_range', ['start' => optional($budget->start_date)->format('Y-m-d'), 'end' => optional($budget->end_date)->format('Y-m-d')]) }}</div>
                                </td>
                                <td>{{ number_format((int) $row['line_count']) }}</td>
                                <td>{{ number_format((float) $row['budget_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_total'], 2) }} {{ $currency }}</td>
                                <td>
                                    <form method="POST" action="{{ route('vasaccounting.budgets.sync_actuals', $budget->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.budgets.register.actions.sync_actuals') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.budgets.register.empty') }}</td>
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
                <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.budgets.variance.title') }}</span>
                <span class="text-muted fs-7">
                    {{ __('vasaccounting::lang.views.budgets.variance.subtitle', [
                        'total' => number_format((float) $varianceTotals['remaining_total'], 2),
                        'currency' => $currency,
                        'count' => number_format((int) $varianceTotals['within_budget_lines']),
                    ]) }}
                </span>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-budgets-variance-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-budgets-variance-table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.budget') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.account_dimension') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.budget_amount') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.committed') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.actual') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.remaining') }}</th>
                            <th>{{ __('vasaccounting::lang.views.budgets.variance.table.status') }}</th>
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
                                    <div>{{ trim(($row['account_code'] ?: '') . ' ' . ($row['account_name'] ?: '')) ?: __('vasaccounting::lang.views.budgets.variance.no_account_filter') }}</div>
                                    <div class="text-muted fs-8">{{ $row['department_name'] ?: $row['cost_center_name'] ?: $row['project_name'] ?: __('vasaccounting::lang.views.budgets.variance.general_dimension') }}</div>
                                </td>
                                <td>{{ number_format((float) $row['budget_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['committed_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['actual_amount'], 2) }} {{ $currency }}</td>
                                <td>{{ number_format((float) $row['remaining_amount'], 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="badge {{ $row['is_over_budget'] ? 'badge-light-danger' : 'badge-light-success' }}">
                                        {{ $row['is_over_budget'] ? __('vasaccounting::lang.views.budgets.variance.statuses.over_budget') : __('vasaccounting::lang.views.budgets.variance.statuses.within_budget') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.budgets.variance.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const budgetsRegisterTable = window.VasWorkspace?.initLocalDataTable('#vas-budgets-register-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (budgetsRegisterTable) {
                $('#vas-budgets-register-search').on('keyup', function () {
                    budgetsRegisterTable.search(this.value).draw();
                });
            }

            const budgetsVarianceTable = window.VasWorkspace?.initLocalDataTable('#vas-budgets-variance-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (budgetsVarianceTable) {
                $('#vas-budgets-variance-search').on('keyup', function () {
                    budgetsVarianceTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
