(function () {
    'use strict';

    var STORAGE_KEY = 'projectx_chat_drawer_open';

    function setCollapseState(pageElement, isOpen) {
        var state = isOpen ? 'off' : 'on';
        pageElement.setAttribute('data-kt-app-sidebar-secondary-collapse', state);
        pageElement.setAttribute('data-kt-app-sidebar-secondary-collapse-mobile', state);

        try {
            if (isOpen) {
                sessionStorage.setItem(STORAGE_KEY, '1');
                return;
            }

            sessionStorage.removeItem(STORAGE_KEY);
        } catch (error) {
            // Ignore storage failures (privacy mode, blocked storage, etc.)
        }
    }

    function isCollapsed(pageElement) {
        return pageElement.getAttribute('data-kt-app-sidebar-secondary-collapse') === 'on';
    }

    function isDrawerOpenPersisted() {
        try {
            return sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function dispatchDrawerShown(chatElement) {
        chatElement.dispatchEvent(new Event('kt.drawer.shown'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        var pageElement = document.querySelector('.page[data-kt-app-sidebar-secondary-enabled="true"]');
        var chatElement = document.getElementById('kt_drawer_chat');

        if (!pageElement || !chatElement) {
            return;
        }

        if (isDrawerOpenPersisted()) {
            setCollapseState(pageElement, true);
            dispatchDrawerShown(chatElement);
        }

        document.addEventListener('click', function (event) {
            var toggleButton = event.target && event.target.closest
                ? event.target.closest('#kt_drawer_chat_toggle')
                : null;
            if (toggleButton) {
                event.preventDefault();

                if (isCollapsed(pageElement)) {
                    setCollapseState(pageElement, true);
                    dispatchDrawerShown(chatElement);
                    return;
                }

                setCollapseState(pageElement, false);
                return;
            }

            var closeButton = event.target && event.target.closest
                ? event.target.closest('#kt_drawer_chat_close')
                : null;
            if (closeButton) {
                event.preventDefault();
                setCollapseState(pageElement, false);
            }
        });
    });
})();
