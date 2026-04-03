(function (window, document, $) {
    'use strict';

    if (!$ || !document.getElementById('kt_content')) {
        return;
    }

    var Dashboard = {
        charts: {},
        table4: null,
        table5: null,
        salesChartFilter: {
            range: 'month',
            startDate: null,
            endDate: null
        },
        tabColors: [
            ['bg-warning', 'text-inverse-warning'],
            ['bg-primary', 'text-inverse-primary'],
            ['bg-success', 'text-inverse-success'],
            ['bg-danger', 'text-inverse-danger'],
            ['bg-info', 'text-inverse-info'],
            ['bg-dark', 'text-inverse-dark']
        ],

        init: function () {
            this.initSalesChartFilter();
            this.bindSalesChartFilterEvents();
            var initialPayload = this.getInitialPayload();
            if (initialPayload && typeof initialPayload === 'object') {
                this.hydrate(initialPayload);
            }
            this.fetchAndHydrate();
        },

        fetchAndHydrate: function () {
            var endpoint = this.getEndpoint();
            if (!endpoint) {
                return;
            }

            var params = {};
            var locationId = this.getLocationId();
            if (locationId) {
                params.location_id = locationId;
            }
            var salesChartParams = this.getSalesChartParams();
            for (var key in salesChartParams) {
                if (Object.prototype.hasOwnProperty.call(salesChartParams, key)) {
                    params[key] = salesChartParams[key];
                }
            }

            var self = this;
            $.ajax({
                url: endpoint,
                method: 'GET',
                dataType: 'text',
                data: params
            })
                .done(function (responseText) {
                    var payload = self.parseDashboardPayload(responseText);
                    if (payload) {
                        self.hydrate(payload);
                        return;
                    }

                    self.reportHydrationFailure('invalid_payload', responseText);
                })
                .fail(function (jqXHR, textStatus) {
                    var payload = self.parseDashboardPayload(jqXHR ? jqXHR.responseText : null);
                    if (payload) {
                        self.hydrate(payload);
                        return;
                    }

                    self.reportHydrationFailure(textStatus || 'request_failed', jqXHR ? jqXHR.responseText : null);
                });
        },

        hydrate: function (payload) {
            var self = this;
            this.runHydrationStep('syncFilterFromPayload', function () { self.syncFilterFromPayload(payload); });
            this.runHydrationStep('applyExpectedEarnings', function () { self.applyExpectedEarnings(payload); });
            this.runHydrationStep('applyOrdersThisMonth', function () { self.applyOrdersThisMonth(payload); });
            this.runHydrationStep('applyAverageDailySales', function () { self.applyAverageDailySales(payload); });
            this.runHydrationStep('applyNewCustomers', function () { self.applyNewCustomers(payload); });
            this.runHydrationStep('applySalesThisMonth', function () { self.applySalesThisMonth(payload); });
            this.runHydrationStep('applyDiscountedSales', function () { self.applyDiscountedSales(payload); });
            this.runHydrationStep('applyRecentOrderTabs', function () { self.applyRecentOrderTabs(payload); });
            this.runHydrationStep('applyProductOrdersTable', function () { self.applyProductOrdersTable(payload); });
            this.runHydrationStep('applyDeliveryFeed', function () { self.applyDeliveryFeed(payload); });
            this.runHydrationStep('applyStockTable', function () { self.applyStockTable(payload); });
        },

        getEndpoint: function () {
            if (window.homeMetronicDashboardConfig && window.homeMetronicDashboardConfig.endpoint) {
                return window.homeMetronicDashboardConfig.endpoint;
            }

            return '/home/metronic-dashboard-data';
        },

        getInitialPayload: function () {
            if (window.homeMetronicDashboardConfig && window.homeMetronicDashboardConfig.initialPayload) {
                return window.homeMetronicDashboardConfig.initialPayload;
            }

            return null;
        },

        getLocationId: function () {
            var search = new URLSearchParams(window.location.search || '');
            var byQuery = search.get('location_id');
            if (byQuery && !isNaN(parseInt(byQuery, 10))) {
                return parseInt(byQuery, 10);
            }

            var selectors = ['#so_location', '#pending_shipments_location', '#po_location', '#pr_location'];
            for (var i = 0; i < selectors.length; i++) {
                var value = $(selectors[i]).val();
                var parsed = parseInt(value, 10);
                if (parsed > 0) {
                    return parsed;
                }
            }

            return null;
        },

        getGlobalFilterContainer: function () {
            return $('#dashboard-date-filter');
        },

        getWidgetCard: function (name) {
            return $('[data-dashboard-widget="' + name + '"]').first();
        },

        initSalesChartFilter: function () {
            var now = new Date();
            var monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
            this.salesChartFilter = {
                range: 'month',
                startDate: this.formatIsoDate(monthStart),
                endDate: this.formatIsoDate(now)
            };

            var initialPayload = this.getInitialPayload();
            var rangeMeta = (((initialPayload || {}).meta || {}).range) || {};
            if (rangeMeta.range) {
                this.salesChartFilter.range = String(rangeMeta.range);
            }
            if (rangeMeta.current_start) {
                this.salesChartFilter.startDate = String(rangeMeta.current_start);
            }
            if (rangeMeta.current_end) {
                this.salesChartFilter.endDate = String(rangeMeta.current_end);
            }

            this.updateSalesChartFilterUi(rangeMeta.label ? String(rangeMeta.label) : null);
            this.initCustomRangePicker();
        },

        initCustomRangePicker: function () {
            var $container = this.getGlobalFilterContainer();
            if (!$container.length || !$.fn.daterangepicker || typeof window.moment === 'undefined') {
                return;
            }

            var $input = $container.find('[data-sales-chart-custom-range]').first();
            if (!$input.length) {
                return;
            }

            var settings = $.extend(true, {}, window.dateRangeSettings || {});
            settings.autoUpdateInput = true;
            settings.startDate = this.salesChartFilter.startDate || this.formatIsoDate(new Date());
            settings.endDate = this.salesChartFilter.endDate || this.formatIsoDate(new Date());
            settings.locale = settings.locale || {};
            settings.locale.format = 'YYYY-MM-DD';

            $input.daterangepicker(settings);
            this.setCustomRangeInputValue($input, this.salesChartFilter.startDate, this.salesChartFilter.endDate);

            var self = this;
            $input.off('apply.daterangepicker.homeMetronicDashboard').on('apply.daterangepicker.homeMetronicDashboard', function (ev, picker) {
                var startDate = picker.startDate.format('YYYY-MM-DD');
                var endDate = picker.endDate.format('YYYY-MM-DD');
                $container.find('[data-sales-chart-start-date]').val(startDate);
                $container.find('[data-sales-chart-end-date]').val(endDate);
                self.setCustomRangeInputValue($input, startDate, endDate);
                self.salesChartFilter.range = 'custom';
                self.salesChartFilter.startDate = startDate;
                self.salesChartFilter.endDate = endDate;
                self.updateSalesChartFilterUi();
                self.fetchAndHydrate();
            });

            $input.off('cancel.daterangepicker.homeMetronicDashboard').on('cancel.daterangepicker.homeMetronicDashboard', function () {
                self.setCustomRangeInputValue($input, self.salesChartFilter.startDate, self.salesChartFilter.endDate);
            });
        },

        setCustomRangeInputValue: function ($input, startDate, endDate) {
            if (!$input || !$input.length) {
                return;
            }
            if (startDate && endDate) {
                $input.val(startDate + ' ~ ' + endDate);
            } else {
                $input.val('');
            }
        },

        updateSalesChartFilterUi: function (rangeLabel) {
            var $container = this.getGlobalFilterContainer();
            if (!$container.length) {
                return;
            }

            var label = rangeLabel || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate);

            $container.find('[data-sales-chart-start-date]').val(this.salesChartFilter.startDate || '');
            $container.find('[data-sales-chart-end-date]').val(this.salesChartFilter.endDate || '');
            $container.find('[data-sales-chart-range-label]').text(label);

            var $input = $container.find('[data-sales-chart-custom-range]').first();
            this.setCustomRangeInputValue($input, this.salesChartFilter.startDate, this.salesChartFilter.endDate);
            this.syncRangeLabelsFromFilter(label);
        },

        getRangeBounds: function (range) {
            var now = new Date();
            var start = new Date(now);
            var end = new Date(now);

            if (range === 'week') {
                var day = now.getDay();
                var diffToMonday = day === 0 ? -6 : 1 - day;
                start = new Date(now);
                start.setDate(now.getDate() + diffToMonday);
            } else if (range === 'month') {
                start = new Date(now.getFullYear(), now.getMonth(), 1);
            } else if (range === 'quarter') {
                var quarterStartMonth = Math.floor(now.getMonth() / 3) * 3;
                start = new Date(now.getFullYear(), quarterStartMonth, 1);
            } else if (range === 'year') {
                start = new Date(now.getFullYear(), 0, 1);
            }

            return {
                startDate: this.formatIsoDate(start),
                endDate: this.formatIsoDate(end)
            };
        },

        syncFilterFromPayload: function (payload) {
            var rangeMeta = (((payload || {}).meta || {}).range) || {};
            if (!rangeMeta.range) {
                return;
            }

            this.salesChartFilter.range = String(rangeMeta.range);
            this.salesChartFilter.startDate = rangeMeta.current_start ? String(rangeMeta.current_start) : this.salesChartFilter.startDate;
            this.salesChartFilter.endDate = rangeMeta.current_end ? String(rangeMeta.current_end) : this.salesChartFilter.endDate;
            this.updateSalesChartFilterUi(rangeMeta.label ? String(rangeMeta.label) : null);
        },

        formatIsoDate: function (dateObj) {
            if (!dateObj) {
                return '';
            }
            var year = dateObj.getFullYear();
            var month = dateObj.getMonth() + 1;
            var day = dateObj.getDate();
            month = month < 10 ? '0' + month : String(month);
            day = day < 10 ? '0' + day : String(day);
            return year + '-' + month + '-' + day;
        },

        tryParseJson: function (value) {
            if (!value || typeof value !== 'string') {
                return null;
            }

            try {
                return JSON.parse(value);
            } catch (e) {
                return null;
            }
        },

        parseDashboardPayload: function (payload) {
            if (payload && typeof payload === 'object') {
                return payload;
            }

            if (typeof payload !== 'string') {
                return null;
            }

            var source = payload.replace(/^\uFEFF/, '').trim();
            if (!source) {
                return null;
            }

            var parsed = this.tryParseJson(source);
            if (parsed && typeof parsed === 'object') {
                return parsed;
            }

            var firstBrace = source.indexOf('{');
            var lastBrace = source.lastIndexOf('}');
            if (firstBrace === -1 || lastBrace <= firstBrace) {
                return null;
            }

            parsed = this.tryParseJson(source.slice(firstBrace, lastBrace + 1));
            return parsed && typeof parsed === 'object' ? parsed : null;
        },

        reportHydrationFailure: function (reason, responseText) {
            if (window.console && typeof window.console.error === 'function') {
                window.console.error('Home dashboard refresh failed:', reason, responseText);
            }
        },

        runHydrationStep: function (stepName, callback) {
            try {
                callback();
            } catch (error) {
                if (window.console && typeof window.console.error === 'function') {
                    window.console.error('Home dashboard widget update failed:', stepName, error);
                }
            }
        },

        syncRangeLabelsFromFilter: function (rangeLabel) {
            var label = rangeLabel || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate);

            this.getWidgetCard('orders').find('[data-dashboard-orders-range-label]').first().text('Orders ' + label);
            this.getWidgetCard('average-daily-sales').find('[data-dashboard-average-range-label]').first().text('Average Daily Sales (' + label + ')');
            this.getWidgetCard('new-customers').find('[data-dashboard-new-customers-range-label]').first().text('New Customers ' + label);
            this.getWidgetCard('sales-chart').find('[data-sales-chart-card-range-label]').first().text(label);
            this.getWidgetCard('discounted-sales').find('[data-dashboard-discounted-header-range-label]').first().text('Across ' + label);
            this.getWidgetCard('discounted-sales').find('[data-dashboard-discounted-range-label]').first().text('Total Discounted Sales ' + label);
            this.getWidgetCard('delivery-feed').find('[data-dashboard-delivery-range-label]').first().text('Deliveries in ' + label);
        },

        getSalesChartParams: function () {
            var params = {
                sales_chart_range: this.salesChartFilter.range || 'month'
            };

            if (params.sales_chart_range === 'custom') {
                if (this.salesChartFilter.startDate && this.salesChartFilter.endDate) {
                    params.sales_chart_start_date = this.salesChartFilter.startDate;
                    params.sales_chart_end_date = this.salesChartFilter.endDate;
                } else {
                    params.sales_chart_range = 'month';
                }
            }

            return params;
        },

        getSalesChartRangeLabel: function (range, startDate, endDate) {
            if (range === 'week') {
                return 'This week';
            }
            if (range === 'month') {
                return 'This month';
            }
            if (range === 'quarter') {
                return 'This quarter';
            }
            if (range === 'year') {
                return 'This year';
            }
            if (range === 'custom' && startDate && endDate) {
                return 'Custom: ' + startDate + ' - ' + endDate;
            }
            return 'Current range';
        },

        bindSalesChartFilterEvents: function () {
            var self = this;
            var $container = this.getGlobalFilterContainer();
            if (!$container.length) {
                return;
            }

            $(document).off('click.homeMetronicSalesChartRange').on('click.homeMetronicSalesChartRange', '[data-sales-chart-range]', function (event) {
                event.preventDefault();
                var range = $(this).data('sales-chart-range');
                if (!range) {
                    return;
                }

                if (range === 'custom') {
                    self.salesChartFilter.range = 'custom';
                    self.updateSalesChartFilterUi();
                    return;
                }

                self.salesChartFilter.range = String(range);
                var bounds = self.getRangeBounds(String(range));
                self.salesChartFilter.startDate = bounds.startDate;
                self.salesChartFilter.endDate = bounds.endDate;
                self.updateSalesChartFilterUi();
                self.fetchAndHydrate();
            });

            $(document).off('click.homeMetronicSalesChartApplyCustom').on('click.homeMetronicSalesChartApplyCustom', '[data-sales-chart-apply-custom]', function (event) {
                event.preventDefault();
                var startDate = ($container.find('[data-sales-chart-start-date]').val() || '').trim();
                var endDate = ($container.find('[data-sales-chart-end-date]').val() || '').trim();
                var rawRange = ($container.find('[data-sales-chart-custom-range]').val() || '').trim();

                if ((!startDate || !endDate) && rawRange) {
                    var delimiter = rawRange.indexOf('~') > -1 ? '~' : ' - ';
                    var parts = rawRange.split(delimiter);
                    startDate = (parts[0] || '').trim();
                    endDate = (parts[1] || '').trim();
                }

                if (!startDate || !endDate) {
                    return;
                }
                if (startDate > endDate) {
                    return;
                }

                self.salesChartFilter.range = 'custom';
                self.salesChartFilter.startDate = startDate;
                self.salesChartFilter.endDate = endDate;
                self.updateSalesChartFilterUi();
                self.fetchAndHydrate();
            });
        },

        formatCurrency: function (value, showSymbol) {
            var amount = this.toNumber(value);
            if (typeof showSymbol === 'undefined') {
                showSymbol = true;
            }

            if (typeof window.__currency_trans_from_en === 'function') {
                return window.__currency_trans_from_en(amount, showSymbol);
            }

            try {
                return new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount);
            } catch (e) {
                return amount.toFixed(2);
            }
        },

        formatNumber: function (value, decimals) {
            var amount = this.toNumber(value);
            if (typeof decimals === 'undefined') {
                decimals = 0;
            }

            if (typeof window.__number_f === 'function') {
                try {
                    return window.__number_f(amount, false, false, decimals);
                } catch (e) {
                    // Fallback to Intl when shared currency globals are not ready.
                }
            }

            try {
                return new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(amount);
            } catch (e) {
                return amount.toFixed(decimals);
            }
        },

        formatPercent: function (value) {
            return this.formatNumber(Math.abs(this.toNumber(value)), 1) + '%';
        },

        formatCompactNumber: function (value, decimals) {
            var amount = this.toNumber(value);
            var abs = Math.abs(amount);
            var divisor = 1;
            var suffix = '';

            if (abs >= 1000000000000) {
                divisor = 1000000000000;
                suffix = 'T';
            } else if (abs >= 1000000000) {
                divisor = 1000000000;
                suffix = 'B';
            } else if (abs >= 1000000) {
                divisor = 1000000;
                suffix = 'M';
            } else if (abs >= 1000) {
                divisor = 1000;
                suffix = 'K';
            }

            if (divisor === 1) {
                return this.formatNumber(amount, typeof decimals === 'number' ? decimals : 0);
            }

            var compactValue = amount / divisor;
            var precision = typeof decimals === 'number' ? decimals : 2;
            var formatted = '';
            try {
                formatted = new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: precision
                }).format(compactValue);
            } catch (e) {
                formatted = compactValue.toFixed(precision);
            }

            return formatted + suffix;
        },

        formatDashboardPrimaryAmount: function (value) {
            var amount = this.toNumber(value);
            if (Math.abs(amount) >= 1000000) {
                return this.formatCompactNumber(amount, 2);
            }

            return this.formatNumber(amount, 0);
        },

        formatDashboardMoneyAmount: function (value) {
            var amount = this.toNumber(value);
            if (Math.abs(amount) >= 1000000) {
                return this.formatCompactNumber(amount, 2);
            }

            return this.formatCurrency(amount, false);
        },

        toNumber: function (value) {
            var parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        },

        escapeHtml: function (unsafe) {
            return String(unsafe || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        normalizeBadgeVariant: function (variant) {
            var allowed = ['primary', 'success', 'warning', 'danger', 'info', 'dark'];
            return allowed.indexOf(variant) >= 0 ? variant : 'primary';
        },

        normalizeOrderFilterStatus: function (variant) {
            var v = this.normalizeBadgeVariant(variant || '');
            if (v === 'success') {
                return 'Shipped';
            }
            if (v === 'danger') {
                return 'Rejected';
            }
            if (v === 'primary') {
                return 'Confirmed';
            }
            return 'Pending';
        },

        applyTrendBadge: function ($badge, deltaPercent, isPositive) {
            if (!$badge.length) {
                return;
            }

            var safeDelta = this.formatPercent(deltaPercent);
            var iconClass = isPositive ? 'ki-arrow-up text-success' : 'ki-arrow-down text-danger';
            var badgeClass = isPositive ? 'badge-light-success' : 'badge-light-danger';

            $badge.removeClass('badge-light-success badge-light-danger').addClass(badgeClass);
            $badge.html(
                '<i class="ki-duotone ' + iconClass + ' fs-5 ms-n1">' +
                '<span class="path1"></span><span class="path2"></span></i>' +
                safeDelta
            );
        },

        renderDonutChart: function (containerId, labels, series) {
            var el = document.getElementById(containerId);
            if (!el || typeof window.ApexCharts === 'undefined') {
                return;
            }

            this.destroyChart(containerId);
            el.innerHTML = '';

            var chart = new window.ApexCharts(el, {
                chart: {
                    type: 'donut',
                    height: 70,
                    width: 70,
                    sparkline: {
                        enabled: true
                    }
                },
                series: (series || []).map(function (value) {
                    return Math.max(parseFloat(value) || 0, 0);
                }),
                labels: labels || [],
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0
                },
                colors: ['#f1416c', '#009ef7', '#e4e6ef'],
                tooltip: {
                    y: {
                        formatter: function (value) {
                            return Dashboard.formatCurrency(value);
                        }
                    }
                }
            });

            chart.render();
            this.charts[containerId] = chart;
        },

        renderAreaChart: function (containerId, labels, series, color, height) {
            var el = document.getElementById(containerId);
            if (!el || typeof window.ApexCharts === 'undefined') {
                return;
            }

            this.destroyChart(containerId);
            el.innerHTML = '';

            var chartHeight = parseInt(height, 10);
            if (isNaN(chartHeight) || chartHeight <= 0) {
                chartHeight = 300;
            }

            var safeLabels = Array.isArray(labels) ? labels : [];
            var safeSeries = Array.isArray(series) ? series : [];
            var isCompactChart = chartHeight <= 120;
            var maxTicks = isCompactChart ? 0 : (chartHeight >= 260 ? 10 : 6);
            var tickAmount = maxTicks > 0 ? Math.min(maxTicks, Math.max(2, safeLabels.length)) : undefined;

            var chart = new window.ApexCharts(el, {
                chart: {
                    type: 'area',
                    height: chartHeight,
                    toolbar: {
                        show: false
                    },
                    sparkline: {
                        enabled: isCompactChart
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: isCompactChart ? 2 : 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                series: [{
                    name: 'Amount',
                    data: safeSeries
                }],
                xaxis: {
                    categories: safeLabels,
                    tickAmount: tickAmount,
                    labels: {
                        show: !isCompactChart,
                        rotate: -35,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        trim: true,
                        minHeight: 30,
                        maxHeight: 70,
                        style: {
                            fontSize: '10px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        show: !isCompactChart,
                        formatter: function (value) {
                            return Dashboard.formatCurrency(value, false);
                        }
                    }
                },
                colors: [color || '#009ef7'],
                tooltip: {
                    y: {
                        formatter: function (value) {
                            return Dashboard.formatCurrency(value);
                        }
                    }
                },
                grid: {
                    show: !isCompactChart,
                    borderColor: '#f1f1f2',
                    strokeDashArray: 4,
                    padding: {
                        left: isCompactChart ? 0 : 6,
                        right: isCompactChart ? 0 : 6
                    }
                }
            });

            chart.render();
            this.charts[containerId] = chart;
        },

        destroyChart: function (key) {
            if (this.charts[key] && typeof this.charts[key].destroy === 'function') {
                this.charts[key].destroy();
            }
            delete this.charts[key];
        },

        applyExpectedEarnings: function (payload) {
            var kpis = ((payload || {}).kpis) || {};
            var kpi = kpis.sales_summary || kpis.expected_earnings || {};
            var chartData = (((payload || {}).charts || {}).expected_earnings_breakdown) || {};
            var currency = ((((payload || {}).meta || {}).currency || {}).symbol) || null;
            var $card = this.getWidgetCard('sales-summary');
            if (!$card.length) {
                return;
            }

            if (currency) {
                $card.find('.card-header .fs-4.fw-semibold.text-gray-500').first().text(currency);
            }

            $card.find('.card-header .fs-2hx').first().text(this.formatDashboardPrimaryAmount(kpi.value || 0));
            this.applyTrendBadge(
                $card.find('.card-header .badge').first(),
                kpi.delta_percent || 0,
                !!kpi.is_positive_delta
            );

            var breakdown = Array.isArray(kpi.breakdown) ? kpi.breakdown : [];
            if (!breakdown.length && Array.isArray(chartData.labels) && Array.isArray(chartData.series)) {
                for (var j = 0; j < chartData.labels.length; j++) {
                    breakdown.push({
                        label: chartData.labels[j],
                        value: chartData.series[j] || 0
                    });
                }
            }
            var $rows = $card.find('.card-body .d-flex.fs-6.fw-semibold.align-items-center');
            for (var i = 0; i < Math.min($rows.length, 3); i++) {
                var row = breakdown[i] || {};
                var $row = $rows.eq(i);
                $row.find('.text-gray-500').first().text(row.label || '-');
                $row.find('.fw-bolder').first().html(this.formatDashboardMoneyAmount(row.value || 0));
            }

            var donutLabels = [];
            var donutSeries = [];
            for (var n = 0; n < breakdown.length; n++) {
                donutLabels.push((breakdown[n] || {}).label || '-');
                donutSeries.push((breakdown[n] || {}).value || 0);
            }

            this.renderDonutChart(
                'hm_dashboard_sales_summary_chart',
                donutLabels,
                donutSeries
            );
        },

        applyOrdersThisMonth: function (payload) {
            var kpi = (((payload || {}).kpis || {}).orders_this_month) || {};
            var $card = this.getWidgetCard('orders');
            if (!$card || !$card.length) {
                return;
            }

            $card.find('.card-header .fs-2hx').first().text(this.formatNumber(kpi.count || 0, 0));
            this.applyTrendBadge(
                $card.find('.card-header .badge').first(),
                kpi.delta_percent || 0,
                !!kpi.is_positive_delta
            );

            $card.find('.d-flex.justify-content-between .fw-bolder').first().text(this.formatNumber(kpi.remaining || 0, 0) + ' to Goal');
            $card.find('.d-flex.justify-content-between .fw-bold').first().text(this.formatPercent(kpi.progress_percent || 0));
            $card.find('.h-8px .bg-success').first().css('width', Math.max(0, Math.min(100, this.toNumber(kpi.progress_percent || 0))) + '%');
            $card.find('[data-dashboard-orders-range-label]').first().text('Orders ' + (kpi.range_label || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate)));
        },

        applyAverageDailySales: function (payload) {
            var kpi = (((payload || {}).kpis || {}).average_daily_sales) || {};
            var chartData = (((payload || {}).charts || {}).average_daily_sales) || {};
            var currency = ((((payload || {}).meta || {}).currency || {}).symbol) || null;

            var $card = this.getWidgetCard('average-daily-sales');
            if (!$card.length) {
                return;
            }

            if (currency) {
                $card.find('.card-header .fs-4.fw-semibold.text-gray-500').first().text(currency);
            }
            $card.find('.card-header .fs-2hx').first().text(this.formatDashboardPrimaryAmount(kpi.value || 0));
            this.applyTrendBadge(
                $card.find('.card-header .badge').first(),
                kpi.delta_percent || 0,
                !!kpi.is_positive_delta
            );
            $card.find('[data-dashboard-average-range-label]').first().text('Average Daily Sales (' + (kpi.range_label || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate)) + ')');

            this.renderAreaChart(
                'hm_dashboard_average_daily_chart',
                chartData.labels || [],
                chartData.series || [],
                '#50cd89',
                80
            );
        },

        applyNewCustomers: function (payload) {
            var kpi = (((payload || {}).kpis || {}).new_customers_this_month) || {};
            var heroes = kpi.heroes || [];
            var $card = this.getWidgetCard('new-customers');

            if (!$card || !$card.length) {
                return;
            }

            $card.find('.card-header .fs-2hx').first().text(this.formatNumber(kpi.count || 0, 0));
            $card.find('[data-dashboard-new-customers-range-label]').first().text('New Customers ' + (kpi.range_label || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate)));

            var html = '';
            for (var i = 0; i < heroes.length && i < 6; i++) {
                var hero = heroes[i] || {};
                var colors = this.tabColors[i % this.tabColors.length];
                html += '<div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="' + this.escapeHtml(hero.name || '') + '">' +
                    '<span class="symbol-label ' + colors[0] + ' ' + colors[1] + ' fw-bold">' + this.escapeHtml(hero.initials || 'NA') + '</span>' +
                    '</div>';
            }

            if ((heroes || []).length > 6) {
                html += '<a href="javascript:void(0)" class="symbol symbol-35px symbol-circle">' +
                    '<span class="symbol-label bg-light text-gray-400 fs-8 fw-bold">+' + this.formatNumber(heroes.length - 6, 0) + '</span>' +
                    '</a>';
            }

            var $group = $card.find('.symbol-group').first();
            if ($group.length) {
                $group.html(html);
            }
        },

        applySalesThisMonth: function (payload) {
            var kpi = (((payload || {}).kpis || {}).sales_this_month) || {};
            var chartData = (((payload || {}).charts || {}).sales_this_month) || {};
            var currency = ((((payload || {}).meta || {}).currency || {}).symbol) || null;
            var $card = this.getWidgetCard('sales-chart');

            if (!$card.length) {
                return;
            }

            if (currency) {
                $card.find('.px-9 .fs-4.fw-semibold.text-gray-500').first().text(currency);
            }
            $card.find('.px-9 .fs-2hx').first().text(this.formatDashboardPrimaryAmount(kpi.value || 0));
            $card.find('.px-9 .fs-6').first().html('Another ' + this.formatDashboardMoneyAmount(kpi.goal_gap || 0) + ' to Goal');
            var label = kpi.range_label || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate);
            $card.find('[data-sales-chart-card-range-label]').first().text(label);

            this.renderAreaChart(
                'hm_dashboard_sales_chart',
                chartData.labels || [],
                chartData.series || [],
                '#009ef7',
                300
            );
        },

        applyDiscountedSales: function (payload) {
            var kpi = (((payload || {}).kpis || {}).discounted_product_sales) || {};
            var chartData = (((payload || {}).charts || {}).discounted_product_sales) || {};
            var $card = this.getWidgetCard('discounted-sales');
            var rangeLabel = kpi.range_label || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate);

            if (!$card.length) {
                return;
            }

            $card.find('.px-9 .fs-2hx').first().text(this.formatDashboardPrimaryAmount(kpi.value || 0));
            this.applyTrendBadge(
                $card.find('.px-9 .badge').first(),
                kpi.delta_percent || 0,
                !!kpi.is_positive_delta
            );
            $card.find('[data-dashboard-discounted-header-range-label]').first().text('Across ' + rangeLabel);
            $card.find('[data-dashboard-discounted-range-label]').first().text('Total Discounted Sales ' + rangeLabel);

            this.renderAreaChart(
                'hm_dashboard_discounted_chart',
                chartData.labels || [],
                chartData.series || [],
                '#f1416c',
                300
            );
        },

        applyRecentOrderTabs: function (payload) {
            var tabs = (payload || {}).recent_orders_tabs || [];
            var $card = this.getWidgetCard('recent-orders');
            if (!$card.length) {
                return;
            }

            var $nav = $card.find('[data-dashboard-recent-tabs-nav]').first();
            var $content = $card.find('[data-dashboard-recent-tabs-content]').first();
            var $empty = $card.find('[data-dashboard-recent-tabs-empty]').first();
            var hasVisibleTab = false;

            for (var i = 1; i <= 5; i++) {
                var tab = tabs[i - 1] || {};
                var label = String(tab.label || '').trim();
                var items = Array.isArray(tab.items) ? tab.items : [];

                var $navItem = $card.find('[data-dashboard-recent-tab-nav-item="' + i + '"]').first();
                var $link = $navItem.find('a.nav-link').first();
                var $pane = $card.find('[data-dashboard-recent-tab-pane="' + i + '"]').first();

                if (!$navItem.length || !$pane.length) {
                    continue;
                }

                var isVisible = label !== '';
                $navItem.toggleClass('d-none', !isVisible);
                $pane.toggleClass('d-none', !isVisible);
                $link.find('.nav-text').first().text(label);

                if (isVisible && !hasVisibleTab) {
                    $link.addClass('active');
                    $pane.addClass('show active');
                    hasVisibleTab = true;
                } else {
                    $link.removeClass('active');
                    $pane.removeClass('show active');
                }

                var html = '';
                if (!items.length) {
                    html = '<tr><td colspan="5" class="text-center text-gray-500">No data</td></tr>';
                } else {
                    for (var n = 0; n < items.length; n++) {
                        var item = items[n] || {};
                        html += '<tr>' +
                            '<td><img src="' + this.escapeHtml(item.image_url || '') + '" class="w-50px ms-n1" alt="" /></td>' +
                            '<td class="ps-0">' +
                            '<a href="javascript:void(0)" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6 text-start pe-0">' + this.escapeHtml(item.product_name || '-') + '</a>' +
                            '<span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Item: #' + this.escapeHtml(item.item_code || '-') + '</span>' +
                            '</td>' +
                            '<td><span class="text-gray-800 fw-bold d-block fs-6 ps-0 text-end">x' + this.formatNumber(item.qty || 0, 0) + '</span></td>' +
                            '<td class="text-end pe-0"><span class="text-gray-800 fw-bold d-block fs-6">' + this.formatCurrency(item.price || 0) + '</span></td>' +
                            '<td class="text-end pe-0"><span class="text-gray-800 fw-bold d-block fs-6">' + this.formatCurrency(item.total_price || 0) + '</span></td>' +
                            '</tr>';
                    }
                }

                $pane.find('tbody').first().html(html);
            }

            $nav.toggleClass('d-none', !hasVisibleTab);
            $content.toggleClass('d-none', !hasVisibleTab);
            if ($empty.length) {
                $empty.toggleClass('d-none', hasVisibleTab);
            }
        },

        destroyDataTableIfExists: function ($table) {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                $table.DataTable().clear().destroy();
            }
        },

        applyProductOrdersTable: function (payload) {
            var rows = (payload || {}).product_orders || [];
            var $table = $('#hm_dashboard_product_orders_table');
            if (!$table.length || !$.fn.DataTable) {
                return;
            }

            this.destroyDataTableIfExists($table);
            $table.find('tbody').empty();

            var self = this;
            this.table4 = $table.DataTable({
                data: rows,
                paging: false,
                info: false,
                searching: true,
                ordering: false,
                fixedHeader: false,
                autoWidth: false,
                language: {
                    emptyTable: 'No data available'
                },
                columns: [
                    {
                        data: 'order_id',
                        render: function (data) {
                            var orderId = self.escapeHtml(data || '');
                            if (orderId && orderId.charAt(0) !== '#') {
                                orderId = '#' + orderId;
                            }
                            return '<span class="text-gray-800 text-hover-primary">' + orderId + '</span>';
                        }
                    },
                    {
                        data: 'created_at',
                        className: 'text-end'
                    },
                    {
                        data: 'customer_name',
                        className: 'text-end',
                        render: function (data) {
                            return '<span class="text-gray-600 text-hover-primary">' + self.escapeHtml(data || '') + '</span>';
                        }
                    },
                    {
                        data: 'total',
                        className: 'text-end',
                        render: function (value) {
                            return self.formatCurrency(value || 0);
                        }
                    },
                    {
                        data: 'profit',
                        className: 'text-end',
                        render: function (value) {
                            return '<span class="text-gray-800 fw-bolder">' + self.formatCurrency(value || 0) + '</span>';
                        }
                    },
                    {
                        data: 'status',
                        className: 'text-end',
                        render: function (data, type, row) {
                            var variant = self.normalizeBadgeVariant(row.status_variant || 'warning');
                            var filterValue = self.normalizeOrderFilterStatus(variant);
                            return '<span class="d-none">' + self.escapeHtml(filterValue) + '</span>' +
                                '<span class="badge py-3 px-4 fs-7 badge-light-' + variant + '">' + self.escapeHtml(data || '') + '</span>';
                        }
                    },
                    {
                        data: null,
                        className: 'text-end',
                        orderable: false,
                        searchable: false,
                        render: function () {
                            return '<button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-primary toggle h-25px w-25px" data-dashboard-product-orders="expand_row">' +
                                '<i class="ki-duotone ki-plus fs-4 m-0 toggle-off"></i>' +
                                '<i class="ki-duotone ki-minus fs-4 m-0 toggle-on d-none"></i>' +
                                '</button>';
                        }
                    }
                ]
            });

            var searchInput = $('[data-dashboard-product-orders="search"]');
            searchInput.off('keyup.homeMetronicDashboard').on('keyup.homeMetronicDashboard', function () {
                self.table4.search($(this).val()).draw();
            });

            var statusFilter = $('[data-dashboard-product-orders="filter_status"]');
            statusFilter.off('change.homeMetronicDashboard').on('change.homeMetronicDashboard', function () {
                var value = $(this).val();
                if (!value || value === 'Show All') {
                    self.table4.column(5).search('').draw();
                    return;
                }
                self.table4.column(5).search(value).draw();
            });

            $table.find('tbody').off('click.homeMetronicDashboardExpand').on('click.homeMetronicDashboardExpand', 'button[data-dashboard-product-orders="expand_row"]', function () {
                var tr = $(this).closest('tr');
                var row = self.table4.row(tr);
                var rowData = row.data() || {};

                var $plus = $(this).find('.toggle-off');
                var $minus = $(this).find('.toggle-on');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $plus.removeClass('d-none');
                    $minus.addClass('d-none');
                } else {
                    row.child(self.renderProductOrderLines(rowData.lines || [])).show();
                    tr.addClass('shown');
                    $plus.addClass('d-none');
                    $minus.removeClass('d-none');
                }
            });
        },

        renderProductOrderLines: function (lines) {
            if (!lines.length) {
                return '<div class="py-4 px-6 text-gray-500">No line items</div>';
            }

            var html = '<div class="p-4"><table class="table align-middle table-row-dashed fs-7 gy-3 mb-0">' +
                '<thead><tr class="text-gray-500 fw-bold text-uppercase">' +
                '<th>Item</th><th class="text-end">Cost</th><th class="text-end">Qty</th><th class="text-end">Total</th><th class="text-end">On hand</th>' +
                '</tr></thead><tbody>';

            for (var i = 0; i < lines.length; i++) {
                var line = lines[i] || {};
                html += '<tr>' +
                    '<td><div class="d-flex align-items-center gap-3">' +
                    '<span class="symbol symbol-40px bg-secondary bg-opacity-25 rounded"><img src="' + this.escapeHtml(line.image_url || '') + '" class="w-35px" alt="" /></span>' +
                    '<div class="d-flex flex-column text-muted">' +
                    '<span class="text-gray-800 fw-bold">' + this.escapeHtml(line.name || '-') + '</span>' +
                    '<span class="fs-7">' + this.escapeHtml(line.description || '') + '</span>' +
                    '</div></div></td>' +
                    '<td class="text-end">' + this.formatCurrency(line.cost || 0) + '</td>' +
                    '<td class="text-end">' + this.formatNumber(line.qty || 0, 0) + '</td>' +
                    '<td class="text-end">' + this.formatCurrency(line.total || 0) + '</td>' +
                    '<td class="text-end">' + this.formatNumber(line.stock || 0, 0) + '</td>' +
                    '</tr>';
            }

            html += '</tbody></table></div>';
            return html;
        },

        applyDeliveryFeed: function (payload) {
            var rows = (payload || {}).delivery_feed || [];
            var rangeLabel = ((((payload || {}).meta || {}).range || {}).label) || this.getSalesChartRangeLabel(this.salesChartFilter.range, this.salesChartFilter.startDate, this.salesChartFilter.endDate);
            var $card = this.getWidgetCard('delivery-feed');
            if (!$card || !$card.length) {
                return;
            }

            $card.find('[data-dashboard-delivery-range-label]').first().text('Deliveries in ' + rangeLabel);

            var $container = $card.find('.hover-scroll-overlay-y').first();
            if (!$container.length) {
                return;
            }

            var html = '';
            if (!rows.length) {
                html = '<div class="text-center text-gray-500 py-10">No data</div>';
            } else {
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i] || {};
                    var variant = this.normalizeBadgeVariant(row.status_variant || 'primary');
                    var marginClass = i === rows.length - 1 ? '' : ' mb-6';

                    html += '<div class="border border-dashed border-gray-300 rounded px-7 py-3' + marginClass + '">' +
                        '<div class="d-flex flex-stack mb-3">' +
                        '<div class="me-3">' +
                        '<img src="' + this.escapeHtml(row.image_url || '') + '" class="w-50px ms-n1 me-1" alt="" />' +
                        '<span class="text-gray-800 text-hover-primary fw-bold">' + this.escapeHtml(row.product_name || '-') + '</span>' +
                        '</div>' +
                        '<div class="m-0"></div>' +
                        '</div>' +
                        '<div class="d-flex flex-stack">' +
                        '<span class="text-gray-500 fw-bold">To: <span class="text-gray-800 text-hover-primary fw-bold">' + this.escapeHtml(row.recipient_name || '-') + '</span></span>' +
                        '<span class="badge badge-light-' + variant + '">' + this.escapeHtml(row.status || '-') + '</span>' +
                        '</div>' +
                        '</div>';
                }
            }

            $container.html(html);
        },

        applyStockTable: function (payload) {
            var rows = (payload || {}).stock_rows || [];
            var $table = $('#hm_dashboard_stock_table');
            if (!$table.length || !$.fn.DataTable) {
                return;
            }

            this.destroyDataTableIfExists($table);
            $table.find('tbody').empty();

            var self = this;
            this.table5 = $table.DataTable({
                data: rows,
                paging: false,
                info: false,
                searching: true,
                ordering: false,
                fixedHeader: false,
                autoWidth: false,
                language: {
                    emptyTable: 'No data available'
                },
                columns: [
                    {
                        data: 'item_name',
                        render: function (value) {
                            return '<span class="text-gray-900 text-hover-primary">' + self.escapeHtml(value || '-') + '</span>';
                        }
                    },
                    {
                        data: 'product_code',
                        className: 'text-end'
                    },
                    {
                        data: 'date_added',
                        className: 'text-end',
                        render: function (value) {
                            if (!value) {
                                return '-';
                            }
                            if (window.moment) {
                                return window.moment(value).format('DD MMM, YYYY');
                            }
                            return self.escapeHtml(value);
                        }
                    },
                    {
                        data: 'price',
                        className: 'text-end',
                        render: function (value) {
                            return self.formatCurrency(value || 0);
                        }
                    },
                    {
                        data: 'status_label',
                        className: 'text-end',
                        render: function (value, type, row) {
                            var variant = self.normalizeBadgeVariant(row.status_variant || 'primary');
                            return '<span class="d-none">' + self.escapeHtml(row.status || '') + '</span>' +
                                '<span class="badge py-3 px-4 fs-7 badge-light-' + variant + '">' + self.escapeHtml(value || '') + '</span>';
                        }
                    },
                    {
                        data: 'qty',
                        className: 'text-end',
                        render: function (value) {
                            return '<span class="text-gray-900 fw-bold">' + self.formatNumber(value || 0, 0) + ' PCS</span>';
                        }
                    }
                ]
            });

            var statusFilter = $('[data-dashboard-stock="filter_status"]');
            statusFilter.off('change.homeMetronicDashboard').on('change.homeMetronicDashboard', function () {
                var value = $(this).val();
                if (!value || value === 'Show All') {
                    self.table5.column(4).search('').draw();
                    return;
                }
                var map = {
                    'In Stock': 'in_stock',
                    'Out of Stock': 'out_of_stock',
                    'Low Stock': 'low_stock'
                };
                var normalized = map[value] || String(value).toLowerCase().replace(/\s+/g, '_');
                self.table5.column(4).search(normalized).draw();
            });
        }
    };

    $(function () {
        Dashboard.init();
    });
})(window, document, window.jQuery);
