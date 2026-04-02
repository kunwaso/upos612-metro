@extends('layouts.app')

@section('title', __('vasaccounting::lang.tools'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tools'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
        'actions' => '<form method="POST" action="' . route('vasaccounting.tools.amortization.run') . '">' . csrf_field() . '<button type="submit" class="btn btn-primary btn-sm">' . $vasAccountingUtil->actionLabel('run_amortization') . '</button></form>',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tools.cards.tool_register') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['tool_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tools.cards.tool_register_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tools.cards.active_issued') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['active_tools']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tools.cards.active_issued_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tools.cards.remaining_value') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $summary['remaining_value'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tools.cards.due_this_cycle') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['due_this_month']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tools.cards.due_this_cycle_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tools.registration.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tools.registration.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.tools.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.tool_code') }}</label>
                                <input type="text" name="tool_code" class="form-control" placeholder="CCDC-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.tool_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.tools.registration.placeholders.tool_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active">{{ __('vasaccounting::lang.generic_statuses.active') }}</option>
                                    <option value="issued">{{ __('vasaccounting::lang.generic_statuses.issued') }}</option>
                                    <option value="draft">{{ __('vasaccounting::lang.generic_statuses.draft') }}</option>
                                    <option value="retired">{{ __('vasaccounting::lang.generic_statuses.retired') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.expense_account') }}</label>
                                <select name="expense_account_id" class="form-select" required data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.asset_account') }}</label>
                                <select name="asset_account_id" class="form-select" required data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.original_cost') }}</label>
                                <input type="number" step="0.0001" min="0" name="original_cost" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.remaining_value') }}</label>
                                <input type="number" step="0.0001" min="0" name="remaining_value" class="form-control" placeholder="{{ __('vasaccounting::lang.views.tools.registration.placeholders.remaining_value') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.amortization_months') }}</label>
                                <input type="number" min="1" name="amortization_months" class="form-control" value="12" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.tools.registration.fields.start_amortization') }}</label>
                                <input type="date" name="start_amortization_at" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.department') }}</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_department') }}</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentName)
                                        <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.cost_center') }}</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_cost_center') }}</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterName)
                                        <option value="{{ $costCenterId }}">{{ $costCenterName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.project') }}</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_project') }}</option>
                                    @foreach ($projectOptions as $projectId => $projectName)
                                        <option value="{{ $projectId }}">{{ $projectName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.tools.registration.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush mb-8">
                <div class="card-header">
                <div class="card-title d-flex flex-column">
                    <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tools.amortization_queue.title') }}</span>
                    <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tools.amortization_queue.subtitle') }}</span>
                </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-tools-amortization-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-tools-amortization-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.tool') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.next_run') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.next_amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.periods_posted') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scheduleRows as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ data_get($row, 'tool.tool_code') }} - {{ data_get($row, 'tool.name') }}</td>
                                        <td>{{ $row['next_run_date'] ?: '-' }}</td>
                                        <td>{{ number_format((float) $row['next_amount'], 2) }} {{ $currency }}</td>
                                        <td>{{ number_format((int) $row['periods_posted']) }}</td>
                                        <td>
                                            <span class="badge {{ $row['due_status'] === 'completed' ? 'badge-light-success' : ($row['due_status'] === 'overdue' ? 'badge-light-danger' : ($row['due_status'] === 'due_now' ? 'badge-light-warning' : 'badge-light-primary')) }}">
                                                {{ $vasAccountingUtil->dueStatusLabel((string) $row['due_status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.tools.amortization_queue.empty') }}</td>
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
                    <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tools.history.title') }}</span>
                    <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tools.history.subtitle') }}</span>
                </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-tools-history-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-tools-history-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.date') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.tool') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($amortizationHistory as $history)
                                    <tr>
                                        <td>{{ optional($history->amortization_date)->format('Y-m-d') }}</td>
                                        <td>{{ data_get($history, 'tool.tool_code', '-') }} - {{ data_get($history, 'tool.name', '-') }}</td>
                                        <td>{{ data_get($history, 'voucher.voucher_no', '-') }}</td>
                                        <td>{{ number_format((float) $history->amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->genericStatusLabel((string) $history->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.tools.history.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
                <div class="card-title d-flex flex-column">
                    <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tools.lifecycle.title') }}</span>
                    <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tools.lifecycle.subtitle') }}</span>
                </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-tools-lifecycle-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-tools-lifecycle-table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.shared.tool') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.branch') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.dimensions') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.original') }}</th>
                            <th>{{ __('vasaccounting::lang.views.tools.lifecycle.table.amortized') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.remaining') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.monthly') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($toolRows as $row)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ data_get($row, 'tool.tool_code') }} - {{ data_get($row, 'tool.name') }}</td>
                                <td>{{ data_get($row, 'tool.businessLocation.name', '-') }}</td>
                                <td>
                                    <div>{{ data_get($row, 'tool.department.name', __('vasaccounting::lang.views.tools.lifecycle.no_department')) }}</div>
                                    <div class="text-muted fs-8">
                                        {{ data_get($row, 'tool.costCenter.name', __('vasaccounting::lang.views.tools.lifecycle.no_cost_center')) }} |
                                        {{ data_get($row, 'tool.project.name', __('vasaccounting::lang.views.tools.lifecycle.no_project')) }}
                                    </div>
                                </td>
                                <td>{{ number_format((float) data_get($row, 'tool.original_cost', 0), 2) }}</td>
                                <td>{{ number_format((float) ($row['amortized_amount'] ?? 0), 2) }}</td>
                                <td>{{ number_format((float) data_get($row, 'tool.remaining_value', 0), 2) }}</td>
                                <td>{{ number_format((float) ($row['monthly_amount'] ?? 0), 2) }}</td>
                                <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->genericStatusLabel((string) data_get($row, 'tool.status', 'draft')) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted">{{ __('vasaccounting::lang.views.tools.lifecycle.empty') }}</td>
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
            const amortizationTable = window.VasWorkspace?.initLocalDataTable('#vas-tools-amortization-table', {
                order: [[1, 'asc']],
                pageLength: 10
            });
            const historyTable = window.VasWorkspace?.initLocalDataTable('#vas-tools-history-table', {
                order: [[0, 'desc']],
                pageLength: 10
            });
            const lifecycleTable = window.VasWorkspace?.initLocalDataTable('#vas-tools-lifecycle-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (amortizationTable) {
                $('#vas-tools-amortization-search').on('keyup', function () {
                    amortizationTable.search(this.value).draw();
                });
            }

            if (historyTable) {
                $('#vas-tools-history-search').on('keyup', function () {
                    historyTable.search(this.value).draw();
                });
            }

            if (lifecycleTable) {
                $('#vas-tools-lifecycle-search').on('keyup', function () {
                    lifecycleTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
