@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_manager'))

@section('content')

@if(session('status'))
<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
    {{ session('status.msg') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
</div>
@endif

<div class="row gx-6 gx-xl-9">
    {{-- Current Fabrics Status Card --}}
    <div class="col-lg-6 col-xxl-4">
        <div class="card h-100">
            <div class="card-body p-9">
                <div class="fs-2hx fw-bold">{{ $statusCounts['total'] }}</div>
                <a href="{{ route('projectx.products') }}" class="btn btn-primary btn-sm me-3">{{ __('projectx::lang.view_all_fabrics') }}</a>
                <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.current_fabrics') }}</div>
                <div class="d-flex flex-wrap">
                    <div class="d-flex flex-center h-100px w-100px me-9 mb-5">
                        <canvas id="kt_fabric_list_chart"
                            data-active="{{ $statusCounts['active'] }}"
                            data-draft="{{ $statusCounts['draft'] }}"
                            data-needs-approval="{{ $statusCounts['needs_approval'] }}"></canvas>
                    </div>
                    <div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5">
                        <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                            <div class="bullet bg-primary me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.active') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ $statusCounts['active'] }}</div>
                        </div>
                        <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                            <div class="bullet bg-warning me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.draft') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ $statusCounts['draft'] }}</div>
                        </div>
                        <div class="d-flex fs-6 fw-semibold align-items-center">
                            <div class="bullet bg-info me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.needs_approval') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ $statusCounts['needs_approval'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fabric Finance Card --}}
    <div class="col-lg-6 col-xxl-4">
        <div class="card h-100">
            <div class="card-body p-9">
                <div class="fs-2hx fw-bold">@format_currency((float) $financeMetrics['total_sale'])</div>
                <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.fabric_finance') }}</div>
                <div class="fs-6 d-flex justify-content-between mb-4">
                    <div class="fw-semibold">{{ __('projectx::lang.avg_sale_price') }}</div>
                    <div class="d-flex fw-bold">
                        <i class="ki-duotone ki-arrow-up-right fs-3 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>@format_currency((float) $financeMetrics['avg_sale_price'])
                    </div>
                </div>
                <div class="separator separator-dashed"></div>
                <div class="fs-6 d-flex justify-content-between my-4">
                    <div class="fw-semibold">{{ __('projectx::lang.avg_purchase_price') }}</div>
                    <div class="d-flex fw-bold">
                        <i class="ki-duotone ki-arrow-down-left fs-3 me-1 text-danger"><span class="path1"></span><span class="path2"></span></i>@format_currency((float) $financeMetrics['avg_purchase_price'])
                    </div>
                </div>
                <div class="separator separator-dashed"></div>
                <div class="fs-6 d-flex justify-content-between mt-4">
                    <div class="fw-semibold">{{ __('projectx::lang.fabric_margin') }}</div>
                    <div class="d-flex fw-bold">
                        @if($financeMetrics['margin'] >= 0)
                        <i class="ki-duotone ki-arrow-up-right fs-3 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>
                        @else
                        <i class="ki-duotone ki-arrow-down-left fs-3 me-1 text-danger"><span class="path1"></span><span class="path2"></span></i>
                        @endif
                        @format_currency((float) abs($financeMetrics['margin']))
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Suppliers Card --}}
    <div class="col-lg-6 col-xxl-4">
        <div class="card h-100">
            <div class="card-body p-9">
                <div class="fs-2hx fw-bold">{{ $supplierSnapshot['total_supplier_count'] }}</div>
                <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.our_suppliers') }}</div>
                <div class="symbol-group symbol-hover mb-9">
                    @foreach($supplierSnapshot['top_suppliers'] as $sup)
                    <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $sup->name }}">
                        <span class="symbol-label bg-light-primary text-primary fw-bold">{{ strtoupper(substr($sup->name, 0, 1)) }}</span>
                    </div>
                    @endforeach
                    @if($supplierSnapshot['total_supplier_count'] > 5)
                    <a href="#" class="symbol symbol-35px symbol-circle">
                        <span class="symbol-label bg-dark text-gray-300 fs-8 fw-bold">+{{ $supplierSnapshot['total_supplier_count'] - 5 }}</span>
                    </a>
                    @endif
                </div>
                <div class="d-flex">
                    @can('projectx.fabric.create')
                    <a href="{{ route('projectx.fabric_manager.create') }}" class="btn btn-primary btn-sm me-3">{{ __('projectx::lang.add_new_fabric') }}</a>
                    @endcan
                    <button type="button" class="btn btn-light btn-sm" id="btn_add_supplier">{{ __('projectx::lang.add_new_supplier') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Supplier Create Modal --}}
