<script>
    (function (window, $) {
        if (!window || !$) {
            return;
        }

        const chartRoots = {};
        const messages = {
            noKpiData: @json(__('vasaccounting::lang.views.shared.no_data_body')),
            noFailures: @json(__('vasaccounting::lang.views.dashboard.operations_board.empty')),
            chartUnavailable: @json(__('vasaccounting::lang.views.shared.chart_unavailable')),
        };

        const escapeHtml = function (value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        };

        const formatNumber = function (value) {
            const num = Number(value ?? 0);
            if (!Number.isFinite(num)) {
                return '0';
            }

            return num.toLocaleString();
        };

        const formatDecimal = function (value) {
            const num = Number(value ?? 0);
            if (!Number.isFinite(num)) {
                return '0.00';
            }

            return num.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        };

        const initLocalDataTable = function (selector, options) {
            if (typeof $.fn.DataTable !== 'function') {
                return null;
            }

            const $table = $(selector);
            if ($table.length === 0) {
                return null;
            }

            if ($.fn.DataTable.isDataTable(selector)) {
                return $table.DataTable();
            }

            return $table.DataTable($.extend(true, {
                order: [],
                pageLength: 10,
                responsive: true,
                autoWidth: false,
                dom: "<'row align-items-center'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
                    "<'table-responsive'tr>" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }, options || {}));
        };

        const initAjaxDataTable = function (selector, ajaxUrl, columns, options) {
            if (typeof $.fn.DataTable !== 'function') {
                return null;
            }

            const $table = $(selector);
            if ($table.length === 0) {
                return null;
            }

            if ($.fn.DataTable.isDataTable(selector)) {
                const existing = $table.DataTable();
                existing.ajax.url(ajaxUrl).load();
                return existing;
            }

            return $table.DataTable($.extend(true, {
                processing: true,
                serverSide: true,
                deferRender: true,
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                ajax: {
                    url: ajaxUrl,
                    type: 'GET',
                },
                columns: columns || [],
                dom: "<'row align-items-center'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
                    "<'table-responsive'tr>" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            }, options || {}));
        };

        const renderKpiStrip = function (targetSelector, payload) {
            const cards = Array.isArray(payload?.cards) ? payload.cards : [];
            const $target = $(targetSelector);
            if ($target.length === 0) {
                return;
            }

            if (cards.length === 0) {
                $target.html(
                    '<div class="col-12"><div class="alert alert-light-warning mb-0">' + escapeHtml(messages.noKpiData) + '</div></div>'
                );
                return;
            }

            const html = cards.map(function (card) {
                const direction = String(card.direction || 'flat');
                const delta = card.delta;
                const isPositive = direction === 'up';
                const isNegative = direction === 'down';
                const deltaClass = isPositive ? 'badge-light-success' : (isNegative ? 'badge-light-danger' : 'badge-light-secondary');
                const deltaPrefix = isPositive ? '+' : '';
                const icon = escapeHtml(card.icon || 'ki-outline ki-chart-line-up-2');
                const badgeVariant = escapeHtml(card.badgeVariant || 'light-primary');
                const hint = card.hint ? '<div class="text-muted fs-8 mt-2">' + escapeHtml(card.hint) + '</div>' : '';
                const deltaBadge = delta !== null && delta !== undefined && delta !== '' ? (
                    '<span class="badge ' + deltaClass + '">' + deltaPrefix + escapeHtml(delta) + '%</span>'
                ) : '';

                return '' +
                    '<div class="col-12 col-sm-6 col-xl-3">' +
                    '  <div class="card card-flush h-100">' +
                    '    <div class="card-body">' +
                    '      <div class="d-flex align-items-center justify-content-between mb-4">' +
                    '        <span class="symbol symbol-35px">' +
                    '          <span class="symbol-label bg-' + badgeVariant + '">' +
                    '            <i class="' + icon + ' fs-4 text-' + badgeVariant.replace('light-', '') + '"></i>' +
                    '          </span>' +
                    '        </span>' +
                    deltaBadge +
                    '      </div>' +
                    '      <div class="text-gray-700 fw-semibold fs-7 mb-2">' + escapeHtml(card.label || '') + '</div>' +
                    '      <div class="text-gray-900 fw-bolder fs-2">' + escapeHtml(card.value || 0) + '</div>' +
                    hint +
                    '    </div>' +
                    '  </div>' +
                    '</div>';
            }).join('');

            $target.html(html);
        };

        const renderFailureList = function (targetSelector, payload) {
            const failures = Array.isArray(payload?.failures) ? payload.failures : [];
            const $target = $(targetSelector);
            if ($target.length === 0) {
                return;
            }

            if (failures.length === 0) {
                $target.html('<div class="text-muted fs-7">' + escapeHtml(messages.noFailures) + '</div>');
                return;
            }

            const html = failures.map(function (failure) {
                return '' +
                    '<div class="d-flex align-items-start gap-4 p-4 border border-gray-200 rounded">' +
                    '  <span class="bullet bullet-vertical h-40px bg-warning"></span>' +
                    '  <div class="flex-grow-1">' +
                    '    <div class="text-gray-900 fw-semibold fs-7">' + escapeHtml(failure.message || '') + '</div>' +
                    '    <div class="text-muted fs-8 mt-1">' + escapeHtml(failure.source || '') + '</div>' +
                    '  </div>' +
                    '</div>';
            }).join('');

            $target.html(html);
        };

        const renderTrendChart = function (elementId, payload) {
            const labels = Array.isArray(payload?.labels) ? payload.labels : [];
            const series = Array.isArray(payload?.series) ? payload.series : [];
            const element = document.getElementById(elementId);
            if (!element) {
                return;
            }

            if (!window.am5 || !window.am5xy || labels.length === 0 || series.length === 0) {
                element.innerHTML = '<div class="alert alert-light-info mb-0">' + escapeHtml(messages.chartUnavailable) + '</div>';
                return;
            }

            if (chartRoots[elementId]) {
                chartRoots[elementId].dispose();
                delete chartRoots[elementId];
            }

            const root = am5.Root.new(elementId);
            chartRoots[elementId] = root;
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: 'none',
                wheelY: 'none',
                layout: root.verticalLayout
            }));

            const chartData = labels.map(function (label, idx) {
                const row = { category: label };
                series.forEach(function (item, sIdx) {
                    row['value_' + sIdx] = Number(item?.data?.[idx] || 0);
                });
                return row;
            });

            const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: 'category',
                renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 30 })
            }));
            xAxis.data.setAll(chartData);

            const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            series.forEach(function (item, idx) {
                const lineSeries = chart.series.push(am5xy.LineSeries.new(root, {
                    name: String(item.name || ('Series ' + (idx + 1))),
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: 'value_' + idx,
                    categoryXField: 'category',
                    strokeWidth: 2,
                    tooltip: am5.Tooltip.new(root, {
                        labelText: '{name}: {valueY}'
                    })
                }));

                lineSeries.data.setAll(chartData);
                lineSeries.appear(800);
            });

            chart.set('cursor', am5xy.XYCursor.new(root, { behavior: 'none' }));
            chart.appear(800, 100);
        };

        window.VasWorkspace = {
            initLocalDataTable: initLocalDataTable,
            initAjaxDataTable: initAjaxDataTable,
            renderKpiStrip: renderKpiStrip,
            renderFailureList: renderFailureList,
            renderTrendChart: renderTrendChart,
            formatNumber: formatNumber,
            formatDecimal: formatDecimal,
        };
    })(window, window.jQuery);
</script>
