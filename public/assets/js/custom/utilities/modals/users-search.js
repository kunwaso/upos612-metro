"use strict";

var KTModalUserSearch = function () {
    var element;
    var inputElement;
    var suggestionsElement;
    var resultsElement;
    var resultsListElement;
    var emptyElement;
    var errorElement;
    var templateElement;
    var typeSelectorElement;
    var typeButtons = [];
    var searchObject;
    var debounceTimer = null;
    var activeRequest = null;
    var requestCounter = 0;
    var config = window.__globalSearchConfig || { defaultType: null, types: {} };
    var activeType = config.defaultType || null;
    var debounceDelay = 350;

    var hasTypes = function () {
        return config && config.types && Object.keys(config.types).length > 0;
    };

    var getMinLength = function () {
        var minLength = parseInt(element.getAttribute("data-kt-search-min-length"), 10);

        return isNaN(minLength) ? 2 : minLength;
    };

    var getActiveConfig = function () {
        if (!activeType || !config.types) {
            return null;
        }

        return config.types[activeType] || null;
    };

    var getTypeLabel = function (typeKey) {
        if (config.types && config.types[typeKey] && config.types[typeKey].label) {
            return config.types[typeKey].label;
        }

        return typeKey ? typeKey.replace(/_/g, " ") : "";
    };

    var setPlaceholder = function () {
        var activeConfig = getActiveConfig();

        if (!inputElement) {
            return;
        }

        inputElement.setAttribute(
            "placeholder",
            activeConfig ? "Search " + activeConfig.label.toLowerCase() + "..." : "Search records..."
        );
    };

    var setActiveType = function (typeKey) {
        activeType = typeKey;

        typeButtons.forEach(function (button) {
            var isActive = button.getAttribute("data-search-type") === typeKey;
            button.classList.toggle("active", isActive);
            button.classList.toggle("btn-primary", isActive);
            button.classList.toggle("btn-light-primary", !isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        setPlaceholder();
    };

    var clearResults = function () {
        if (resultsListElement) {
            resultsListElement.innerHTML = "";
        }
    };

    var hideError = function () {
        if (errorElement) {
            errorElement.classList.add("d-none");
        }
    };

    var showSuggestions = function () {
        if (suggestionsElement) {
            suggestionsElement.classList.remove("d-none");
        }
        if (resultsElement) {
            resultsElement.classList.add("d-none");
        }
        if (emptyElement) {
            emptyElement.classList.add("d-none");
        }
        hideError();
    };

    var showEmpty = function () {
        if (suggestionsElement) {
            suggestionsElement.classList.add("d-none");
        }
        if (resultsElement) {
            resultsElement.classList.add("d-none");
        }
        if (emptyElement) {
            emptyElement.classList.remove("d-none");
        }
        hideError();
    };

    var showError = function () {
        if (suggestionsElement) {
            suggestionsElement.classList.add("d-none");
        }
        if (resultsElement) {
            resultsElement.classList.add("d-none");
        }
        if (emptyElement) {
            emptyElement.classList.add("d-none");
        }
        if (errorElement) {
            errorElement.classList.remove("d-none");
        }
    };

    var renderResults = function (items) {
        clearResults();

        if (!Array.isArray(items) || items.length === 0) {
            showEmpty();
            return;
        }

        items.forEach(function (item) {
            var fragment = templateElement.content.cloneNode(true);
            var row = fragment.querySelector("a");
            var textNode = fragment.querySelector('[data-global-search-field="text"]');
            var subtitleNode = fragment.querySelector('[data-global-search-field="subtitle"]');
            var typeNode = fragment.querySelector('[data-global-search-field="type-label"]');
            var text = item && item.text ? item.text : "Untitled";
            var subtitle = item && item.subtitle ? item.subtitle : "";
            var typeKey = item && item.type ? item.type : activeType;

            row.setAttribute("href", item && item.url ? item.url : "#");
            textNode.textContent = text;
            subtitleNode.textContent = subtitle;
            subtitleNode.classList.toggle("d-none", subtitle === "");
            typeNode.textContent = getTypeLabel(typeKey);

            resultsListElement.appendChild(fragment);
        });

        if (suggestionsElement) {
            suggestionsElement.classList.add("d-none");
        }
        if (resultsElement) {
            resultsElement.classList.remove("d-none");
        }
        if (emptyElement) {
            emptyElement.classList.add("d-none");
        }
        hideError();
    };

    var abortActiveRequest = function () {
        if (activeRequest && activeRequest.readyState !== 4) {
            activeRequest.abort();
        }

        activeRequest = null;
    };

    var processSearch = function (search) {
        clearTimeout(debounceTimer);
        hideError();

        var activeConfig = getActiveConfig();
        var query = search.getQuery().trim();

        if (!activeConfig || query.length < getMinLength()) {
            abortActiveRequest();
            clearResults();
            showSuggestions();
            search.complete();
            return;
        }

        debounceTimer = setTimeout(function () {
            var requestId = ++requestCounter;
            var payload = $.extend({}, activeConfig.params || {});
            payload[activeConfig.param || "q"] = query;

            abortActiveRequest();
            if (suggestionsElement) {
                suggestionsElement.classList.add("d-none");
            }
            if (resultsElement) {
                resultsElement.classList.add("d-none");
            }
            if (emptyElement) {
                emptyElement.classList.add("d-none");
            }

            activeRequest = $.ajax({
                url: activeConfig.url,
                method: "GET",
                data: payload,
                dataType: "json",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
                .done(function (response) {
                    if (requestId !== requestCounter) {
                        return;
                    }

                    renderResults(response && response.results ? response.results : []);
                })
                .fail(function (xhr, status) {
                    if (status === "abort" || requestId !== requestCounter) {
                        return;
                    }

                    clearResults();
                    showError();
                })
                .always(function () {
                    if (requestId === requestCounter) {
                        search.complete();
                    }
                });
        }, debounceDelay);
    };

    var clearSearch = function () {
        clearTimeout(debounceTimer);
        abortActiveRequest();
        clearResults();
        showSuggestions();
    };

    var bindTypeSelector = function () {
        if (!typeSelectorElement) {
            return;
        }

        typeButtons = [].slice.call(typeSelectorElement.querySelectorAll("[data-search-type]"));

        if (!activeType && typeButtons.length > 0) {
            activeType = typeButtons[0].getAttribute("data-search-type");
        }

        setActiveType(activeType);

        typeButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                var nextType = button.getAttribute("data-search-type");

                if (nextType === activeType) {
                    return;
                }

                setActiveType(nextType);
                clearResults();
                hideError();

                if (inputElement.value.trim().length >= getMinLength()) {
                    if (searchObject.isProcessing()) {
                        searchObject.complete();
                    }
                    searchObject.search();
                } else {
                    showSuggestions();
                }
            });
        });
    };

    return {
        init: function () {
            element = document.querySelector("#kt_modal_users_search_handler");

            if (!element) {
                return;
            }

            inputElement = element.querySelector('[data-kt-search-element="input"]');
            suggestionsElement = element.querySelector('[data-kt-search-element="suggestions"]');
            resultsElement = element.querySelector('[data-kt-search-element="results"]');
            resultsListElement = element.querySelector('[data-global-search-element="results-list"]');
            emptyElement = element.querySelector('[data-kt-search-element="empty"]');
            errorElement = element.querySelector('[data-global-search-element="error"]');
            templateElement = element.querySelector("#kt_global_search_result_template");
            typeSelectorElement = element.querySelector('[data-global-search-element="type-selector"]');

            if (!inputElement || !suggestionsElement || !resultsElement || !resultsListElement || !templateElement || !hasTypes()) {
                return;
            }

            searchObject = new KTSearch(element);
            bindTypeSelector();
            showSuggestions();

            searchObject.on("kt.search.process", processSearch);
            searchObject.on("kt.search.clear", function () {
                clearSearch();
            });
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTModalUserSearch.init();
});