<div class="modal fade" id="supplier_create_modal" tabindex="-1" aria-hidden="true">
</div>

{{-- Fabric List Header + Filter --}}
<div class="d-flex flex-wrap flex-stack my-5">
    <h2 class="fs-2 fw-semibold my-2">{{ __('projectx::lang.fabrics') }}
        <span class="fs-6 text-gray-500 ms-1">{{ __('projectx::lang.by_status') }}</span>
    </h2>

    <div class="d-flex flex-wrap my-1">
        <form id="fabric_status_filter_form" method="GET" action="{{ route('projectx.fabric_manager.list') }}" class="m-0">
            <select name="status" id="fabric_status_filter" data-control="select2" data-hide-search="true" class="form-select form-select-sm form-select-solid fw-bold w-150px">
                <option value="all" {{ $status_filter === 'all' ? 'selected' : '' }}>{{ __('projectx::lang.all_statuses') }}</option>
                <option value="active" {{ $status_filter === 'active' ? 'selected' : '' }}>{{ __('projectx::lang.active') }}</option>
                <option value="draft" {{ $status_filter === 'draft' ? 'selected' : '' }}>{{ __('projectx::lang.draft') }}</option>
                <option value="needs_approval" {{ $status_filter === 'needs_approval' ? 'selected' : '' }}>{{ __('projectx::lang.needs_approval') }}</option>
            </select>
        </form>
    </div>
</div>

