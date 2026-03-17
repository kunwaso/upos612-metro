"use strict";

// Class definition
var KTTablesWidget5 = function () {
    var table;
    var datatable;

    // Private methods
    const initDatatable = () => {
        // DataTables does not support tbody colspan rows (for example Blade @empty placeholders).
        if (table.querySelector('tbody td[colspan], tbody th[colspan]')) {
            return;
        }

        // Set date data order
        const tableRows = table.querySelectorAll('tbody tr');

        tableRows.forEach(row => {
            const dateRow = row.querySelectorAll('td');
            const dateCell = dateRow[2];
            if (!dateCell || typeof moment === 'undefined') {
                return;
            }

            const dateText = dateCell.textContent ? dateCell.textContent.trim() : '';
            if (!dateText) {
                return;
            }

            const parsedDate = moment(dateText, ["MMM DD, YYYY", "MMM D, YYYY"], true);
            if (parsedDate.isValid()) {
                dateCell.setAttribute('data-order', parsedDate.format());
            }
        });

        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": false,
            'order': [],
            "lengthChange": false,
            'pageLength': 6,
            'paging': false,
            'columnDefs': [
                { orderable: false, targets: 1 }, // Disable ordering on column 1 (product id)
            ]
        });
    }

    // Handle status filter
    const handleStatusFilter = () => {
        const select = document.querySelector('[data-kt-table-widget-5="filter_status"]');
        if (!select || !datatable) {
            return;
        }

        $(select).on('select2:select', function (e) {
            const value = $(this).val();
            if (value === 'Show All') {
                datatable.search('').draw();
            } else {
                datatable.search(value).draw();
            }
        });
    }

    // Public methods
    return {
        init: function () {
            table = document.querySelector('#kt_table_widget_5_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleStatusFilter();
        }
    }
}();

// Webpack support
if (typeof module !== 'undefined') {
    module.exports = KTTablesWidget5;
}

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTTablesWidget5.init();
});
