@extends('layouts.app')

@section('title', __('vasaccounting::lang.costing'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.costing'),
        'subtitle' => 'Maintain departments, cost centers, and projects so postings can accumulate against enterprise cost objects and project budgets.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Departments</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['departments'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Cost centers</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['cost_centers'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Projects</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['projects'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Dimensioned journal entries</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['dimensioned_entries'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100"><div class="card-header"><div class="card-title">Create department</div></div><div class="card-body">
                <form method="POST" action="{{ route('vasaccounting.costing.departments.store') }}">@csrf
                    <div class="mb-5"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="DPT-ADM" required></div>
                    <div class="mb-5"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="Administration" required></div>
                    <div class="mb-5"><label class="form-label">Branch</label><select name="business_location_id" class="form-select"><option value="">Select branch</option>@foreach ($locationOptions as $locationId => $locationLabel)<option value="{{ $locationId }}">{{ $locationLabel }}</option>@endforeach</select></div>
                    <button type="submit" class="btn btn-primary btn-sm">Save department</button>
                </form>
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100"><div class="card-header"><div class="card-title">Create cost center</div></div><div class="card-body">
                <form method="POST" action="{{ route('vasaccounting.costing.cost_centers.store') }}">@csrf
                    <div class="mb-5"><label class="form-label">Code</label><input type="text" name="code" class="form-control" placeholder="CC-001" required></div>
                    <div class="mb-5"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="Warehouse operations" required></div>
                    <div class="mb-5"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">Select department</option>@foreach ($departmentOptions as $departmentId => $departmentLabel)<option value="{{ $departmentId }}">{{ $departmentLabel }}</option>@endforeach</select></div>
                    <button type="submit" class="btn btn-primary btn-sm">Save cost center</button>
                </form>
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100"><div class="card-header"><div class="card-title">Create project</div></div><div class="card-body">
                <form method="POST" action="{{ route('vasaccounting.costing.projects.store') }}">@csrf
                    <div class="mb-5"><label class="form-label">Project code</label><input type="text" name="project_code" class="form-control" placeholder="PJ-001" required></div>
                    <div class="mb-5"><label class="form-label">Project name</label><input type="text" name="name" class="form-control" placeholder="Factory expansion" required></div>
                    <div class="mb-5"><label class="form-label">Customer / vendor</label><select name="contact_id" class="form-select"><option value="">Select contact</option>@foreach ($contactOptions as $contactId => $contactLabel)<option value="{{ $contactId }}">{{ $contactLabel }}</option>@endforeach</select></div>
                    <div class="mb-5"><label class="form-label">Cost center</label><select name="cost_center_id" class="form-select"><option value="">Select cost center</option>@foreach ($costCenterOptions as $costCenterId => $costCenterLabel)<option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>@endforeach</select></div>
                    <div class="row g-3 mb-5"><div class="col-md-6"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ now()->format('Y-m-d') }}"></div><div class="col-md-6"><label class="form-label">Budget</label><input type="number" step="0.0001" min="0" name="budget_amount" class="form-control" value="0"></div></div>
                    <button type="submit" class="btn btn-primary btn-sm">Save project</button>
                </form>
            </div></div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-4"><div class="card card-flush h-100"><div class="card-header"><div class="card-title">Departments</div></div><div class="card-body"><div class="table-responsive"><table class="table align-middle fs-7"><thead><tr><th>Department</th><th>Cost centers</th><th>Actual</th></tr></thead><tbody>@forelse ($departmentRows as $row)<tr><td><div class="text-gray-900 fw-semibold">{{ $row['department']->code }}</div><div class="text-muted fs-8">{{ $row['department']->name }}</div></td><td>{{ $row['cost_center_count'] }}</td><td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No departments.</td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-xl-4"><div class="card card-flush h-100"><div class="card-header"><div class="card-title">Cost centers</div></div><div class="card-body"><div class="table-responsive"><table class="table align-middle fs-7"><thead><tr><th>Cost center</th><th>Projects</th><th>Actual</th></tr></thead><tbody>@forelse ($costCenterRows as $row)<tr><td><div class="text-gray-900 fw-semibold">{{ $row['cost_center']->code }}</div><div class="text-muted fs-8">{{ $row['cost_center']->name }}</div></td><td>{{ $row['project_count'] }}</td><td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No cost centers.</td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-xl-4"><div class="card card-flush h-100"><div class="card-header"><div class="card-title">Projects</div></div><div class="card-body"><div class="table-responsive"><table class="table align-middle fs-7"><thead><tr><th>Project</th><th>Budget</th><th>Actual</th></tr></thead><tbody>@forelse ($projectRows as $row)<tr><td><div class="text-gray-900 fw-semibold">{{ $row['project']->project_code }}</div><div class="text-muted fs-8">{{ $row['project']->name }}</div></td><td>{{ number_format((float) $row['project']->budget_amount, 2) }} {{ $currency }}</td><td>{{ number_format((float) $row['actual_total'], 2) }} {{ $currency }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No projects.</td></tr>@endforelse</tbody></table></div></div></div></div>
    </div>
@endsection
