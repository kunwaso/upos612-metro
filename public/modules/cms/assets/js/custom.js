/*
 * CMS custom integration layer
 * Purpose: place Metronic-like component behavior here without mutating vendor PKThemes files.
 * Rule: only target elements with [data-cms-metronic] or .cms-metronic-* namespace.
 */

(function () {
    "use strict";

    var initCmsMetronicButtons = function () {
        var buttons = document.querySelectorAll(".cms-metronic-btn");
        if (!buttons.length) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                var pulseTarget = button.closest("[data-cms-metronic-pulse]");
                if (!pulseTarget) {
                    return;
                }

                pulseTarget.classList.remove("is-active");
                // Force reflow to restart animation class.
                void pulseTarget.offsetWidth;
                pulseTarget.classList.add("is-active");
            });
        });
    };

    document.addEventListener("DOMContentLoaded", function () {
        initCmsMetronicButtons();
    });
})();
