@extends('layouts.app')

@section('title', __('vasaccounting::lang.fixed_assets'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.fixed_assets'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
        'actions' => '<form method="POST" action="' . route('vasaccounting.assets.depreciation.run') . '">' . csrf_field() . '<button type="submit" class="btn btn-primary btn-sm">' . $vasAccountingUtil->actionLabel('run_depreciation') . '</button></form>',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.fixed_assets.cards.asset_register') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['asset_count']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.fixed_assets.cards.asset_register_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.fixed_assets.cards.active_assets') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['active_assets']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.fixed_assets.cards.active_assets_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.fixed_assets.cards.disposed') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['disposed_assets']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.fixed_assets.cards.disposed_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.fixed_assets.cards.net_book_value') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $summary['net_book_value'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.assets.categories.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.fields.category_name') }}</label>
                            <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.category_setup.placeholders.category_name') }}" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.fields.asset_account') }}</label>
                            <select name="asset_account_id" class="form-select" required data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.fields.accumulated_depreciation_account') }}</label>
                            <select name="accumulated_depreciation_account_id" class="form-select" required data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.fields.depreciation_expense_account') }}</label>
                            <select name="depreciation_expense_account_id" class="form-select" required data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.fields.default_useful_life') }}</label>
                            <input type="number" min="1" name="default_useful_life_months" class="form-control" value="60" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.fixed_assets.category_setup.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.fixed_assets.register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.fixed_assets.register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.assets.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.asset_code') }}</label>
                                <input type="text" name="asset_code" class="form-control" placeholder="TSCD-001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.asset_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.register.placeholder') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.category') }}</label>
                                <select name="asset_category_id" class="form-select" required data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.fixed_assets.register.select_category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.acquisition_date') }}</label>
                                <input type="date" name="acquisition_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.capitalization_date') }}</label>
                                <input type="date" name="capitalization_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.vendor') }}</label>
                                <select name="vendor_contact_id" class="form-select" data-control="select2">
                                    @foreach ($vendorOptions as $vendorId => $vendorLabel)
                                        <option value="{{ $vendorId }}">{{ $vendorLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active">{{ __('vasaccounting::lang.generic_statuses.active') }}</option>
                                    <option value="draft">{{ __('vasaccounting::lang.generic_statuses.draft') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.original_cost') }}</label>
                                <input type="number" step="0.0001" min="0" name="original_cost" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.salvage_value') }}</label>
                                <input type="number" step="0.0001" min="0" name="salvage_value" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.useful_life') }}</label>
                                <input type="number" min="1" name="useful_life_months" class="form-control" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.register.use_category_default') }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('vasaccounting::lang.views.fixed_assets.register.fields.notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.register.notes_placeholder') }}"></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-7">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.fixed_assets.register.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-fixed-assets-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-fixed-assets-table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.shared.asset') }}</th>
                            <th>{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.table.category_branch') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.original') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.accumulated') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.remaining') }}</th>
                            <th>{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.table.last_depreciation') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.table.transfer') }}</th>
                            <th>{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.table.dispose') }}</th>
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
                                    <div class="text-muted fs-8">{{ optional($asset->businessLocation)->name ?: __('vasaccounting::lang.views.fixed_assets.lifecycle.no_branch') }}</div>
                                </td>
                                <td>{{ number_format((float) $asset->original_cost, 2) }}</td>
                                <td>{{ number_format((float) $row['accumulated_depreciation'], 2) }}</td>
                                <td>{{ number_format((float) $row['net_book_value'], 2) }}</td>
                                <td>{{ $row['last_depreciated_at'] ? \Illuminate\Support\Carbon::parse($row['last_depreciated_at'])->format('Y-m-d') : '-' }}</td>
                                <td>
                                    <span class="badge {{ $asset->status === 'disposed' ? 'badge-light-danger' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $asset->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($asset->status !== 'disposed')
                                        <form method="POST" action="{{ route('vasaccounting.assets.transfer', $asset->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <select name="business_location_id" class="form-select form-select-sm">
                                                <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                                @foreach ($locationOptions as $locationId => $locationLabel)
                                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.transfer_note') }}">
                                            <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.actions.transfer') }}</button>
                                        </form>
                                    @else
                                        <span class="text-muted">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.disposed') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($asset->status !== 'disposed')
                                        <form method="POST" action="{{ route('vasaccounting.assets.dispose', $asset->id) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            <input type="date" name="disposed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.disposal_note') }}">
                                            <button type="submit" class="btn btn-light-danger btn-sm">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.actions.dispose') }}</button>
                                        </form>
                                    @else
                                        <div class="text-muted fs-8">{{ optional($asset->disposed_at)->format('Y-m-d') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted">{{ __('vasaccounting::lang.views.fixed_assets.lifecycle.empty') }}</td>
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
            const fixedAssetsTable = window.VasWorkspace?.initLocalDataTable('#vas-fixed-assets-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (fixedAssetsTable) {
                $('#vas-fixed-assets-search').on('keyup', function () {
                    fixedAssetsTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
