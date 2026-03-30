@extends('layouts.app')

@section('title', __('vasaccounting::lang.tools'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tools'),
        'subtitle' => 'Register tools and instruments, assign ownership dimensions, and run amortization with full ledger traceability.',
        'actions' => '<form method="POST" action="' . route('vasaccounting.tools.amortization.run') . '">' . csrf_field() . '<button type="submit" class="btn btn-primary btn-sm">Run Amortization</button></form>',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Tool Register</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['tool_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">Tracked assets in active scope</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Active / Issued</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['active_tools']) }}</div>
                    <div class="text-muted fs-8 mt-1">Operational tools with ownership</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Remaining Value</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $summary['remaining_value'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Due This Cycle</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['due_this_month']) }}</div>
                    <div class="text-muted fs-8 mt-1">Schedules requiring action</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">New Tool Registration</span>
                        <span class="text-muted fs-7">Create a tool profile and bind accounting dimensions.</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.tools.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">Tool code</label>
                                <input type="text" name="tool_code" class="form-control" placeholder="CCDC-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tool name</label>
                                <input type="text" name="name" class="form-control" placeholder="POS handheld" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <select name="business_location_id" class="form-select" data-control="select2">
                                    <option value="">Select branch</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="issued">Issued</option>
                                    <option value="draft">Draft</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expense account</label>
                                <select name="expense_account_id" class="form-select" required data-control="select2">
                                    <option value="">Select account</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asset account</label>
                                <select name="asset_account_id" class="form-select" required data-control="select2">
                                    <option value="">Select account</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Original cost</label>
                                <input type="number" step="0.0001" min="0" name="original_cost" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Remaining value</label>
                                <input type="number" step="0.0001" min="0" name="remaining_value" class="form-control" placeholder="Auto from original">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amortization months</label>
                                <input type="number" min="1" name="amortization_months" class="form-control" value="12" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start amortization</label>
                                <input type="date" name="start_amortization_at" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select" data-control="select2">
                                    <option value="">Select department</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentName)
                                        <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost center</label>
                                <select name="cost_center_id" class="form-select" data-control="select2">
                                    <option value="">Select cost center</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterName)
                                        <option value="{{ $costCenterId }}">{{ $costCenterName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" data-control="select2">
                                    <option value="">Select project</option>
                                    @foreach ($projectOptions as $projectId => $projectName)
                                        <option value="{{ $projectId }}">{{ $projectName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">Save tool</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush mb-8">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Amortization Queue</span>
                        <span class="text-muted fs-7">Upcoming runs and due-state control.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tool</th>
                                    <th>Next run</th>
                                    <th>Next amount</th>
                                    <th>Periods posted</th>
                                    <th>Status</th>
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
                                        <td colspan="5" class="text-muted">No amortization schedule is available for this scope.</td>
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
                        <span class="fw-bold text-gray-900">Recent Amortization History</span>
                        <span class="text-muted fs-7">Latest posted rows from the tool amortization cycle.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Date</th>
                                    <th>Tool</th>
                                    <th>Voucher</th>
                                    <th>Amount</th>
                                    <th>Status</th>
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
                                        <td colspan="5" class="text-muted">No amortization runs have been posted yet.</td>
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
                <span class="fw-bold text-gray-900">Tool Lifecycle Register</span>
                <span class="text-muted fs-7">Inventory of tools with accounting and operational dimensions.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Tool</th>
                            <th>Branch</th>
                            <th>Dimensions</th>
                            <th>Original</th>
                            <th>Amortized</th>
                            <th>Remaining</th>
                            <th>Monthly</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($toolRows as $row)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ data_get($row, 'tool.tool_code') }} - {{ data_get($row, 'tool.name') }}</td>
                                <td>{{ data_get($row, 'tool.businessLocation.name', '-') }}</td>
                                <td>
                                    <div>{{ data_get($row, 'tool.department.name', 'No department') }}</div>
                                    <div class="text-muted fs-8">
                                        {{ data_get($row, 'tool.costCenter.name', 'No cost center') }} |
                                        {{ data_get($row, 'tool.project.name', 'No project') }}
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
                                <td colspan="8" class="text-muted">No tools are available for the selected scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
