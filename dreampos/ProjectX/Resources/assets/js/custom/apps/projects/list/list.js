"use strict";

var KTProjectList = function () {    
    var initChart = function () {
        var element = document.getElementById("kt_fabric_list_chart");

        if (!element) {
            return;
        }

        var activeCount = parseInt(element.getAttribute('data-active') || '0', 10);
        var draftCount = parseInt(element.getAttribute('data-draft') || '0', 10);
        var needsApprovalCount = parseInt(element.getAttribute('data-needs-approval') || '0', 10);

        var hasData = (activeCount + draftCount + needsApprovalCount) > 0;

        var config = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: hasData ? [activeCount, draftCount, needsApprovalCount] : [1],
                    backgroundColor: hasData ? ['#00A3FF', '#FFC700', '#7239EA'] : ['#E4E6EF']
                }],
                labels: hasData ? ['Active', 'Draft', 'Needs Approval'] : ['No Data']
            },
            options: {
                chart: {
                    fontFamily: 'inherit'
                },
                borderWidth: 0,
                cutout: '75%',
                cutoutPercentage: 65,
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: false
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                },
                stroke: {
                    width: 0
                },
                tooltips: {
                    enabled: hasData,
                    intersect: false,
                    mode: 'nearest',
                    bodySpacing: 5,
                    yPadding: 10,
                    xPadding: 10,
                    caretPadding: 0,
                    displayColors: false,
                    backgroundColor: '#20D489',
                    titleFontColor: '#ffffff',
                    cornerRadius: 4,
                    footerSpacing: 0,
                    titleSpacing: 0
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }                
            }
        };

        var ctx = element.getContext('2d');
        new Chart(ctx, config);
    }

    return {
        init: function () {
            initChart();
        }
    }
}();

KTUtil.onDOMContentLoaded(function() {
    KTProjectList.init();
});
