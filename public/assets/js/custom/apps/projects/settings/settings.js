"use strict";

// Class definition
var KTProjectSettings = function () {

    // Private functions
    var handleForm = function () {
        var _form = document.getElementById('kt_project_settings_form');
        if (!_form) {
            return;
        }

        // Init Datepicker --- For more info, please check Flatpickr's official documentation: https://flatpickr.js.org/
        var datepickerEl = document.getElementById('kt_datepicker_1');
        if (datepickerEl && typeof $.fn.flatpickr !== 'undefined') {
            $("#kt_datepicker_1").flatpickr();
        }

        // Form validation
        var validation;
        var submitButton = _form.querySelector('#kt_project_settings_submit');
        if (!submitButton) {
            return;
        }

        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validation = FormValidation.formValidation(
            _form,
            {
                fields: {
                    name: {
                        validators: {
                            notEmpty: {
                                message: 'Project name is required'
                            }
                        }
                    },
                    type: {
                        validators: {
                            notEmpty: {
                                message: 'Project type is required'
                            }
                        }
                    },
                    description: {
                        validators: {
                            notEmpty: {
                                message: 'Project Description is required'
                            }
                        }
                    },
                    date: {
                        validators: {
                            notEmpty: {
                                message: 'Due Date is required'
                            }
                        }
                    },
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    submitButton: new FormValidation.plugins.SubmitButton(),
                    //defaultSubmit: new FormValidation.plugins.DefaultSubmit(), // Uncomment this line to enable normal button submit after form validation
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row'
                    })
                }
            }
        );

        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            validation.validate().then(function (status) {
                if (status == 'Valid') {

                    swal.fire({
                        text: "Thank you! You've updated your project settings",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn fw-bold btn-light-primary"
                        }
                    });

                } else {
                    swal.fire({
                        text: "Sorry, looks like there are some errors detected, please try again.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn fw-bold btn-light-primary"
                        }
                    });
                }
            });
        });
    }

    var handleConstructionDetailDatalist = function () {
        var constructionTypeField = document.getElementById('construction_type_select');
        var constructionDetailField = document.getElementById('construction_detail_input');

        if (!constructionTypeField || !constructionDetailField) {
            return;
        }

        var typeToListMap = {
            'Woven': 'construction-detail-woven',
            'Jacquard': 'construction-detail-woven',
            'Knit': 'construction-detail-knit',
            'Warp knit': 'construction-detail-knit',
            'Weft knit': 'construction-detail-knit',
            'Non-woven': 'construction-detail-non-woven',
            'Yarn': 'construction-detail-yarn'
        };

        var syncConstructionDetailList = function () {
            var selectedType = constructionTypeField.value.trim();
            var mappedListId = typeToListMap[selectedType] || '';

            if (mappedListId) {
                constructionDetailField.setAttribute('list', mappedListId);
                return;
            }

            constructionDetailField.removeAttribute('list');
        };

        syncConstructionDetailList();
        constructionTypeField.addEventListener('change', syncConstructionDetailList);
        constructionTypeField.addEventListener('input', syncConstructionDetailList);
    }

    // Public methods
    return {
        init: function () {
            handleForm();
            handleConstructionDetailDatalist();
        }
    }
}();


// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTProjectSettings.init();
});
