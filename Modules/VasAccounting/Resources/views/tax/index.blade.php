@extends('layouts.app')

@section('title', __('vasaccounting::lang.tax'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tax'),
        'subtitle' => 'VAT books, tax-code activity, and declaration payload controls generated from posted journals.',
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Tax Codes</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $taxStats['tax_codes']) }}</div>
                    <div class="text-muted fs-8 mt-1">Configured in current business scope</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Summary Rows</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $taxStats['summary_rows']) }}</div>
                    <div class="text-muted fs-8 mt-1">Journal rollups by tax code</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Sales VAT</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $taxStats['sales_tax_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">Output VAT total</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Purchase VAT</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $taxStats['purchase_tax_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">Input VAT total</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">VAT Summary</span>
                        <span class="text-muted fs-7">Debit and credit totals grouped by mapped tax code.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tax code</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summaries as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row->code }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format((float) $row->total_debit, 2) }}</td>
                                        <td>{{ number_format((float) $row->total_credit, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No VAT journal rows found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Xuất dữ liệu kê khai</span>
                        <span class="text-muted fs-7">Tạo dữ liệu đầu ra theo nhà cung cấp để nộp tờ khai hoặc bàn giao sang hệ thống ngoài.</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.tax.export') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_export_provider') }}</label>
                            <select name="provider" class="form-select">
                                @foreach ($providerOptions as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}" @selected($providerKey === $defaultProvider)>{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Loại dữ liệu xuất</label>
                            <select name="export_type" class="form-select">
                                <option value="vat_declaration">Tờ khai thuế GTGT</option>
                                <option value="sales_book">Sổ thuế GTGT đầu ra</option>
                                <option value="purchase_book">Sổ thuế GTGT đầu vào</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Tạo dữ liệu xuất</button>
                    </form>

                    @if (session('tax_export_result'))
                        <div class="separator separator-dashed my-6"></div>
                        <div class="fw-bold text-gray-900 mb-2">Bản xem trước lần xuất gần nhất</div>
                        <div class="text-muted fs-7">Nhà cung cấp: {{ $vasAccountingUtil->providerLabel((string) session('tax_export_result.provider'), 'tax_export_adapters') }}</div>
                        <div class="text-muted fs-7">Loại dữ liệu: {{ session('tax_export_result.export_type') }}</div>
                        <div class="text-muted fs-7">Thời điểm tạo: {{ session('tax_export_result.generated_at') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Sổ thuế GTGT đầu ra</span>
                        <span class="text-muted fs-7">Dữ liệu thuế GTGT đầu ra được lấy từ chứng từ hóa đơn đã ghi sổ.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Chứng từ</th>
                                    <th>Khách hàng</th>
                                    <th>Mã thuế</th>
                                    <th>Số thuế</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesVatBook as $row)
                                    <tr>
                                        <td>{{ $row->voucher_no }}</td>
                                        <td>{{ $row->contact_name }}</td>
                                        <td>{{ $row->tax_code }}</td>
                                        <td>{{ number_format((float) $row->tax_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Chưa có phát sinh thuế GTGT đầu ra.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Sổ thuế GTGT đầu vào</span>
                        <span class="text-muted fs-7">Dữ liệu thuế GTGT đầu vào gắn với chứng từ mua hàng và chi phí.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Chứng từ</th>
                                    <th>Nhà cung cấp</th>
                                    <th>Mã thuế</th>
                                    <th>Số thuế</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseVatBook as $row)
                                    <tr>
                                        <td>{{ $row->voucher_no }}</td>
                                        <td>{{ $row->contact_name }}</td>
                                        <td>{{ $row->tax_code }}</td>
                                        <td>{{ number_format((float) $row->tax_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">Chưa có phát sinh thuế GTGT đầu vào.</td>
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
                <span class="fw-bold text-gray-900">Danh mục mã thuế</span>
                <span class="text-muted fs-7">Các mã thuế GTGT, thuế suất và chiều hạch toán đang được cấu hình.</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Mã</th>
                            <th>Tên</th>
                            <th>Thuế suất</th>
                            <th>Chiều hạch toán</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxCodes as $taxCode)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ $taxCode->code }}</td>
                                <td>{{ $taxCode->name }}</td>
                                <td>{{ number_format((float) $taxCode->rate, 2) }}%</td>
                                <td>{{ $vasAccountingUtil->genericStatusLabel((string) $taxCode->direction) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Chưa cấu hình mã thuế nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
