<script type="text/javascript">
    base_path = "{{ url('/') }}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{ config('broadcasting.connections.pusher.key') }}';
    APP.PUSHER_APP_CLUSTER = '{{ config('broadcasting.connections.pusher.options.cluster') }}';
    APP.INVOICE_SCHEME_SEPARATOR = '{{ config('constants.invoice_scheme_separator') }}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{ $__is_pusher_enabled }}';
    @auth
    @php
        $user = Auth::user();
    @endphp
    APP.USER_ID = "{{ $user->id }}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

@php
    $active_locale = $vasAccountingLocale ?? app()->getLocale();
@endphp

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->

<script src="{{ asset('assets/app/js/vendor.js?v=' . $asset_v) }}"></script>

@if (file_exists(public_path('assets/app/js/lang/' . $active_locale . '.js')))
    <script src="{{ asset('assets/app/js/lang/' . $active_locale . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('assets/app/js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);

    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);

    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if ($business_time_format == 12) {
        $moment_time_format = 'hh:mm A';
    }

    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries'])
        ? $common_settings['default_datatable_page_entries']
        : 25;
@endphp

<script>
    Dropzone.autoDiscover = false;
    moment.tz.setDefault('{{ Session::get('business.time_zone') }}');
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get('financial_year.start') }}'),
        end: moment('{{ Session::get('financial_year.end') }}'),
    }
    @if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . $active_locale . '.js')))
        //Default setting for select2
        $.fn.select2.defaults.set("language", "{{ $active_locale }}");
    @endif

    var datepicker_date_format = "{{ $datepicker_date_format }}";
    var moment_date_format = "{{ $moment_date_format }}";
    var moment_time_format = "{{ $moment_time_format }}";

    var app_locale = "{{ $active_locale }}";

    var non_utf8_languages = [
        @foreach (config('constants.non_utf8_languages') as $const)
            "{{ $const }}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{ $default_datatable_page_entries }}";

    var __new_notification_count_interval = "{{ config('constants.new_notification_count_interval', 60) }}000";
</script>

@if (file_exists(public_path('assets/app/js/lang/' . $active_locale . '.js')))
    <script src="{{ asset('assets/app/js/lang/' . $active_locale . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('assets/app/js/lang/en.js?v=' . $asset_v) }}"></script>
@endif

<script src="{{ asset('assets/app/js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('assets/app/js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('assets/app/js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('assets/app/js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('assets/app/js/documents_and_note.js?v=' . $asset_v) }}"></script>

<!-- TODO -->
@if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . $active_locale . '.js')))
    <script
        src="{{ asset('AdminLTE/plugins/select2/lang/' . $active_locale . '.js?v=' . $asset_v) }}">
    </script>
@endif
@php
    $validation_lang_file = 'messages_' . $active_locale . '.js';
