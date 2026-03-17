@extends('layouts.app')
@section('title', __('lang_v1.' . $type . 's'))
@php
    $api_key = env('GOOGLE_MAP_API_KEY');
@endphp
@if (!empty($api_key))
    @section('css')
        @include('contact.partials.google_map_styles')
    @endsection
@endif
@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="mb-5">
            <h1 class="mb-1 text-gray-900 fw-bolder fs-2x">@lang('lang_v1.' . $type . 's')</h1>
            <div class="text-muted fw-semibold fs-6">
                @lang('contact.manage_your_contact', ['contacts' => __('lang_v1.' . $type . 's')])
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <input type="hidden" value="{{ $type }}" id="contact_type">
        <div class="card card-flush">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" id="contact_table_search"
                            class="form-control form-control-solid w-250px ps-13"
                            placeholder="@lang('messages.search')">
                    </div>
                </div>

                <div class="card-toolbar">
                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" class="btn btn-light-primary" data-bs-toggle="collapse"
                            data-bs-target="#contact_table_filters_panel" aria-expanded="false"
                            aria-controls="contact_table_filters_panel">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            @lang('report.filters')
                        </button>

                        @if (auth()->user()->can('supplier.create') ||
                                auth()->user()->can('customer.create') ||
                                auth()->user()->can('supplier.view_own') ||
                                auth()->user()->can('customer.view_own'))
                            <a class="btn btn-primary btn-modal"
                                data-href="{{ action([\App\Http\Controllers\ContactController::class, 'create'], ['type' => $type]) }}"
                                data-container=".contact_modal">
                                <i class="ki-duotone ki-plus fs-2"></i>
                                @lang('messages.add')
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="collapse mb-10" id="contact_table_filters_panel">
                    <div class="separator separator-dashed mb-7"></div>
                    <div class="row g-5">
                        @if ($type == 'customer')
                            <div class="col-md-6 col-xl-3">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    {!! Form::checkbox('has_sell_due', 1, false, ['class' => 'form-check-input', 'id' => 'has_sell_due']) !!}
                                    {!! Form::label('has_sell_due', __('lang_v1.sell_due'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    {!! Form::checkbox('has_sell_return', 1, false, ['class' => 'form-check-input', 'id' => 'has_sell_return']) !!}
                                    {!! Form::label('has_sell_return', __('lang_v1.sell_return'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                                </div>
                            </div>
                        @elseif($type == 'supplier')
                            <div class="col-md-6 col-xl-3">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    {!! Form::checkbox('has_purchase_due', 1, false, ['class' => 'form-check-input', 'id' => 'has_purchase_due']) !!}
                                    {!! Form::label('has_purchase_due', __('report.purchase_due'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    {!! Form::checkbox('has_purchase_return', 1, false, ['class' => 'form-check-input', 'id' => 'has_purchase_return']) !!}
                                    {!! Form::label('has_purchase_return', __('lang_v1.purchase_return'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                                </div>
                            </div>
                        @endif

                        <div class="col-md-6 col-xl-3">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                {!! Form::checkbox('has_advance_balance', 1, false, ['class' => 'form-check-input', 'id' => 'has_advance_balance']) !!}
                                {!! Form::label('has_advance_balance', __('lang_v1.advance_balance'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                {!! Form::checkbox('has_opening_balance', 1, false, ['class' => 'form-check-input', 'id' => 'has_opening_balance']) !!}
                                {!! Form::label('has_opening_balance', __('lang_v1.opening_balance'), ['class' => 'form-check-label fw-semibold text-gray-700 ms-3']) !!}
                            </div>
                        </div>

                        @if ($type == 'customer')
                            <div class="col-md-6 col-xl-3">
                                {!! Form::label('has_no_sell_from', __('lang_v1.has_no_sell_from'), ['class' => 'form-label fw-semibold']) !!}
                                {!! Form::select(
                                    'has_no_sell_from',
                                    [
                                        'one_month' => __('lang_v1.one_month'),
                                        'three_months' => __('lang_v1.three_months'),
                                        'six_months' => __('lang_v1.six_months'),
                                        'one_year' => __('lang_v1.one_year'),
                                    ],
                                    null,
                                    ['class' => 'form-select form-select-solid', 'id' => 'has_no_sell_from', 'placeholder' => __('messages.please_select')],
                                ) !!}
                            </div>

                            <div class="col-md-6 col-xl-3">
                                {!! Form::label('cg_filter', __('lang_v1.customer_group'), ['class' => 'form-label fw-semibold']) !!}
                                {!! Form::select(
                                    'cg_filter',
                                    $customer_groups,
                                    null,
                                    ['class' => 'form-select form-select-solid', 'id' => 'cg_filter', 'placeholder' => __('lang_v1.all')],
                                ) !!}
                            </div>
                        @endif

                        @if (config('constants.enable_contact_assign') === true)
                            <div class="col-md-6 col-xl-3">
                                {!! Form::label('assigned_to', __('lang_v1.assigned_to'), ['class' => 'form-label fw-semibold']) !!}
                                {!! Form::select(
                                    'assigned_to',
                                    $users,
                                    null,
                                    ['class' => 'form-select form-select-solid', 'id' => 'assigned_to', 'placeholder' => __('lang_v1.all')],
                                ) !!}
                            </div>
                        @endif

                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('status_filter', __('sale.status'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::select(
                                'status_filter',
                                ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')],
                                null,
                                ['class' => 'form-select form-select-solid', 'id' => 'status_filter', 'placeholder' => __('lang_v1.none')],
                            ) !!}
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-7">
                        <button type="button" class="btn btn-light" id="contact_table_filters_reset">
                            @lang('messages.reset')
                        </button>
                    </div>
                </div>

                @if (auth()->user()->can('supplier.view') ||
                        auth()->user()->can('customer.view') ||
                        auth()->user()->can('supplier.view_own') ||
                        auth()->user()->can('customer.view_own'))
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="contact_table">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-125px">@lang('messages.action')</th>
                                    <th class="min-w-125px">@lang('lang_v1.contact_id')</th>
                                    @if ($type == 'supplier')
                                        <th class="min-w-150px">@lang('business.business_name')</th>
                                        <th class="min-w-150px">@lang('contact.name')</th>
                                        <th class="min-w-200px">@lang('business.email')</th>
                                        <th class="min-w-125px">@lang('contact.tax_no')</th>
                                        <th class="min-w-125px">@lang('contact.pay_term')</th>
                                        <th class="min-w-125px">@lang('account.opening_balance')</th>
                                        <th class="min-w-125px">@lang('lang_v1.advance_balance')</th>
                                        <th class="min-w-125px">@lang('lang_v1.added_on')</th>
                                        <th class="min-w-200px">@lang('business.address')</th>
                                        <th class="min-w-125px">@lang('contact.mobile')</th>
                                        <th class="min-w-150px">@lang('contact.total_purchase_due')</th>
                                        <th class="min-w-150px">@lang('lang_v1.total_purchase_return_due')</th>
                                    @elseif($type == 'customer')
                                        <th class="min-w-150px">@lang('business.business_name')</th>
                                        <th class="min-w-150px">@lang('user.name')</th>
                                        <th class="min-w-200px">@lang('business.email')</th>
                                        <th class="min-w-125px">@lang('contact.tax_no')</th>
                                        <th class="min-w-125px">@lang('lang_v1.credit_limit')</th>
                                        <th class="min-w-125px">@lang('contact.pay_term')</th>
                                        <th class="min-w-125px">@lang('account.opening_balance')</th>
                                        <th class="min-w-125px">@lang('lang_v1.advance_balance')</th>
                                        <th class="min-w-125px">@lang('lang_v1.added_on')</th>
                                        @if ($reward_enabled)
                                            <th class="min-w-125px" id="rp_col">{{ session('business.rp_name') }}</th>
                                        @endif
                                        <th class="min-w-150px">@lang('lang_v1.customer_group')</th>
                                        <th class="min-w-200px">@lang('business.address')</th>
                                        <th class="min-w-125px">@lang('contact.mobile')</th>
                                        <th class="min-w-150px">@lang('contact.total_sale_due')</th>
                                        <th class="min-w-150px">@lang('lang_v1.total_sell_return_due')</th>
                                    @endif
                                    @php
                                        $custom_labels = json_decode(session('business.custom_labels'), true);
                                    @endphp
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_1'] ?? __('lang_v1.contact_custom_field1') }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_2'] ?? __('lang_v1.contact_custom_field2') }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_3'] ?? __('lang_v1.contact_custom_field3') }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_4'] ?? __('lang_v1.contact_custom_field4') }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_5'] ?? __('lang_v1.custom_field', ['number' => 5]) }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_6'] ?? __('lang_v1.custom_field', ['number' => 6]) }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_7'] ?? __('lang_v1.custom_field', ['number' => 7]) }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_8'] ?? __('lang_v1.custom_field', ['number' => 8]) }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_9'] ?? __('lang_v1.custom_field', ['number' => 9]) }}
                                    </th>
                                    <th class="min-w-125px">
                                        {{ $custom_labels['contact']['custom_field_10'] ?? __('lang_v1.custom_field', ['number' => 10]) }}
                                    </th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="fw-bold text-gray-900 border-top border-gray-200">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td @if ($type == 'supplier') colspan="6"
                                @elseif($type == 'customer')
                                    @if ($reward_enabled)
                                        colspan="9"
                                    @else
                                        colspan="8" @endif
                                        @endif>
                                        @lang('sale.total'):
                                    </td>
                                    <td class="footer_contact_due"></td>
                                    <td class="footer_contact_return_due"></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>

        <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
        <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->
@stop
@section('javascript')
    @if (!empty($api_key))
        <script>
            // This example adds a search box to a map, using the Google Place Autocomplete
            // feature. People can enter geographical searches. The search box will return a
            // pick list containing a mix of places and predicted search terms.

            // This example requires the Places library. Include the libraries=places
            // parameter when you first load the API. For example:
            // <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

            function initAutocomplete() {
                var map = new google.maps.Map(document.getElementById('map'), {
                    center: {
                        lat: -33.8688,
                        lng: 151.2195
                    },
                    zoom: 10,
                    mapTypeId: 'roadmap'
                });

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                        map.setCenter(initialLocation);
                    });
                }


                // Create the search box and link it to the UI element.
                var input = document.getElementById('shipping_address');
                var searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

                // Bias the SearchBox results towards current map's viewport.
                map.addListener('bounds_changed', function() {
                    searchBox.setBounds(map.getBounds());
                });

                var markers = [];
                // Listen for the event fired when the user selects a prediction and retrieve
                // more details for that place.
                searchBox.addListener('places_changed', function() {
                    var places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    // Clear out the old markers.
                    markers.forEach(function(marker) {
                        marker.setMap(null);
                    });
                    markers = [];

                    // For each place, get the icon, name and location.
                    var bounds = new google.maps.LatLngBounds();
                    places.forEach(function(place) {
                        if (!place.geometry) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var icon = {
                            url: place.icon,
                            size: new google.maps.Size(71, 71),
                            origin: new google.maps.Point(0, 0),
                            anchor: new google.maps.Point(17, 34),
                            scaledSize: new google.maps.Size(25, 25)
                        };

                        // Create a marker for each place.
                        markers.push(new google.maps.Marker({
                            map: map,
                            icon: icon,
                            title: place.name,
                            position: place.geometry.location
                        }));

                        //set position field value
                        var lat_long = [place.geometry.location.lat(), place.geometry.location.lng()]
                        $('#position').val(lat_long);

                        if (place.geometry.viewport) {
                            // Only geocodes have viewport.
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $api_key }}&libraries=places" async defer></script>
        <script type="text/javascript">
            $(document).on('shown.bs.modal', '.contact_modal', function(e) {
                initAutocomplete();
            });
        </script>
    @endif
@endsection