{{-- Fabric Cards Grid --}}
<div class="row g-6 g-xl-9">
    @forelse($fabrics as $fabric)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('projectx.fabric_manager.fabric', ['fabric_id' => $fabric->id]) }}" class="card border-hover-primary">
            <div class="card-header border-0 pt-9">
                <div class="card-title m-0">
                    <div class="symbol symbol-80px symbol-2by3 symbol-lg-150px mb-4 bg-light ">
                        @if($fabric->image_path)
                        <img src="{{ asset('storage/' . $fabric->image_path) }}" alt="{{ $fabric->name }}" class="p-3" />
                        @else
                        <span class="symbol-label bg-light-primary text-primary fs-lg fw-bold">{{ strtoupper(substr($fabric->name, 0, 2)) }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge {{ $fabric->badge_class }} fw-bold me-auto px-4 py-3">{{ $fabric->status_label }}</span>
                </div>
            </div>
            <div class="card-body p-9">
                <div class="fs-3 fw-bold text-gray-900">{{ $fabric->name }}</div>
                <div class="mt-1 mb-7">
                    <div class="d-flex flex-column" data-role="composition-legend">
                        @forelse(($fabric->compositionView['items'] ?? []) as $compositionItem)
                            <div class="d-flex fs-6 fw-semibold align-items-center {{ ! $loop->last ? 'mb-2' : '' }}">
                                <div class="bullet {{ $compositionItem['bullet_class'] ?? 'bg-gray-300' }} me-3"></div>
                                <div class="text-gray-500">{{ $compositionItem['label'] }}</div>
                                <div class="ms-auto fw-bold text-gray-700">{{ rtrim(rtrim(number_format((float) ($compositionItem['percent'] ?? 0), 2), '0'), '.') }}%</div>
                            </div>
                        @empty
                            <div class="d-flex fs-6 fw-semibold align-items-center">
                                <div class="bullet bg-gray-300 me-3"></div>
                                <div class="text-gray-500">{{ __('projectx::lang.no_compositions_added') }}</div>
                                <div class="ms-auto fw-bold text-gray-700">0%</div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="d-flex flex-wrap mb-5">
                    @if($fabric->weight_gsm !== null && $fabric->weight_gsm !== '')
                    <div class="border border-gray-300 border-dashed bg-warning rounded min-w-125px py-3 px-4 me-7 mb-3">
                        <div class="fs-3 text-gray-800 fw-bold">@num_format((float) $fabric->weight_gsm)</div>
                        <div class="fw-semibold text-white">{{ __('projectx::lang.weight_gsm') }}</div>
                    </div>
                    @endif
                    <div class="border border-gray-300 border-dashed bg-danger rounded min-w-125px py-3 px-4 mb-3">
                        <div class="fs-3 text-gray-800 fw-bold">@format_currency((float) $fabric->purchase_price)</div>
                        <div class="fw-semibold text-white">{{ __('projectx::lang.purchase_price') }}</div>
                    </div>
                </div>
                <div class="h-4px w-100 bg-light mb-5" data-bs-toggle="tooltip" title="{{ $fabric->progress_percent }}%">
                    <div class="{{ $fabric->progress_class }} rounded h-4px" role="progressbar" style="width: {{ $fabric->progress_percent }}%" aria-valuenow="{{ $fabric->progress_percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                @if($fabric->primarySupplier)
                    <div class="d-flex align-items-center">
                        <div class="symbol-group symbol-hover">
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $fabric->primarySupplier->name }}">
                                <span class="symbol-label bg-light-success text-success fw-bold">{{ strtoupper(substr($fabric->primarySupplier->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        @if((int) $fabric->supplierCount > 1)
                            <span class="badge badge-light-primary ms-2">+{{ ((int) $fabric->supplierCount) - 1 }}</span>
                        @endif
                    </div>
                @else
                    <div class="fs-7 fw-semibold text-gray-500">{{ __('projectx::lang.no_supplier_assigned') }}</div>
                @endif
                @if($fabric->pantoneItems->count() > 0)
                <div class="d-flex fs-6 fw-semibold align-items-center mb-2 text-gray-600">
                    {{ __('projectx::lang.pantone') }}:
                </div>
                <div class="symbol-group symbol-hover">
                    @foreach($fabric->pantoneItems as $pantoneItem)
                    <div class="symbol symbol-35px symbol-circle pl-6" data-bs-toggle="tooltip" title="{{ $pantoneItem->pantone_name }}">
                        <span class="symbol-label text-white fw-bold" style="background-color: {{ $pantoneItem->pantone_hex }};">{{ strtoupper(substr($pantoneItem->pantone_name, 0, 1)) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </a>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body p-9 text-center">
                <div class="text-gray-500 fs-4 fw-semibold mb-5">{{ __('projectx::lang.no_fabrics_yet') }}</div>
                @can('projectx.fabric.create')
                <a href="{{ route('projectx.fabric_manager.create') }}" class="btn btn-primary btn-sm">{{ __('projectx::lang.add_new_fabric') }}</a>
                @endcan
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection

@section('page_javascript')
<script src="{{ asset('modules/projectx/js/custom/apps/projects/list/list.js') }}"></script>
<script>
    function setContactPersonTypeState($modal, personType) {
        var isIndividual = personType === 'individual';

        $modal.find('div.individual').toggle(isIndividual);
        $modal.find('div.business').toggle(!isIndividual);
        syncVisibleRequiredFields($modal);
    }

    function initializeContactPersonType($modal) {
        var $typeSelect = $modal.find('select#contact_type');
        var isSupplierFlow = $typeSelect.length && $typeSelect.val() === 'supplier';
        var $selectedRadio = $modal.find('input[name="contact_type_radio"]:checked');

        if (!$selectedRadio.length) {
            var defaultType = isSupplierFlow ? 'business' : 'individual';
            $modal.find('input[name="contact_type_radio"][value="' + defaultType + '"]').prop('checked', true);
            $selectedRadio = $modal.find('input[name="contact_type_radio"]:checked');
        }

        setContactPersonTypeState($modal, $selectedRadio.val() || 'business');

        $modal.find('input[name="contact_type_radio"]')
            .off('change.projectx.person_type')
            .on('change.projectx.person_type', function() {
                setContactPersonTypeState($modal, this.value);
            });
    }

    function syncVisibleRequiredFields($scope) {
        $scope.find(':input').each(function() {
            var $field = $(this);
            var wasRequired = $field.data('was-required');

            if (typeof wasRequired === 'undefined') {
                $field.data('was-required', $field.prop('required') === true);
                wasRequired = $field.data('was-required');
            }

            if (!wasRequired) {
                return;
            }

            $field.prop('required', $field.is(':visible'));
        });
    }

    function enhanceSupplierCreateModal($modal) {
        var $dialog = $modal.find('.modal-dialog').first();
        var $content = $modal.find('.modal-content').first();
        var $header = $content.find('.modal-header').first();
        var $body = $content.find('.modal-body').first();
        var $footer = $content.find('.modal-footer').first();

        $dialog.removeClass('modal-lg modal-dialog-centered').addClass('modal-xl modal-dialog-scrollable');
        $content.addClass('border-0 shadow-sm rounded-3');

        $header.addClass('border-0 pb-0');
        $header.find('.modal-title').addClass('fw-bold fs-2 text-gray-900');

        $header.find('button.close').each(function() {
            $(this)
                .removeClass('close')
                .addClass('btn btn-sm btn-icon btn-active-color-primary')
                .html('<i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>');
        });

        $body.addClass('pt-7 pb-7 scroll-y').css({
            'max-height': 'calc(100vh - 220px)',
            'overflow-y': 'auto',
            '-webkit-overflow-scrolling': 'touch'
        });
        $body.find('.row').addClass('g-5');
        $body.find('.form-group').addClass('mb-8');
        $body.find('label').addClass('form-label fw-semibold text-gray-700 mb-2');
        $body.find('input.form-control, textarea.form-control').addClass('form-control-solid');
        $body.find('select.form-control').removeClass('form-control').addClass('form-select form-select-solid');
        $body.find('.input-group-addon').removeClass('input-group-addon').addClass('input-group-text bg-light border-0');
        $body.find('.help-block').addClass('fs-7 text-muted mt-2 mb-0');
        $body.find('.radio-inline').addClass('form-check form-check-inline form-check-custom form-check-solid');
        $body.find('.pull-left').removeClass('pull-left').addClass('float-start');
        $body.find('.col-md-offset-2').removeClass('col-md-offset-2').addClass('offset-md-2');

        $body.find('.more_btn')
            .removeClass('tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm center-block')
            .addClass('btn btn-sm btn-light-primary');

        $footer.addClass('border-0 pt-0');
        $footer.find('button[type="submit"]')
            .removeClass('tw-dw-btn tw-dw-btn-primary tw-text-white')
            .addClass('btn btn-primary');
        $footer.find('[data-dismiss="modal"]')
            .removeClass('tw-dw-btn tw-dw-btn-neutral tw-text-white')
            .addClass('btn btn-light');

        initializeContactPersonType($modal);
        syncVisibleRequiredFields($modal);
        $modal.find('input[name="contact_type_radio"], select#contact_type')
            .off('change.projectx.required')
            .on('change.projectx.required', function() {
                syncVisibleRequiredFields($modal);
            });
    }

    $(document).ready(function() {
        $(document).on('change select2:select', '#fabric_status_filter', function() {
            $('#fabric_status_filter_form').trigger('submit');
        });

        $('#btn_add_supplier').on('click', function() {
            $.ajax({
                url: '{{ action([\App\Http\Controllers\ContactController::class, "create"], ["type" => "supplier"]) }}',
                type: 'GET',
                success: function(html) {
                    var $supplierModal = $('#supplier_create_modal');
                    $supplierModal.html(html);
                    $supplierModal.appendTo('body');
                    enhanceSupplierCreateModal($supplierModal);

                    var modal = new bootstrap.Modal(document.getElementById('supplier_create_modal'));
                    modal.show();

                    // Bridge Bootstrap 4 data-dismiss to Bootstrap 5
                    $supplierModal.find('[data-dismiss="modal"]').on('click', function() {
                        modal.hide();
                    });

                    // Toggle "More Info" section
                    $supplierModal.find('.more_btn').on('click', function() {
                        var target = $($(this).data('target'));
                        target.toggleClass('hide d-none');
                        setTimeout(function() {
                            syncVisibleRequiredFields($supplierModal);
                        }, 0);
                    });

                    $supplierModal.find('select').each(function() {
                        if ($(this).data('control') === 'select2' || $(this).hasClass('select2')) {
                            $(this).select2({ dropdownParent: $('#supplier_create_modal') });
                        }
                    });

                    $supplierModal.off('hidden.bs.modal').on('hidden.bs.modal', function() {
                        $supplierModal.empty();
                    });
                }
            });
        });

        $(document).on('submit', '#contact_add_form', function(e) {
            e.preventDefault();
            var $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('supplier_create_modal'));
                        if (modal) modal.hide();
                        window.location.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: response.msg || @json(__('projectx::lang.error_generic')), confirmButtonColor: '#3085d6' });
                    }
                },
                error: function(xhr) {
                    var msg = @json(__('projectx::lang.something_went_wrong'));
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: msg, confirmButtonColor: '#3085d6' });
                }
            });
        });
    });
</script>
@endsection
