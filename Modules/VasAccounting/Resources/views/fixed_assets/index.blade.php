@extends('layouts.app')

@section('title', __('vasaccounting::lang.fixed_assets'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.fixed_assets'),
        'subtitle' => 'Asset categories, capitalization, depreciation, transfer, and disposal workflows for VAS fixed assets.',
        'actions' => '<form method="POST" action="' . route('vasaccounting.assets.depreciation.run') . '">' . csrf_field() . '<button type="submit" class="btn btn-light-primary btn-sm">Run Depreciation</button></form>',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Assets</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['asset_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Active assets</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['active_assets'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Disposed assets</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['disposed_assets'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Net book value</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($summary['net_book_value'], 2) }} {{ $currency }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Create asset category</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.assets.categories.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Category name</label>
                            <input type="text" name="name" class="form-control" placeholder="Machinery" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Asset account</label>
                            <select name="asset_account_id" class="form-select" required>
                                <option value="">Select account</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Accumulated depreciation account</label>
                            <select name="accumulated_depreciation_account_id" class="form-select" required>
                                <option value="">Select account</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Depreciation expense account</label>
                            <select name="depreciation_expense_account_id" class="form-select" required>
                                <option value="">Select account</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Default useful life (months)</label>
                            <input type="number" min="1" name="default_useful_life_months" class="form-control" value="60" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save category</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Register fixed asset</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.assets.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Asset code</label>
                                <input type="text" name="asset_code" class="form-control" placeholder="TSCD-001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Asset name</label>
                                <input type="text" name="name" class="form-control" placeholder="Warehouse forklift" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="asset_category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Acquisition date</label>
                                <input type="date" name="acquisition_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Capitalization date</label>
                                <input type="date" name="capitalization_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <select name="business_location_id" class="form-select">
                                    <option value="">Select branch</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor</label>
                                <select name="vendor_contact_id" class="form-select">
                                    @foreach ($vendorOptions as $vendorId => $vendorLabel)
                                        <option value="{{ $vendorId }}">{{ $vendorLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Original cost</label>
                                <input type="number" step="0.0001" min="0" name="original_cost" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Salvage value</label>
                                <input type="number" step="0.0001" min="0" name="salvage_value" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Useful life (months)</label>
                                <input type="number" min="1" name="useful_life_months" class="form-control" placeholder="Leave blank to use category">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Acquisition note, serial number, or ownership details"></textarea>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">Register asset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">Asset register and lifecycle</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Asset</th>
                            <th>Category / Branch</th>
                            <th>Original cost</th>
                            <th>Accumulated depreciation</th>
                            <th>Net book value</th>
                            <th>Last depreciation</th>
                            <th>Status</th>
                            <th>Transfer</th>
                            <th>Dispose</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assetRows as $row)
                            @php($asset = $row['asset'])
                            <tr>
                                <td>
                                    <div class="text-gray-900 fw-semibold">{{ $asset->asset_code }}</div>
                                    <div class="text-muted fs-8">{{ $asset->name }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($asset->category)->name ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ optional($asset->businessLocation)->name ?: 'No branch linked' }}</div>
                                </td>
                                <td>{{ number_format((float) $asset->original_cost, 2) }}</td>
                                <td>{{ number_format((float) $row['accumulated_depreciation'], 2) }}</td>
                                <td>{{ number_format((float) $row['net_book_value'], 2) }}</td>
                                <td>{{ $row['last_depreciated_at'] ? \Illuminate\Support\Carbon::parse($row['last_depreciated_at'])->format('Y-m-d') : '-' }}</td>
                                <td><span class="badge {{ $asset->status === 'disposed' ? 'badge-light-danger' : 'badge-light-primary' }}">{{ ucfirst($asset->status) }}</span></td>
                                <td>
                                    @if ($asset->status !== 'disposed')
                                        <form method="POST" action="{{ route('vasaccounting.assets.transfer', $asset->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <select name="business_location_id" class="form-select form-select-sm">
                                                <option value="">Select branch</option>
                                                @foreach ($locationOptions as $locationId => $locationLabel)
                                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Transfer note">
                                            <button type="submit" class="btn btn-light-primary btn-sm">Transfer</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Disposed</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($asset->status !== 'disposed')
                                        <form method="POST" action="{{ route('vasaccounting.assets.dispose', $asset->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="date" name="disposed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Disposal note">
                                            <button type="submit" class="btn btn-light-danger btn-sm">Dispose</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">{{ optional($asset->disposed_at)->format('Y-m-d') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No fixed assets have been registered yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
