@extends('layouts.app')

@section('title', __('vasaccounting::lang.tools'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tools'),
        'subtitle' => 'Register tools and instruments, assign usage dimensions, and run periodic amortization from the VAS ledger.',
        'actions' => '<form method="POST" action="' . route('vasaccounting.tools.amortization.run') . '">' . csrf_field() . '<button type="submit" class="btn btn-light-primary btn-sm">Run Amortization</button></form>',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Registered tools</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['tool_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Active tools</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['active_tools'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Remaining value</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($summary['remaining_value'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Due this month</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['due_this_month'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Register tool</div>
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
                                <select name="business_location_id" class="form-select">
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
                                <select name="expense_account_id" class="form-select" required>
                                    <option value="">Select account</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asset account</label>
                                <select name="asset_account_id" class="form-select" required>
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
                                <input type="number" step="0.0001" min="0" name="remaining_value" class="form-control" placeholder="Leave blank to use original cost">
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
                                <select name="department_id" class="form-select">
                                    <option value="">Select department</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentName)
                                        <option value="{{ $departmentId }}">{{ $departmentName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost center</label>
                                <select name="cost_center_id" class="form-select">
                                    <option value="">Select cost center</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterName)
                                        <option value="{{ $costCenterId }}">{{ $costCenterName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select">
                                    <option value="">Select project</option>
                                    @foreach ($projectOptions as $projectId => $projectName)
                                        <option value="{{ $projectId }}">{{ $projectName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">Save tool</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">Upcoming amortization schedule</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tool</th>
                                    <th>Next run date</th>
                                    <th>Next amount</th>
                                    <th>Posted periods</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scheduleRows as $row)
                                    <tr>
                                        <td>{{ $row['tool']->tool_code }} - {{ $row['tool']->name }}</td>
                                        <td>{{ $row['next_run_date'] ?: '-' }}</td>
                                        <td>{{ number_format($row['next_amount'], 2) }} {{ $currency }}</td>
                                        <td>{{ $row['periods_posted'] }}</td>
                                        <td><span class="badge {{ $row['due_status'] === 'completed' ? 'badge-light-success' : ($row['due_status'] === 'overdue' ? 'badge-light-danger' : ($row['due_status'] === 'due_now' ? 'badge-light-warning' : 'badge-light-primary')) }}">{{ ucfirst(str_replace('_', ' ', $row['due_status'])) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No tool schedule is available yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Latest amortization history</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
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
                                        <td>{{ optional($history->tool)->tool_code }} - {{ optional($history->tool)->name }}</td>
                                        <td>{{ optional($history->voucher)->voucher_no ?: '-' }}</td>
                                        <td>{{ number_format((float) $history->amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-primary">{{ ucfirst($history->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No amortization runs posted yet.</td></tr>
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
            <div class="card-title">Tool register</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Tool</th>
                            <th>Branch</th>
                            <th>Dimensions</th>
                            <th>Original cost</th>
                            <th>Amortized</th>
                            <th>Remaining</th>
                            <th>Monthly amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($toolRows as $row)
                            <tr>
                                <td class="text-gray-900 fw-semibold">{{ $row['tool']->tool_code }} - {{ $row['tool']->name }}</td>
                                <td>{{ optional($row['tool']->businessLocation)->name ?: '-' }}</td>
                                <td>
                                    <div>{{ optional($row['tool']->department)->name ?: 'No department' }}</div>
                                    <div class="text-muted fs-8">{{ optional($row['tool']->costCenter)->name ?: 'No cost center' }} | {{ optional($row['tool']->project)->name ?: 'No project' }}</div>
                                </td>
                                <td>{{ number_format((float) $row['tool']->original_cost, 2) }}</td>
                                <td>{{ number_format((float) $row['amortized_amount'], 2) }}</td>
                                <td>{{ number_format((float) $row['tool']->remaining_value, 2) }}</td>
                                <td>{{ number_format((float) $row['monthly_amount'], 2) }}</td>
                                <td><span class="badge badge-light-primary">{{ ucfirst($row['tool']->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">No tools or instruments have been registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