@endphp
@if (file_exists(public_path() . '/assets/app/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('assets/app/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}">
    </script>
@endif

<script type="text/javascript">
    window.__datatable_show_export_buttons = @json($is_org_admin ?? false);

    if (
        typeof $ !== 'undefined' &&
        $.fn.dataTable &&
        $.fn.dataTable.defaults
    ) {
        if (!window.__datatable_show_export_buttons) {
            $.fn.dataTable.defaults.buttons = [];

            if ($.fn.dataTable.defaults.dom) {
                $.fn.dataTable.defaults.dom = $.fn.dataTable.defaults.dom.replace(/B/g, '');
            }
        } else if (!$.fn.dataTable.defaults.buttons || !$.fn.dataTable.defaults.buttons.length) {
            $.fn.dataTable.defaults.buttons = ['copy', 'excel', 'csv', 'pdf', 'print', 'colvis'];
        }

        if (
            window.__datatable_show_export_buttons &&
            $.fn.dataTable.defaults.dom &&
            $.fn.dataTable.defaults.dom.indexOf('B') === -1
        ) {
            $.fn.dataTable.defaults.dom = 'B' + $.fn.dataTable.defaults.dom;
        }
    }
</script>

@if (!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')
@stack('scripts')

@if (Module::has('Essentials'))
    @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready(function() {
        var locale = "{{ $active_locale }}";
        var isRTL =
            @if (in_array($active_locale, config('constants.langs_rtl')))
                true;
            @else
                false;
            @endif

        if ($('#calendar').length && typeof $.fn.fullCalendar === 'function' && $('#calendar').data('fullCalendar')) {
            $('#calendar').fullCalendar('option', {
                locale: locale,
                isRTL: isRTL
            });
        }
        // side bar toggle  
        $(".drop_down").click(function(event) {
            event.preventDefault();
            var $chiled = $(this).next(".chiled");
            var svgElement = $(this).find(".svg");
            $(".chiled").not($chiled).slideUp();
            $chiled.slideToggle(function() {
                $(".svg").each(function() {
                    var $currentSvgElement = $(this);
                    if ($currentSvgElement.closest(".drop_down").next(".chiled").is(
                            ":visible")) {
                        // If the corresponding menu is visible, set the arrow pointing upwards
                        $currentSvgElement.html(
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M6 9l6 6l6 -6" />'
                        );
                    } else {
                        // Otherwise, set the arrow pointing downwards
                        $currentSvgElement.html(
                            '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" />'
                        );
                    }
                });
            });
        });

        $('.small-view-button').on('click', function() {
            $('.side-bar').addClass('small-view-side-active');
            $('.overlay').fadeIn('slow');
        });

        $('.overlay').on('click', function() {
            $('.overlay').fadeOut('slow');
            $('.side-bar').removeClass('small-view-side-active');
        });

        $(window).on('resize', function() {
            if ($(window).width() >= 992) {
                $('.overlay').fadeOut('slow');
                $('.side-bar').removeClass('small-view-side-active');
            }

            if($('.side-bar').hasClass('small-view-side-active')){
                $('.overlay').fadeIn('slow');
            }
        });

        var popoverSelector = '[data-toggle="popover"], [data-bs-toggle="popover"]';

        var getPopoverAttribute = function($element, name) {
            var legacyValue = $element.attr('data-' + name);

            if (typeof legacyValue !== 'undefined') {
                return legacyValue;
            }

            return $element.attr('data-bs-' + name);
        };

        var parsePopoverBoolean = function(value, fallback) {
            if (typeof value === 'undefined' || value === null || value === '') {
                return fallback;
            }

            if (typeof value === 'boolean') {
                return value;
            }

            return value === 'true';
        };

        var normalizePopoverPlacement = function(value) {
            if (typeof value === 'undefined' || value === null || value === '') {
                return 'right';
            }

            var normalizedValue = value.toString().trim().toLowerCase();
            var validPlacements = [
                'auto',
                'auto-start',
                'auto-end',
                'top',
                'top-start',
                'top-end',
                'bottom',
                'bottom-start',
                'bottom-end',
                'left',
                'left-start',
                'left-end',
                'right',
                'right-start',
                'right-end'
            ];

            if (validPlacements.indexOf(normalizedValue) !== -1) {
                return normalizedValue;
            }

            var placementParts = normalizedValue.split(/\s+/);
            var nonAutoPart = placementParts.filter(function(part) {
                return part !== 'auto';
            })[0];

            if (nonAutoPart && validPlacements.indexOf(nonAutoPart) !== -1) {
                return nonAutoPart;
            }

            return placementParts[0] || 'right';
        };

        var getPopoverConfig = function(element) {
            var $element = $(element);
            var html = parsePopoverBoolean(getPopoverAttribute($element, 'html'), false);
            var container = getPopoverAttribute($element, 'container');
            var content = getPopoverAttribute($element, 'content');
            var placement = getPopoverAttribute($element, 'placement');
            var trigger = getPopoverAttribute($element, 'trigger');
            var title = $element.attr('data-original-title')
                || $element.attr('data-bs-original-title')
                || $element.attr('title')
                || '';

            return {
                container: container || false,
                content: typeof content === 'undefined' ? '' : content,
                html: html,
                placement: normalizePopoverPlacement(placement),
                sanitize: !html,
                title: title,
                trigger: trigger || 'click'
            };
        };

        window.__init_popovers = function(scope) {
            if (typeof $ === 'undefined' || typeof $.fn.popover !== 'function') {
                return;
            }

            var $scope = scope ? $(scope) : $(document);
            var $targets = $scope.find(popoverSelector);

            if ($scope.is(popoverSelector)) {
                $targets = $targets.add($scope);
            }

            $targets.each(function() {
                if (!$(this).data('bs.popover')) {
                    $(this).popover(getPopoverConfig(this));
                }
            });
        };

        window.__init_popovers(document);

        $(document)
            .off('shown.bs.modal.popoverInit')
            .on('shown.bs.modal.popoverInit', '.modal', function() {
                window.__init_popovers(this);
            });

        $(document)
            .off('click.popoverHide')
            .on('click.popoverHide', function(e) {
                $(popoverSelector).each(function() {
                    var $trigger = $(this);
                    var isTriggerClick = $trigger.is(e.target) || $trigger.has(e.target).length > 0;
                    var isPopoverClick = $('.popover').has(e.target).length > 0;

                    if (!isTriggerClick && !isPopoverClick) {
                        $trigger.popover('hide');
                    }
                });
            });

        $('.side-bar-collapse').click(function() {
            $('.side-bar').toggle('slow');
        });

        window.__normalize_datatable_buttons = function(scope) {
            var $scope = scope ? $(scope) : $(document);
            var $containers = $scope.find('.dt-buttons.btn-group');

            if ($scope.hasClass('dt-buttons') && $scope.hasClass('btn-group')) {
                $containers = $containers.add($scope);
            }

            $containers.addClass('flex-wrap gap-2');

            var $buttons = $scope.find('.dt-buttons .dt-button, .dt-buttons .btn');

            if ($scope.hasClass('dt-button') || $scope.hasClass('btn')) {
                $buttons = $buttons.add($scope);
            }

            $buttons.each(function() {
                $(this)
                    .removeClass(
                        'btn-default btn-secondary btn-primary btn-info btn-success btn-warning btn-danger tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-my-2'
                    )
                    .addClass('btn btn-sm btn-outline btn-outline-dashed btn-outline-primary');
            });
        };

        window.__normalize_datatable_buttons(document);

        $(document)
            .off('init.dt.metronicButtons draw.dt.metronicButtons')
            .on('init.dt.metronicButtons draw.dt.metronicButtons', function(e, settings) {
                if (settings && settings.nTableWrapper) {
                    window.__normalize_datatable_buttons(settings.nTableWrapper);
                    return;
                }

                window.__normalize_datatable_buttons(document);
            });
        
        // $('.date_range').on('show.daterangepicker', function (ev, picker) {
        //     $(picker.container).insertAfter($(this));
        // });
   
    });
</script>
