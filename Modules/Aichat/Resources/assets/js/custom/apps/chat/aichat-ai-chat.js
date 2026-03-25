(function () {
    'use strict';

    var config = window.__aichatChatConfig || null;
    if (!config || !config.enabled) {
        return;
    }

    var i18n = config.i18n || {};
    var tokenNode = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = tokenNode ? String(tokenNode.getAttribute('content') || '') : '';

    function t(key, fallback) {
        return i18n[key] || fallback;
    }

    function e(value) {
        var node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function toHtml(text) {
        return e(text).replace(/\n/g, '<br>');
    }

    function route(template, params) {
        var out = String(template || '');
        Object.keys(params || {}).forEach(function (key) {
            out = out.replace(new RegExp('__' + key + '__', 'g'), String(params[key]));
        });
        return out;
    }

    function routeConversation(template, id) {
        return route(template, { CONVERSATION_ID: id, ID: id });
    }

    function routeMessage(template, id) {
        return route(template, { MESSAGE_ID: id, ID: id });
    }

    function request(url, options) {
        var opts = options || {};
        var headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }, opts.headers || {});

        return fetch(url, Object.assign({}, opts, { headers: headers })).then(function (response) {
            return response.text().then(function (raw) {
                var json = {};
                try {
                    json = raw ? JSON.parse(raw) : {};
                } catch (error) {
                    json = {};
                }

                if (!response.ok || json.success === false) {
                    throw new Error(json.message || json.msg || t('chat_provider_error', 'Request failed.'));
                }

                return json;
            });
        });
    }

    function parseSse(state, chunk, cb) {
        state.buffer += chunk;
        var lines = state.buffer.split(/\r?\n/);
        state.buffer = lines.pop() || '';
        var eventName = 'message';

        lines.forEach(function (line) {
            if (!line) {
                eventName = 'message';
                return;
            }

            if (line.indexOf('event:') === 0) {
                eventName = line.slice(6).trim();
                return;
            }

            if (line.indexOf('data:') === 0) {
                var payload = line.slice(5).trim();
                if (!payload) {
                    return;
                }

                try {
                    cb(eventName, JSON.parse(payload));
                } catch (error) {
                    // Ignore malformed chunks.
                }
            }
        });
    }

    function stream(url, payload, handlers) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            var contentType = String(response.headers.get('content-type') || '').toLowerCase();
            if (!response.ok || contentType.indexOf('application/json') !== -1 || !response.body) {
                return response.text().then(function (raw) {
                    var parsed = {};
                    try {
                        parsed = raw ? JSON.parse(raw) : {};
                    } catch (error) {
                        parsed = {};
                    }
                    throw new Error(parsed.message || parsed.msg || raw || t('chat_provider_error', 'Stream failed.'));
                });
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder('utf-8');
            var state = { buffer: '' };

            function readLoop() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        return;
                    }

                    parseSse(state, decoder.decode(result.value, { stream: true }), function (eventName, payload) {
                        if (handlers && typeof handlers[eventName] === 'function') {
                            handlers[eventName](payload || {});
                        }
                    });

                    return readLoop();
                });
            }

            return readLoop();
        });
    }

    function getActiveConversationId(container) {
        var node = container.querySelector('[data-chat-active-conversation]');
        return node ? String(node.value || '') : '';
    }

    function setActiveConversationId(container, id) {
        var node = container.querySelector('[data-chat-active-conversation]');
        if (node) {
            node.value = id || '';
        }
    }

    function getMessagesHost(container) {
        return container.querySelector('[data-kt-element="messages"]');
    }

    function clearMessages(container) {
        var host = getMessagesHost(container);
        if (!host) {
            return;
        }

        var nodes = host.querySelectorAll('[data-chat-rendered="1"]');
        Array.prototype.forEach.call(nodes, function (node) {
            node.remove();
        });
    }

    function showEmptyState(container, show) {
        var states = container.querySelectorAll('[data-chat-empty-state]');
        Array.prototype.forEach.call(states, function (state) {
            if (show) {
                state.classList.remove('d-none');
            } else {
                state.classList.add('d-none');
            }
        });
    }

    function warning(container, message) {
        var node = container.querySelector('[data-chat-warning-inline]');
        if (!node) {
            return;
        }

        node.textContent = message || '';
        clearTimeout(container.__aichatWarningTimer);
        if (!message) {
            return;
        }

        container.__aichatWarningTimer = setTimeout(function () {
            node.textContent = '';
        }, 6000);
    }

    function quoteFeatureEnabled() {
        return !!(((config || {}).features || {}).quote_wizard_enabled) && !!(((config || {}).permissions || {}).can_use_quote_wizard);
    }

    function isQuoteMode(container) {
        return !!container.__aichatQuoteMode;
    }

    function setQuoteMode(container, enabled) {
        container.__aichatQuoteMode = !!enabled;
        var button = container.querySelector('[data-chat-quote-mode-toggle]');
        if (button) {
            button.setAttribute('data-chat-quote-mode', enabled ? 'on' : 'off');
            button.classList.toggle('btn-warning', !!enabled);
            button.classList.toggle('btn-light-warning', !enabled);
        }

        if (!enabled) {
            renderQuoteDraft(container, null);
            return;
        }

        renderQuoteDraft(container, container.__aichatQuoteDraft || null);
        warning(container, t('quote_assistant_mode_on', 'Quote assistant mode is on.'));
    }

    function quoteRoute(templateKey, conversationId) {
        return routeConversation(((config || {}).routes || {})[templateKey] || '', conversationId);
    }

    function actionRoute(templateKey, conversationId, actionId) {
        return route(
            ((config || {}).routes || {})[templateKey] || '',
            {
                CONVERSATION_ID: conversationId,
                ACTION_ID: actionId || ''
            }
        );
    }

    function isActionCommand(prompt) {
        return /^\/action\b/i.test(String(prompt || '').trim());
    }

    function isLikelyMutationIntent(prompt) {
        var text = String(prompt || '').trim().toLowerCase();
        if (!text) {
            return false;
        }

        var mutationKeyword = /\b(create|add|new|insert|update|edit|change|delete|remove|set)\b/;
        if (!mutationKeyword.test(text)) {
            return false;
        }

        var targetKeyword = /\b(product|products|item|items|contact|contacts|customer|customers|supplier|suppliers|sale|sales|quote|quotes|purchase|purchases|setting|settings)\b/;
        return targetKeyword.test(text);
    }

    function buildNaturalLanguageActionHint() {
        return t(
            'chat_action_natural_language_hint',
            'Data changes require action confirmation. Use /action prepare <module> <action> <json payload>, then /action confirm.'
        );
    }

    function normalizeActionId(value) {
        var id = Number(value || 0);
        return Number.isFinite(id) && id > 0 ? Math.floor(id) : 0;
    }

    function getActionResponseData(json) {
        if (!json || typeof json !== 'object') {
            return {};
        }

        if (json.data && typeof json.data === 'object') {
            return json.data;
        }

        if (json.action && typeof json.action === 'object') {
            return json.action;
        }

        if (json.result && typeof json.result === 'object') {
            return json.result;
        }

        return json;
    }

    function getPendingActionItems(json) {
        var data = getActionResponseData(json);
        var candidates = [
            data.items,
            data.actions,
            data.pending_actions,
            data.pending,
            data.records
        ];

        for (var i = 0; i < candidates.length; i++) {
            if (Array.isArray(candidates[i])) {
                return candidates[i];
            }
        }

        if (Array.isArray(data)) {
            return data;
        }

        return [];
    }

    function getActionStatusLabel(status, fallback) {
        var normalized = String(status || fallback || 'pending').trim().toLowerCase();
        if (!normalized) {
            normalized = 'pending';
        }

        return normalized;
    }

    function formatActionDescriptor(item, fallbackStatus) {
        var id = normalizeActionId(item && item.id);
        var moduleName = String((item && item.module) || '').trim();
        var actionName = String((item && item.action) || '').trim();
        var status = getActionStatusLabel(item && item.status, fallbackStatus);
        var descriptor = id ? '#' + String(id) + ' ' : '';

        descriptor += moduleName && actionName ? moduleName + '.' + actionName : t('chat_action_label', 'AI action');
        descriptor += ' [' + status + ']';

        return descriptor;
    }

    function formatActionHint(item, fallbackCommand) {
        var id = normalizeActionId(item && item.id);
        var status = getActionStatusLabel(item && item.status, fallbackCommand);
        var confirmCommand = id ? '/action confirm ' + String(id) : '/action confirm <id>';
        var cancelCommand = id ? '/action cancel ' + String(id) : '/action cancel <id>';
        var prepareCommand = '/action prepare <module> <action> <json payload>';

        if (status === 'pending' || status === 'prepared') {
            return t('chat_action_pending_hint', 'Use /action confirm <id> or /action cancel <id>.')
                .replace('/action confirm <id>', confirmCommand)
                .replace('/action cancel <id>', cancelCommand);
        }

        if (status === 'executed') {
            return t('chat_action_executed_hint', 'Use /action pending to review the queue or /action prepare <module> <action> <json payload> to create a new action.')
                .replace('/action prepare <module> <action> <json payload>', prepareCommand);
        }

        if (status === 'cancelled' || status === 'failed') {
            return t('chat_action_complete_hint', 'Use /action pending to review the queue or /action prepare <module> <action> <json payload> to create a new action.')
                .replace('/action prepare <module> <action> <json payload>', prepareCommand);
        }

        return t('chat_action_command_help', 'Commands: /action pending, /action confirm [id], /action cancel [id], /action prepare <module> <action> <json payload>.');
    }

    function formatPendingActionNotice(items) {
        var validItems = (Array.isArray(items) ? items : [])
            .map(function (item) {
                return item && typeof item === 'object' ? item : null;
            })
            .filter(function (item) {
                return normalizeActionId(item && item.id) > 0;
            });

        if (!validItems.length) {
            return t('chat_action_pending_empty', 'No pending actions.')
                + '\n'
                + t('chat_action_pending_prepare_hint', 'Use /action prepare <module> <action> <json payload> to create one.');
        }

        var limit = Math.min(validItems.length, 5);
        var lines = [];

        for (var i = 0; i < limit; i++) {
            var item = validItems[i];
            var preview = String(item.preview_text || item.summary || item.description || '').trim();
            var line = formatActionDescriptor(item, 'pending');
            if (preview) {
                line += ' - ' + preview;
            }
            lines.push(line);
        }

        if (validItems.length > limit) {
            lines.push(t('chat_action_pending_more', '+:count more pending actions.').replace(':count', String(validItems.length - limit)));
        }

        lines.push(formatActionHint(validItems[0], 'pending'));

        return t('chat_action_pending_title', 'Pending AI actions')
            + ' (' + String(validItems.length) + ')'
            + '\n'
            + lines.join('\n');
    }

    function formatActionNotice(titleKey, fallbackTitle, item, fallbackStatus, bodyText) {
        var lines = [];
        var descriptor = formatActionDescriptor(item, fallbackStatus);

        lines.push(t(titleKey, fallbackTitle));
        lines.push(descriptor);

        if (bodyText) {
            lines.push(String(bodyText));
        }

        lines.push(formatActionHint(item, fallbackStatus));

        return lines.join('\n');
    }

    function actionUiEnabled() {
        return !!(((config || {}).features || {}).actions_enabled) && !!(((config || {}).permissions || {}).can_edit);
    }

    function getActionToolbar(container) {
        return container.querySelector('[data-chat-action-toolbar]');
    }

    function getPendingQueueHost(container) {
        return container.querySelector('[data-chat-pending-queue]');
    }

    function buildActionToolbar(container) {
        if (!actionUiEnabled()) {
            return;
        }

        if (getActionToolbar(container) && getPendingQueueHost(container)) {
            return;
        }

        var repliesHost = container.querySelector('[data-chat-suggested-replies]');
        if (!repliesHost || !repliesHost.parentNode) {
            return;
        }

        var toolbar = document.createElement('div');
        toolbar.className = 'mt-3 d-flex flex-wrap gap-2';
        toolbar.setAttribute('data-chat-action-toolbar', '1');

        var pendingButton = document.createElement('button');
        pendingButton.type = 'button';
        pendingButton.className = 'btn btn-sm btn-light-primary';
        pendingButton.setAttribute('data-chat-quick-action', 'pending');
        pendingButton.textContent = t('chat_action_toolbar_pending', 'Pending Actions');

        var confirmLatestButton = document.createElement('button');
        confirmLatestButton.type = 'button';
        confirmLatestButton.className = 'btn btn-sm btn-light-success';
        confirmLatestButton.setAttribute('data-chat-quick-action', 'confirm_latest');
        confirmLatestButton.textContent = t('chat_action_toolbar_confirm_latest', 'Confirm Latest');

        var cancelLatestButton = document.createElement('button');
        cancelLatestButton.type = 'button';
        cancelLatestButton.className = 'btn btn-sm btn-light-danger';
        cancelLatestButton.setAttribute('data-chat-quick-action', 'cancel_latest');
        cancelLatestButton.textContent = t('chat_action_toolbar_cancel_latest', 'Cancel Latest');

        toolbar.appendChild(pendingButton);
        toolbar.appendChild(confirmLatestButton);
        toolbar.appendChild(cancelLatestButton);

        var queueHost = document.createElement('div');
        queueHost.className = 'mt-3';
        queueHost.setAttribute('data-chat-pending-queue', '1');

        repliesHost.parentNode.insertBefore(toolbar, repliesHost);
        repliesHost.parentNode.insertBefore(queueHost, repliesHost);
    }

    function getActionStatusBadgeClass(status) {
        switch (String(status || '').toLowerCase()) {
            case 'confirmed':
                return 'badge-light-primary';
            case 'executed':
                return 'badge-light-success';
            case 'cancelled':
                return 'badge-light-dark';
            case 'failed':
                return 'badge-light-danger';
            case 'expired':
                return 'badge-light-danger';
            case 'pending':
            default:
                return 'badge-light-warning';
        }
    }

    function normalizePendingQueueItems(items) {
        return (Array.isArray(items) ? items : []).map(function (item) {
            return item && typeof item === 'object' ? item : null;
        }).filter(function (item) {
            return normalizeActionId(item.id) > 0;
        });
    }

    function renderPendingQueue(container, items, options) {
        if (!actionUiEnabled()) {
            return;
        }

        buildActionToolbar(container);
        var host = getPendingQueueHost(container);
        if (!host) {
            return;
        }

        var opts = options || {};
        if (opts.loading) {
            host.innerHTML = '<div class="text-muted fs-8">' + e(t('chat_action_queue_loading', 'Loading pending actions...')) + '</div>';
            return;
        }

        var queueItems = normalizePendingQueueItems(items);
        if (!queueItems.length) {
            host.innerHTML = '<div class="text-muted fs-8">' + e(t('chat_action_queue_empty', 'No pending actions in this conversation.')) + '</div>';
            return;
        }

        host.innerHTML = '';

        var title = document.createElement('div');
        title.className = 'fw-semibold text-gray-900 fs-8 mb-2';
        title.textContent = t('chat_action_queue_title', 'Pending Queue') + ' (' + String(queueItems.length) + ')';
        host.appendChild(title);

        queueItems.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'border border-gray-300 rounded p-3 mb-2';

            var descriptor = document.createElement('div');
            descriptor.className = 'd-flex flex-wrap align-items-center justify-content-between gap-2 mb-2';

            var label = document.createElement('div');
            label.className = 'text-gray-900 fw-semibold fs-8';
            label.textContent = formatActionDescriptor(item, 'pending');
            descriptor.appendChild(label);

            var statusBadge = document.createElement('span');
            statusBadge.className = 'badge ' + getActionStatusBadgeClass(item.status);
            statusBadge.textContent = getActionStatusLabel(item.status, 'pending');
            descriptor.appendChild(statusBadge);
            row.appendChild(descriptor);

            var previewText = String(item.preview_text || '').trim();
            if (previewText) {
                var preview = document.createElement('div');
                preview.className = 'text-muted fs-8 mb-2';
                preview.textContent = previewText;
                row.appendChild(preview);
            }

            var canMutate = ['pending', 'confirmed'].indexOf(getActionStatusLabel(item.status, 'pending')) !== -1;
            if (canMutate) {
                var actions = document.createElement('div');
                actions.className = 'd-flex flex-wrap gap-2';

                var confirmButton = document.createElement('button');
                confirmButton.type = 'button';
                confirmButton.className = 'btn btn-sm btn-light-success';
                confirmButton.setAttribute('data-chat-pending-confirm-id', String(normalizeActionId(item.id)));
                confirmButton.textContent = t('chat_action_confirm_button', 'Confirm');
                actions.appendChild(confirmButton);

                var cancelButton = document.createElement('button');
                cancelButton.type = 'button';
                cancelButton.className = 'btn btn-sm btn-light-danger';
                cancelButton.setAttribute('data-chat-pending-cancel-id', String(normalizeActionId(item.id)));
                cancelButton.textContent = t('chat_action_cancel_button', 'Cancel');
                actions.appendChild(cancelButton);

                row.appendChild(actions);
            }

            host.appendChild(row);
        });
    }

    function refreshPendingQueue(container, options) {
        if (!actionUiEnabled()) {
            return Promise.resolve([]);
        }

        buildActionToolbar(container);
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            renderPendingQueue(container, []);
            return Promise.resolve([]);
        }

        var opts = options || {};
        if (opts.loading) {
            renderPendingQueue(container, [], { loading: true });
        }

        return loadPendingActions(container).then(function (items) {
            renderPendingQueue(container, items);
            return items;
        }).catch(function (error) {
            renderPendingQueue(container, []);
            if (!opts.silent) {
                warning(container, (error && error.message) || t('chat_provider_error', 'Unable to load pending actions.'));
            }
            throw error;
        });
    }

    function getQuoteMissingModalNode(container) {
        return container.querySelector('[data-chat-quote-missing-modal]');
    }

    function getQuoteMissingModalInstance(modalNode) {
        if (!modalNode || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return window.bootstrap.Modal.getOrCreateInstance(modalNode);
    }

    function isDrawerChatContainer(container) {
        return !!container && String(container.getAttribute('data-aichat-chat-container') || '') === 'drawer';
    }

    function setQuoteModalDrawerZIndex(container, active) {
        if (!isDrawerChatContainer(container)) {
            return;
        }

        if (typeof container.__aichatOriginalZIndex === 'undefined') {
            container.__aichatOriginalZIndex = container.style.zIndex || '';
        }

        if (active) {
            container.style.zIndex = '2000';
            return;
        }

        container.style.zIndex = container.__aichatOriginalZIndex;
    }

    function bindQuoteMissingModalLayerReset(modalNode) {
        if (!modalNode || modalNode.__aichatQuoteModalLayerBound) {
            return;
        }

        modalNode.__aichatQuoteModalLayerBound = true;
        modalNode.addEventListener('hidden.bs.modal', function () {
            setQuoteModalDrawerZIndex(modalNode.__aichatQuoteModalOwner || null, false);
        });
    }

    function openQuoteMissingModal(container) {
        var modalNode = getQuoteMissingModalNode(container);
        var draft = container.__aichatQuoteDraft || null;
        var missing = (draft && Array.isArray(draft.missing_fields)) ? draft.missing_fields : [];
        if (!modalNode || !missing.length) {
            return;
        }

        var listNode = modalNode.querySelector('[data-chat-quote-missing-modal-list]');
        var inputNode = modalNode.querySelector('[data-chat-quote-missing-modal-input]');
        var errorNode = modalNode.querySelector('[data-chat-quote-missing-modal-error]');
        if (listNode) {
            listNode.innerHTML = missing.map(function (item) {
                return '<span class="badge badge-light-warning">' + e(item.label || item.key || '') + '</span>';
            }).join('');
        }
        if (errorNode) {
            errorNode.textContent = '';
        }
        if (inputNode) {
            inputNode.value = '';
        }

        var modal = getQuoteMissingModalInstance(modalNode);
        if (!modal) {
            warning(container, t('quote_assistant_fill_modal_unavailable', 'Modal is unavailable. Please type the missing details in chat.'));
            var chatInput = container.querySelector('[data-kt-element="input"]');
            if (chatInput) {
                chatInput.focus();
            }
            return;
        }

        if (isDrawerChatContainer(container)) {
            modalNode.__aichatQuoteModalOwner = container;
            bindQuoteMissingModalLayerReset(modalNode);
            setQuoteModalDrawerZIndex(container, true);
        }

        modal.show();
        setTimeout(function () {
            if (inputNode) {
                inputNode.focus();
            }
        }, 120);
    }

    function submitQuoteMissingModal(container) {
        if (isBusy(container)) {
            return;
        }

        var modalNode = getQuoteMissingModalNode(container);
        if (!modalNode) {
            return;
        }

        var inputNode = modalNode.querySelector('[data-chat-quote-missing-modal-input]');
        var errorNode = modalNode.querySelector('[data-chat-quote-missing-modal-error]');
        var message = inputNode ? String(inputNode.value || '').trim() : '';
        if (!message) {
            if (errorNode) {
                errorNode.textContent = t('quote_assistant_fill_modal_error_required', 'Please enter the missing details before submitting.');
            }
            if (inputNode) {
                inputNode.focus();
            }
            return;
        }

        var modal = getQuoteMissingModalInstance(modalNode);
        if (modal) {
            modal.hide();
        }
        if (inputNode) {
            inputNode.value = '';
        }
        if (errorNode) {
            errorNode.textContent = '';
        }

        warning(container, '');
        setBusy(container, true);
        processQuoteWizard(container, { message: message }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to process quote draft.'));
        }).finally(function () {
            setBusy(container, false);
        });
    }

    function renderQuoteDraft(container, draft) {
        var panel = container.querySelector('[data-chat-quote-panel]');
        if (!panel || !quoteFeatureEnabled()) {
            return;
        }

        container.__aichatQuoteDraft = draft || null;
        container.__aichatQuoteConversationId = draft ? String(getActiveConversationId(container) || '') : '';

        if (!isQuoteMode(container) && !draft) {
            panel.classList.add('d-none');
            return;
        }

        panel.classList.remove('d-none');

        var statusNode = container.querySelector('[data-chat-quote-status-text]');
        var summaryNode = container.querySelector('[data-chat-quote-summary]');
        var missingNode = container.querySelector('[data-chat-quote-missing]');
        var picksNode = container.querySelector('[data-chat-quote-picks]');
        var linksNode = container.querySelector('[data-chat-quote-links]');
        var fillMissingButton = container.querySelector('[data-chat-quote-fill-missing]');
        var confirmButton = container.querySelector('[data-chat-quote-confirm]');
        var missing = (draft && Array.isArray(draft.missing_fields)) ? draft.missing_fields : [];

        if (statusNode) {
            if (!draft) {
                statusNode.textContent = t('quote_assistant_mode_on', 'Quote assistant mode is on.');
            } else if (draft.status === 'ready') {
                statusNode.textContent = t('quote_assistant_ready', 'Quote draft is ready to confirm.');
            } else {
                statusNode.textContent = t('quote_assistant_mode_on', 'Quote assistant mode is on.');
            }
        }

        if (summaryNode) {
            var summary = (draft && draft.summary) || null;
            if (!summary) {
                summaryNode.innerHTML = '<div class="text-muted fs-8">' + e(t('quote_assistant_summary_empty', 'No draft details yet.')) + '</div>';
            } else {
                var lines = Array.isArray(summary.lines) ? summary.lines : [];
                var canRemoveLine = !!(draft && draft.status !== 'consumed');
                var linesHtml = lines.map(function (line) {
                    var textHtml = '<div class="fs-8 text-gray-800">' + e(line.label || '') + ': ' + e(line.text || '') + '</div>';
                    var removeButtonHtml = '';
                    if (canRemoveLine && line && line.line_uid) {
                        removeButtonHtml = '<button type="button" class="btn btn-xs btn-light-danger" data-chat-quote-remove-line="' + e(String(line.line_uid || '')) + '">' + e(t('quote_assistant_remove_line', 'Remove')) + '</button>';
                    }

                    return '<div class="d-flex align-items-center justify-content-between gap-2 mb-1">' + textHtml + removeButtonHtml + '</div>';
                }).join('');

                summaryNode.innerHTML =
                    '<div class="fw-semibold text-gray-900 mb-2">' + e(t('quote_assistant_current_summary', 'Current Draft')) + '</div>' +
                    '<div class="fs-8 text-gray-700 mb-1"><strong>' + e(t('quote_assistant_customer_required', 'Customer')) + ':</strong> ' + e(summary.customer || '-') + '</div>' +
                    '<div class="fs-8 text-gray-700 mb-1"><strong>' + e(t('quote_assistant_location_required', 'Location')) + ':</strong> ' + e(summary.location || '-') + '</div>' +
                    '<div class="fs-8 text-gray-700 mb-2"><strong>' + e(t('quote_assistant_expires_label', 'Expires')) + ':</strong> ' + e(summary.expires_at || '-') + '</div>' +
                    linesHtml;
            }
        }

        if (missingNode) {
            missingNode.innerHTML = missing.length
                ? '<div class="fw-semibold text-gray-900 mb-2">' + e(t('quote_assistant_missing', 'Still needed')) + '</div>' + missing.map(function (item) {
                    return '<span class="badge badge-light-warning me-2 mb-2">' + e(item.label || item.key || '') + '</span>';
                }).join('')
                : '';
        }

        if (fillMissingButton) {
            fillMissingButton.classList.toggle('d-none', !(draft && draft.status !== 'ready' && missing.length > 0));
            fillMissingButton.textContent = t('quote_assistant_fill_missing', 'Fill Still Needed');
        }

        if (picksNode) {
            picksNode.innerHTML = '';
            var pickLists = (draft && draft.pick_lists) || {};
            (pickLists.contacts || []).forEach(function (contact) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-light-warning';
                button.setAttribute('data-chat-quote-pick', 'contact');
                button.setAttribute('data-chat-quote-contact-id', String(contact.id || ''));
                button.textContent = contact.label || contact.name || '';
                picksNode.appendChild(button);
            });

            (pickLists.products || []).forEach(function (productGroup) {
                (productGroup.options || []).forEach(function (product) {
                    var productButton = document.createElement('button');
                    productButton.type = 'button';
                    productButton.className = 'btn btn-sm btn-light-warning';
                    productButton.setAttribute('data-chat-quote-pick', 'product');
                    productButton.setAttribute('data-chat-quote-product-id', String(product.id || ''));
                    productButton.setAttribute('data-chat-quote-line-uid', String(productGroup.line_uid || ''));
                    productButton.textContent = (productGroup.label ? productGroup.label + ': ' : '') + (product.label || product.name || '');
                    picksNode.appendChild(productButton);
                });
            });
        }

        if (linksNode) {
            linksNode.innerHTML = '';
            var result = (draft && draft.result) || {};
            if (result.public_url) {
                linksNode.innerHTML += '<a class="btn btn-sm btn-light-primary" target="_blank" href="' + e(result.public_url) + '">' + e(t('quote_assistant_open_public', 'Open Public Quote')) + '</a>';
            }
            if (result.admin_url) {
                linksNode.innerHTML += '<a class="btn btn-sm btn-light-info" target="_blank" href="' + e(result.admin_url) + '">' + e(t('quote_assistant_open_admin', 'Open In Admin')) + '</a>';
            }
        }

        if (confirmButton) {
            confirmButton.classList.toggle('d-none', !(draft && draft.status === 'ready'));
        }
    }

    function setBusy(container, isBusy) {
        container.__aichatBusy = !!isBusy;
        var input = container.querySelector('[data-kt-element="input"]');
        var sendButton = container.querySelector('[data-kt-element="send"]');
        var actionButtons = container.querySelectorAll('[data-chat-quick-action], [data-chat-pending-confirm-id], [data-chat-pending-cancel-id]');
        if (input) {
            input.disabled = !!isBusy;
        }
        if (sendButton) {
            sendButton.disabled = !!isBusy;
        }
        Array.prototype.forEach.call(actionButtons, function (button) {
            button.disabled = !!isBusy;
        });
    }

    function isBusy(container) {
        return !!container.__aichatBusy;
    }

    function timeText(value) {
        if (!value) {
            return 'Just now';
        }

        var date = new Date(value);
        if (isNaN(date.getTime())) {
            return 'Just now';
        }

        return date.toLocaleString();
    }

    function setMessageMeta(row, message) {
        row.__aichatMessage = message || {};
        row.setAttribute('data-chat-message-id', message && message.id ? String(message.id) : '');
    }

    function getMessageMeta(row) {
        return row && row.__aichatMessage ? row.__aichatMessage : null;
    }

    function setFeedbackState(row, value) {
        var up = row.querySelector('[data-chat-action="feedback-up"]');
        var down = row.querySelector('[data-chat-action="feedback-down"]');
        if (up) {
            up.classList.toggle('active', value === 'up');
        }
        if (down) {
            down.classList.toggle('active', value === 'down');
        }
    }

    function setAssistantActions(row, message) {
        var copy = row.querySelector('[data-chat-action="copy"]');
        var regen = row.querySelector('[data-chat-action="regenerate"]');
        var up = row.querySelector('[data-chat-action="feedback-up"]');
        var down = row.querySelector('[data-chat-action="feedback-down"]');
        var assistantActions = row.querySelector('[data-chat-assistant-actions]');

        if (assistantActions) {
            assistantActions.classList.toggle('d-none', !!(message && message.is_action_notice));
        }

        if (message && message.is_action_notice) {
            if (copy) {
                copy.disabled = true;
            }
            if (regen) {
                regen.disabled = true;
            }
            if (up) {
                up.disabled = true;
            }
            if (down) {
                down.disabled = true;
            }
            return;
        }

        var hasId = !!(message && message.id);
        var canRegenerate = !!(message && message.id && message.can_regenerate);

        if (copy) {
            copy.disabled = false;
        }
        if (regen) {
            regen.disabled = !canRegenerate;
        }
        if (up) {
            up.disabled = !hasId;
        }
        if (down) {
            down.disabled = !hasId;
        }

        setFeedbackState(row, message && message.feedback_value ? String(message.feedback_value) : null);
    }

    function renderMessageBody(row, message, streamText) {
        var body = row.querySelector('[data-kt-element="message-text"]');
        if (!body) {
            return;
        }

        if (streamText != null) {
            body.innerHTML = toHtml(streamText);
            return;
        }

        if (
            message &&
            (message.role === 'assistant' || message.role === 'system' || message.role === 'error') &&
            message.content_html
        ) {
            body.innerHTML = String(message.content_html);
            return;
        }

        body.innerHTML = toHtml((message && message.content) || '');
    }

    function createMessageRow(container, role, message) {
        var host = getMessagesHost(container);
        if (!host) {
            return null;
        }

        var templateName = role === 'user' ? 'template-out' : 'template-in';
        var template = host.querySelector('[data-kt-element="' + templateName + '"]');
        if (!template) {
            return null;
        }

        var row = template.cloneNode(true);
        row.classList.remove('d-none');
        row.setAttribute('data-chat-rendered', '1');

        var timeNode = row.querySelector('.text-muted.fs-7');
        if (timeNode) {
            timeNode.textContent = timeText(message && message.created_at);
        }

        setMessageMeta(row, message || {});
        renderMessageBody(row, message || {});

        if (role !== 'user') {
            setAssistantActions(row, message || {});
        }

        host.appendChild(row);
        host.scrollTop = host.scrollHeight;
        return row;
    }

    function setTitle(container, title) {
        var titleNode = container.querySelector('[data-chat-conversation-title]');
        if (titleNode) {
            titleNode.textContent = title || t('new_chat', 'New Chat');
        }
    }

    function renderMessages(container, messages) {
        clearMessages(container);
        var safeMessages = Array.isArray(messages) ? messages : [];
        showEmptyState(container, safeMessages.length === 0);

        safeMessages.forEach(function (message) {
            createMessageRow(container, message && message.role === 'user' ? 'user' : 'assistant', message || {});
        });
    }

    function renderConversationList(container, conversations) {
        var listNode = container.querySelector('[data-chat-conversations-list]');
        if (!listNode) {
            return;
        }

        var activeId = getActiveConversationId(container);
        var canEdit = !!(((config || {}).permissions || {}).can_edit);
        listNode.innerHTML = '';

        (conversations || []).forEach(function (conversation) {
            var id = String(conversation.id || '');
            var isActive = id === activeId;
            var row = document.createElement('div');
            row.className = 'd-flex align-items-center justify-content-between gap-2 mb-2 p-3 border rounded cursor-pointer ' + (isActive ? 'border-primary bg-light-primary' : 'border-gray-300');
            row.setAttribute('data-chat-conversation-id', id);
            row.innerHTML =
                '<div class="flex-grow-1 min-w-0 text-start">' +
                '<div class="fw-bold text-gray-900 mb-1">' + e(conversation.title || t('new_chat', 'New Chat')) + '</div>' +
                '<div class="text-muted fs-7 mb-1">' + e(conversation.last_message_preview || '') + '</div>' +
                '<div class="text-muted fs-8">' + e(timeText(conversation.updated_at || conversation.last_message_at)) + '</div>' +
                '</div>';

            if (canEdit && id) {
                var deleteLabel = t('chat_delete_conversation', 'Delete conversation');
                var deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'btn btn-sm btn-icon btn-active-color-danger flex-shrink-0';
                deleteButton.setAttribute('data-chat-delete-conversation', '1');
                deleteButton.setAttribute('data-conversation-id', id);
                deleteButton.setAttribute('title', deleteLabel);
                deleteButton.setAttribute('aria-label', deleteLabel);
                deleteButton.innerHTML = '<i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span></i>';
                row.appendChild(deleteButton);
            }

            listNode.appendChild(row);
        });

        showEmptyState(container, (conversations || []).length === 0);
    }

    function loadConversations(container) {
        var listUrl = String((config.routes || {}).list_url || '');
        if (!listUrl) {
            return Promise.resolve([]);
        }

        return request(listUrl, { method: 'GET' }).then(function (json) {
            var conversations = Array.isArray(json.data) ? json.data : [];
            container.__aichatConversations = conversations;
            renderConversationList(container, conversations);
            return conversations;
        });
    }

    function loadConversation(container, id) {
        if (!id) {
            setTitle(container, t('new_chat', 'New Chat'));
            renderMessages(container, []);
            renderQuoteDraft(container, null);
            renderPendingQueue(container, []);
            return Promise.resolve(null);
        }

        var template = (config.routes || {}).conversation_url_template || '';
        var url = routeConversation(template, id);
        if (!url) {
            return Promise.resolve(null);
        }

        return request(url, { method: 'GET' }).then(function (json) {
            var payload = (json && json.data) || {};
            var conversation = payload.conversation || {};
            setActiveConversationId(container, conversation.id || id);
            if (String(container.__aichatQuoteConversationId || '') !== String(conversation.id || id)) {
                renderQuoteDraft(container, null);
            }
            setTitle(container, conversation.title || t('new_chat', 'New Chat'));
            renderMessages(container, payload.messages || []);
            refreshPendingQueue(container, { silent: true }).catch(function () {
                return [];
            });
            return payload;
        });
    }

    function deleteConversation(container, conversationId) {
        var id = String(conversationId || '');
        if (!id) {
            return Promise.resolve(null);
        }

        if (!window.confirm(t('chat_delete_confirm', 'Delete this conversation? This cannot be undone.'))) {
            return Promise.resolve(null);
        }

        var destroyTemplate = (config.routes || {}).destroy_url_template || '';
        var destroyUrl = routeConversation(destroyTemplate, id);
        if (!destroyUrl) {
            warning(container, t('chat_provider_error', 'Delete route is missing.'));
            return Promise.resolve(null);
        }

        var wasActive = String(getActiveConversationId(container) || '') === id;
        if (wasActive) {
            setActiveConversationId(container, '');
            setTitle(container, t('new_chat', 'New Chat'));
            renderMessages(container, []);
        }

        return request(destroyUrl, { method: 'DELETE' }).then(function (json) {
            var successMessage = (json && (json.message || json.msg)) || t('chat_delete_success', 'Conversation deleted successfully.');
            return loadConversations(container).then(function (conversations) {
                var items = Array.isArray(conversations) ? conversations : [];

                if (wasActive && items.length > 0) {
                    var nextId = String(items[0].id || '');
                    if (nextId) {
                        setActiveConversationId(container, nextId);
                        return loadConversation(container, nextId).then(function () {
                            warning(container, successMessage);
                            return items;
                        });
                    }
                }

                warning(container, successMessage);
                return items;
            });
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to delete conversation.'));
            return null;
        });
    }

    function createConversation(container, title) {
        var url = String((config.routes || {}).create_url || '');
        if (!url) {
            return Promise.reject(new Error(t('chat_provider_error', 'Create route is missing.')));
        }

        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title: title || null })
        }).then(function (json) {
            var conversation = (json && json.data) || {};
            var id = conversation.id ? String(conversation.id) : '';
            if (!id) {
                throw new Error(t('chat_provider_error', 'Unable to create conversation.'));
            }

            setActiveConversationId(container, id);
            return loadConversations(container).then(function () {
                return loadConversation(container, id);
            });
        });
    }

    function renderReplies(container, replies) {
        var host = container.querySelector('[data-chat-suggested-replies]');
        if (!host) {
            return;
        }

        host.innerHTML = '';
        (replies || []).slice(0, 3).forEach(function (reply) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-light-primary';
            button.setAttribute('data-chat-suggested-reply', '1');
            button.setAttribute('data-chat-suggested-text', String(reply));
            button.textContent = String(reply);
            host.appendChild(button);
        });
    }

    function sync(container) {
        return loadConversations(container).then(function () {
            var id = getActiveConversationId(container);
            if (!id) {
                return null;
            }
            return loadConversation(container, id);
        });
    }

    function selectedProvider(container) {
        var node = container.querySelector('[data-chat-provider-select]');
        return node ? String(node.value || '') : '';
    }

    function selectedModel(container) {
        var node = container.querySelector('[data-chat-model-select]');
        return node ? String(node.value || '') : '';
    }

    function updateModelOptions(container) {
        var providerNode = container.querySelector('[data-chat-provider-select]');
        var modelNode = container.querySelector('[data-chat-model-select]');
        if (!providerNode || !modelNode) {
            return;
        }

        var provider = String(providerNode.value || '');
        var firstVisible = null;

        Array.prototype.forEach.call(modelNode.options, function (option) {
            var visible = String(option.getAttribute('data-provider') || '') === provider;
            option.hidden = !visible;
            if (visible && firstVisible === null) {
                firstVisible = option.value;
            }
        });

        if (!modelNode.value || (modelNode.selectedOptions[0] && modelNode.selectedOptions[0].hidden)) {
            if (firstVisible !== null) {
                modelNode.value = firstVisible;
            }
        }
    }

    function processQuoteWizard(container, payload) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return Promise.reject(new Error(t('chat_provider_error', 'Conversation is required.')));
        }

        var url = quoteRoute('quote_wizard_process_url_template', conversationId);
        if (!url) {
            return Promise.reject(new Error(t('chat_provider_error', 'Quote wizard route is missing.')));
        }

        var draft = container.__aichatQuoteDraft || null;
        var body = Object.assign({
            draft_id: draft && draft.id ? draft.id : null,
            provider: selectedProvider(container) || config.default_provider,
            model: selectedModel(container) || config.default_model
        }, payload || {});

        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function (json) {
            var data = (json && json.data) || {};
            renderQuoteDraft(container, data.draft || null);
            return sync(container).then(function () {
                return data;
            });
        });
    }

    function confirmQuoteWizard(container) {
        var conversationId = getActiveConversationId(container);
        var draft = container.__aichatQuoteDraft || null;
        if (!conversationId || !draft || !draft.id) {
            return Promise.resolve(null);
        }

        var url = quoteRoute('quote_wizard_confirm_url_template', conversationId);
        if (!url) {
            return Promise.reject(new Error(t('chat_provider_error', 'Quote confirm route is missing.')));
        }

        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ draft_id: draft.id })
        }).then(function (json) {
            var data = (json && json.data) || {};
            renderQuoteDraft(container, data.draft || null);
            return sync(container).then(function () {
                return data;
            });
        });
    }

    function parseQuoteStartCommand(prompt) {
        var match = String(prompt || '').trim().match(/^\/quote(?:\s+(.*))?$/i);
        if (!match) {
            return null;
        }

        return {
            message: String(match[1] || '').trim()
        };
    }

    function isQuoteCancelCommand(prompt) {
        return /^\/cancel(?:\s+.*)?$/i.test(String(prompt || '').trim());
    }

    function parseActionPrepareCommand(prompt) {
        var trimmed = String(prompt || '').trim();
        var match = trimmed.match(/^\/action\s+prepare(?:\s+([\s\S]+))?$/i);
        if (!match) {
            return null;
        }

        var remainder = String(match[1] || '').trim();
        if (!remainder) {
            throw new Error(t('chat_action_prepare_usage', 'Use /action prepare <module> <action> <json payload>.'));
        }

        var parts = remainder.split(/\s+/);
        if (parts.length < 2) {
            throw new Error(t('chat_action_prepare_usage', 'Use /action prepare <module> <action> <json payload>.'));
        }

        var moduleName = String(parts.shift() || '').trim();
        var actionName = String(parts.shift() || '').trim();
        var payloadText = parts.join(' ').trim();
        var payload = {};
        if (payloadText) {
            try {
                payload = JSON.parse(payloadText);
                if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
                    throw new Error('invalid');
                }
            } catch (error) {
                throw new Error(t('chat_action_invalid_payload', 'The action payload is invalid or incomplete.'));
            }
        }

        return {
            module: moduleName.toLowerCase(),
            action: actionName.toLowerCase(),
            payload: payload
        };
    }

    function isActionPendingCommand(prompt) {
        return /^\/action\s+pending(?:\s*)$/i.test(String(prompt || '').trim());
    }

    function parseActionConfirmCommand(prompt) {
        var trimmed = String(prompt || '').trim();
        var match = trimmed.match(/^\/action\s+confirm(?:\s+([\s\S]+))?$/i);
        if (!match) {
            return null;
        }

        var idText = String(match[1] || '').trim();
        if (!idText) {
            return 0;
        }

        if (!/^\d+$/.test(idText)) {
            throw new Error(t('chat_action_confirm_usage', 'Use /action confirm or /action confirm <id>.'));
        }

        return Number(idText);
    }

    function parseActionCancelCommand(prompt) {
        var trimmed = String(prompt || '').trim();
        var match = trimmed.match(/^\/action\s+cancel(?:\s+([\s\S]+))?$/i);
        if (!match) {
            return null;
        }

        var idText = String(match[1] || '').trim();
        if (!idText) {
            return 0;
        }

        if (!/^\d+$/.test(idText)) {
            throw new Error(t('chat_action_cancel_usage', 'Use /action cancel or /action cancel <id>.'));
        }

        return Number(idText);
    }

    function appendActionNotice(container, text) {
        createMessageRow(container, 'assistant', {
            role: 'assistant',
            content: text,
            created_at: new Date().toISOString(),
            can_regenerate: false,
            is_action_notice: true
        });
    }

    function loadPendingActions(container) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return Promise.resolve([]);
        }

        var url = actionRoute('actions_pending_url_template', conversationId, null);
        if (!url) {
            return Promise.resolve([]);
        }

        return request(url, { method: 'GET' }).then(function (json) {
            return getPendingActionItems(json);
        });
    }

    function resolveActionIdForCommand(container, requestedId) {
        var normalizedRequestedId = normalizeActionId(requestedId);
        if (normalizedRequestedId > 0) {
            return Promise.resolve(normalizedRequestedId);
        }

        return loadPendingActions(container).then(function (items) {
            var firstValidItem = (Array.isArray(items) ? items : []).find(function (item) {
                return normalizeActionId(item && item.id) > 0;
            });

            if (!firstValidItem) {
                throw new Error(
                    t('chat_action_pending_empty', 'No pending actions.')
                    + ' '
                    + t('chat_action_pending_prepare_hint', 'Use /action prepare <module> <action> <json payload> to create one.')
                );
            }

            return normalizeActionId(firstValidItem.id);
        });
    }

    function handleActionCommandError(container, error, fallbackMessage) {
        warning(container, (error && error.message) || fallbackMessage || t('chat_provider_error', 'Unable to process action command.'));
    }

    function prepareAction(container, command) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return createConversation(container).then(function () {
                return prepareAction(container, command);
            });
        }

        var url = actionRoute('actions_prepare_url_template', conversationId, null);
        if (!url) {
            return Promise.reject(new Error(t('chat_provider_error', 'Action prepare route is missing.')));
        }

        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                module: command.module,
                action: command.action,
                payload: command.payload || {},
                channel: 'web'
            })
        }).then(function (json) {
            var data = getActionResponseData(json);
            var message = formatActionNotice(
                'chat_action_prepared',
                'Action prepared. Confirm to execute.',
                {
                    id: data.id || 0,
                    module: data.module || command.module,
                    action: data.action || command.action,
                    status: data.status || 'pending'
                },
                'pending',
                data.preview_text ? String(data.preview_text) : ''
            );
            appendActionNotice(container, message);
            return refreshPendingQueue(container, { silent: true }).catch(function () {
                return [];
            }).then(function () {
                return data;
            });
        });
    }

    function confirmAction(container, actionId) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return Promise.reject(new Error(t('chat_provider_error', 'Conversation is required.')));
        }

        return resolveActionIdForCommand(container, actionId).then(function (resolvedId) {
            var url = actionRoute('actions_confirm_url_template', conversationId, resolvedId);
            if (!url) {
                throw new Error(t('chat_provider_error', 'Action confirm route is missing.'));
            }

            return request(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            }).then(function (json) {
                var data = getActionResponseData(json);
                appendActionNotice(
                    container,
                    formatActionNotice(
                        'chat_action_executed',
                        'Action executed successfully.',
                        {
                            id: data.id || resolvedId,
                            module: data.module || '',
                            action: data.action || '',
                            status: data.status || 'executed'
                        },
                        'executed',
                        data.preview_text ? String(data.preview_text) : ''
                    )
                );
                return refreshPendingQueue(container, { silent: true }).catch(function () {
                    return [];
                }).then(function () {
                    return data;
                });
            });
        });
    }

    function cancelAction(container, actionId) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return Promise.reject(new Error(t('chat_provider_error', 'Conversation is required.')));
        }

        return resolveActionIdForCommand(container, actionId).then(function (resolvedId) {
            var url = actionRoute('actions_cancel_url_template', conversationId, resolvedId);
            if (!url) {
                throw new Error(t('chat_provider_error', 'Action cancel route is missing.'));
            }

            return request(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            }).then(function (json) {
                var data = getActionResponseData(json);
                appendActionNotice(
                    container,
                    formatActionNotice(
                        'chat_action_cancelled',
                        'Action cancelled.',
                        {
                            id: data.id || resolvedId,
                            module: data.module || '',
                            action: data.action || '',
                            status: data.status || 'cancelled'
                        },
                        'cancelled',
                        data.preview_text ? String(data.preview_text) : ''
                    )
                );
                return refreshPendingQueue(container, { silent: true }).catch(function () {
                    return [];
                }).then(function () {
                    return data;
                });
            });
        });
    }

    function submitQuoteWizard(container, message) {
        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            return createConversation(container).then(function () {
                return submitQuoteWizard(container, message);
            });
        }

        warning(container, '');
        setBusy(container, true);

        return processQuoteWizard(container, { message: String(message || '') }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to process quote draft.'));
        }).finally(function () {
            setBusy(container, false);
        });
    }

    function send(container) {
        if (isBusy(container)) {
            return;
        }

        var input = container.querySelector('[data-kt-element="input"]');
        if (!input) {
            return;
        }

        var prompt = String(input.value || '').trim();
        if (!prompt) {
            return;
        }

        var quoteStartCommand = parseQuoteStartCommand(prompt);
        if (quoteStartCommand) {
            input.value = '';
            if (!quoteFeatureEnabled()) {
                warning(container, t('quote_assistant_feature_disabled', 'Quote assistant is disabled for this business.'));
                return;
            }
            setQuoteMode(container, true);
            submitQuoteWizard(container, quoteStartCommand.message);

            return;
        }

        if (quoteFeatureEnabled() && isQuoteMode(container) && isQuoteCancelCommand(prompt)) {
            input.value = '';
            setQuoteMode(container, false);
            warning(container, t('quote_assistant_mode_off', 'Quote assistant mode is off.'));

            return;
        }

        if (quoteFeatureEnabled() && isQuoteMode(container)) {
            input.value = '';
            submitQuoteWizard(container, prompt);

            return;
        }

        if (isActionCommand(prompt)) {
            var actionCommand = null;

            try {
                actionCommand = parseActionPrepareCommand(prompt);
                if (!actionCommand) {
                    var pendingId = parseActionConfirmCommand(prompt);
                    if (pendingId !== null) {
                        actionCommand = { kind: 'confirm', id: pendingId };
                    } else {
                        var cancelId = parseActionCancelCommand(prompt);
                        if (cancelId !== null) {
                            actionCommand = { kind: 'cancel', id: cancelId };
                        } else if (isActionPendingCommand(prompt)) {
                            actionCommand = { kind: 'pending' };
                        }
                    }
                } else {
                    actionCommand.kind = 'prepare';
                }
            } catch (error) {
                warning(container, error.message || t('chat_action_invalid_payload', 'The action payload is invalid or incomplete.'));
                return;
            }

            if (!actionCommand) {
                warning(container, t('chat_action_command_help', 'Commands: /action pending, /action confirm [id], /action cancel [id], /action prepare <module> <action> <json payload>.'));
                return;
            }

            input.value = '';
            warning(container, '');
            setBusy(container, true);

            if (actionCommand.kind === 'prepare') {
                prepareAction(container, actionCommand).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to prepare action.'));
                }).finally(function () {
                    setBusy(container, false);
                });

                return;
            }

            if (actionCommand.kind === 'pending') {
                refreshPendingQueue(container, { loading: true, silent: true }).then(function (items) {
                    appendActionNotice(container, formatPendingActionNotice(items));
                }).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to load pending actions.'));
                }).finally(function () {
                    setBusy(container, false);
                });

                return;
            }

            if (actionCommand.kind === 'confirm') {
                confirmAction(container, actionCommand.id).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to confirm action.'));
                }).finally(function () {
                    setBusy(container, false);
                });

                return;
            }

            if (actionCommand.kind === 'cancel') {
                cancelAction(container, actionCommand.id).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to cancel action.'));
                }).finally(function () {
                    setBusy(container, false);
                });

                return;
            }
        }

        var conversationId = getActiveConversationId(container);
        if (!conversationId) {
            createConversation(container).then(function () {
                send(container);
            }).catch(function (error) {
                warning(container, error.message || t('chat_provider_error', 'Unable to start chat.'));
            });
            return;
        }

        var provider = selectedProvider(container) || config.default_provider;
        var model = selectedModel(container) || config.default_model;
        var url = routeConversation((config.routes || {}).stream_url_template || '', conversationId);
        if (!url) {
            warning(container, t('chat_provider_error', 'Stream route is missing.'));
            return;
        }

        var showMutationHint = actionUiEnabled() && isLikelyMutationIntent(prompt);

        input.value = '';
        warning(container, showMutationHint ? buildNaturalLanguageActionHint() : '');
        setBusy(container, true);

        createMessageRow(container, 'user', {
            role: 'user',
            content: prompt,
            created_at: new Date().toISOString()
        });

        var assistantRow = createMessageRow(container, 'assistant', {
            role: 'assistant',
            content: '...',
            created_at: new Date().toISOString(),
            can_regenerate: false
        });

        var contentBuffer = '';

        stream(url, {
            prompt: prompt,
            provider: provider,
            model: model
        }, {
            warning: function (payload) {
                warning(container, payload.message || t('chat_provider_error', 'Warning.'));
            },
            chunk: function (payload) {
                contentBuffer += String(payload.text || '');
                if (assistantRow) {
                    renderMessageBody(assistantRow, null, contentBuffer);
                }
            },
            done: function (payload) {
                var assistantMessage = payload.assistant_message || null;
                if (assistantRow && assistantMessage) {
                    setMessageMeta(assistantRow, assistantMessage);
                    renderMessageBody(assistantRow, assistantMessage);
                    setAssistantActions(assistantRow, assistantMessage);
                }

                renderReplies(container, payload.suggested_replies || []);
            },
            error: function (payload) {
                var message = payload.message || t('chat_provider_error', 'Unable to generate response.');
                var errorMessage = payload.error_message || null;

                if (assistantRow && errorMessage) {
                    setMessageMeta(assistantRow, errorMessage);
                    renderMessageBody(assistantRow, errorMessage);
                    setAssistantActions(assistantRow, errorMessage);
                } else if (assistantRow) {
                    renderMessageBody(assistantRow, { role: 'error', content: message });
                }

                warning(container, message);
            }
        }).then(function () {
            return sync(container);
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Stream failed.'));
        }).finally(function () {
            setBusy(container, false);
        });
    }

    function copyToClipboard(text) {
        var value = String(text || '');
        if (!value) {
            return Promise.resolve();
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(value);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                resolve();
            } catch (error) {
                reject(error);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    function saveFeedback(container, row, value) {
        var message = getMessageMeta(row);
        if (!message || !message.id) {
            return;
        }

        var url = routeMessage((config.routes || {}).feedback_url_template || '', message.id);
        if (!url) {
            return;
        }

        request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ feedback: value })
        }).then(function () {
            message.feedback_value = value;
            setMessageMeta(row, message);
            setAssistantActions(row, message);
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to save feedback.'));
        });
    }

    function regenerate(container, row) {
        if (isBusy(container)) {
            return;
        }

        var message = getMessageMeta(row);
        if (!message || !message.id) {
            return;
        }

        var url = routeMessage((config.routes || {}).regenerate_url_template || '', message.id);
        if (!url) {
            warning(container, t('chat_provider_error', 'Regenerate route is missing.'));
            return;
        }

        warning(container, '');
        setBusy(container, true);
        setAssistantActions(row, { id: message.id, role: 'assistant', can_regenerate: false });

        var buffer = '';

        stream(url, {}, {
            warning: function (payload) {
                warning(container, payload.message || t('chat_provider_error', 'Warning.'));
            },
            chunk: function (payload) {
                buffer += String(payload.text || '');
                renderMessageBody(row, null, buffer);
            },
            done: function (payload) {
                var assistantMessage = payload.assistant_message || null;
                if (assistantMessage) {
                    setMessageMeta(row, assistantMessage);
                    renderMessageBody(row, assistantMessage);
                    setAssistantActions(row, assistantMessage);
                }

                renderReplies(container, payload.suggested_replies || []);
            },
            error: function (payload) {
                warning(container, payload.message || t('chat_provider_error', 'Unable to regenerate response.'));
            }
        }).then(function () {
            return sync(container);
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Regenerate failed.'));
        }).finally(function () {
            setBusy(container, false);
        });
    }

    function share(container) {
        var id = getActiveConversationId(container);
        if (!id) {
            return;
        }

        var url = routeConversation((config.routes || {}).share_url_template || '', id);
        if (!url) {
            return;
        }

        request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        }).then(function (json) {
            var sharedUrl = (((json || {}).data || {}).url) || '';
            if (!sharedUrl) {
                return;
            }

            window.prompt(t('chat_share', 'Share URL'), sharedUrl);
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to share.'));
        });
    }

    function exportConversation(container, format) {
        var id = getActiveConversationId(container);
        if (!id) {
            return;
        }

        var template = (config.routes || {}).export_url_template || '';
        var url = routeConversation(template, id);
        if (!url) {
            return;
        }

        window.open(url + '?format=' + encodeURIComponent(format || 'markdown'), '_blank');
    }

    function onAssistantAction(container, button) {
        var row = button.closest('[data-chat-rendered="1"]');
        if (!row) {
            return;
        }

        var action = String(button.getAttribute('data-chat-action') || '');
        if (!action) {
            return;
        }

        if (action === 'copy') {
            var message = getMessageMeta(row);
            copyToClipboard(message && message.content ? message.content : '').then(function () {
                warning(container, t('chat_copied', 'Copied.'));
            }).catch(function () {
                warning(container, t('chat_provider_error', 'Unable to copy.'));
            });
            return;
        }

        if (action === 'regenerate') {
            regenerate(container, row);
            return;
        }

        if (action === 'feedback-up') {
            saveFeedback(container, row, 'up');
            return;
        }

        if (action === 'feedback-down') {
            saveFeedback(container, row, 'down');
        }
    }

    function bootstrap(container) {
        if (container.__aichatBootstrapPromise) {
            return container.__aichatBootstrapPromise;
        }

        container.__aichatBootstrapPromise = loadConversations(container).then(function (conversations) {
            var id = getActiveConversationId(container);

            if (!id && conversations.length) {
                id = conversations[0].id;
                setActiveConversationId(container, id);
            }

            if (!id) {
                setTitle(container, t('new_chat', 'New Chat'));
                renderMessages(container, []);
                renderPendingQueue(container, []);
                return null;
            }

            return loadConversation(container, id);
        }).catch(function (error) {
            warning(container, error.message || t('chat_provider_error', 'Unable to load conversations.'));
            return null;
        }).finally(function () {
            container.__aichatBootstrapPromise = null;
        });

        return container.__aichatBootstrapPromise;
    }

    function bindContainer(container) {
        if (!container || container.__aichatBound) {
            return;
        }
        container.__aichatBound = true;

        updateModelOptions(container);
        renderReplies(container, []);
        if (actionUiEnabled()) {
            buildActionToolbar(container);
            renderPendingQueue(container, []);
        }
        if (quoteFeatureEnabled()) {
            setQuoteMode(container, false);
        }

        container.addEventListener('click', function (event) {
            var deleteButton = event.target.closest('[data-chat-delete-conversation]');
            if (deleteButton && container.contains(deleteButton)) {
                event.preventDefault();
                event.stopPropagation();
                var deleteId = String(deleteButton.getAttribute('data-conversation-id') || '');
                if (!deleteId) {
                    return;
                }

                deleteConversation(container, deleteId);
                return;
            }

            var conversationButton = event.target.closest('[data-chat-conversation-id]');
            if (conversationButton && container.contains(conversationButton)) {
                event.preventDefault();
                var id = String(conversationButton.getAttribute('data-chat-conversation-id') || '');
                if (!id) {
                    return;
                }

                setActiveConversationId(container, id);
                loadConversations(container).then(function () {
                    return loadConversation(container, id);
                }).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to load conversation.'));
                });
                return;
            }

            var sendButton = event.target.closest('[data-kt-element="send"]');
            if (sendButton && container.contains(sendButton)) {
                event.preventDefault();
                send(container);
                return;
            }

            var quickActionButton = event.target.closest('[data-chat-quick-action]');
            if (quickActionButton && container.contains(quickActionButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                var quickAction = String(quickActionButton.getAttribute('data-chat-quick-action') || '');
                warning(container, '');
                setBusy(container, true);

                if (quickAction === 'pending') {
                    refreshPendingQueue(container, { loading: true, silent: true }).then(function (items) {
                        appendActionNotice(container, formatPendingActionNotice(items));
                    }).catch(function (error) {
                        warning(container, error.message || t('chat_provider_error', 'Unable to load pending actions.'));
                    }).finally(function () {
                        setBusy(container, false);
                    });
                    return;
                }

                if (quickAction === 'confirm_latest') {
                    confirmAction(container, 0).catch(function (error) {
                        warning(container, error.message || t('chat_provider_error', 'Unable to confirm action.'));
                    }).finally(function () {
                        setBusy(container, false);
                    });
                    return;
                }

                if (quickAction === 'cancel_latest') {
                    cancelAction(container, 0).catch(function (error) {
                        warning(container, error.message || t('chat_provider_error', 'Unable to cancel action.'));
                    }).finally(function () {
                        setBusy(container, false);
                    });
                    return;
                }

                setBusy(container, false);
                return;
            }

            var queueConfirmButton = event.target.closest('[data-chat-pending-confirm-id]');
            if (queueConfirmButton && container.contains(queueConfirmButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                var confirmId = normalizeActionId(queueConfirmButton.getAttribute('data-chat-pending-confirm-id'));
                if (!confirmId) {
                    warning(container, t('chat_action_invalid_payload', 'The action payload is invalid or incomplete.'));
                    return;
                }

                warning(container, '');
                setBusy(container, true);
                confirmAction(container, confirmId).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to confirm action.'));
                }).finally(function () {
                    setBusy(container, false);
                });
                return;
            }

            var queueCancelButton = event.target.closest('[data-chat-pending-cancel-id]');
            if (queueCancelButton && container.contains(queueCancelButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                var cancelId = normalizeActionId(queueCancelButton.getAttribute('data-chat-pending-cancel-id'));
                if (!cancelId) {
                    warning(container, t('chat_action_invalid_payload', 'The action payload is invalid or incomplete.'));
                    return;
                }

                warning(container, '');
                setBusy(container, true);
                cancelAction(container, cancelId).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to cancel action.'));
                }).finally(function () {
                    setBusy(container, false);
                });
                return;
            }

            var quoteModeButton = event.target.closest('[data-chat-quote-mode-toggle]');
            if (quoteModeButton && container.contains(quoteModeButton)) {
                event.preventDefault();
                var enableQuoteMode = !isQuoteMode(container);
                setQuoteMode(container, enableQuoteMode);
                if (!enableQuoteMode) {
                    warning(container, t('quote_assistant_mode_off', 'Quote assistant mode is off.'));
                }
                return;
            }

            var quoteFillMissingButton = event.target.closest('[data-chat-quote-fill-missing]');
            if (quoteFillMissingButton && container.contains(quoteFillMissingButton)) {
                event.preventDefault();
                openQuoteMissingModal(container);
                return;
            }

            var quoteMissingModalSubmit = event.target.closest('[data-chat-quote-missing-modal-submit]');
            if (quoteMissingModalSubmit && container.contains(quoteMissingModalSubmit)) {
                event.preventDefault();
                submitQuoteMissingModal(container);
                return;
            }

            var quoteRemoveLineButton = event.target.closest('[data-chat-quote-remove-line]');
            if (quoteRemoveLineButton && container.contains(quoteRemoveLineButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                var removeLineUid = String(quoteRemoveLineButton.getAttribute('data-chat-quote-remove-line') || '');
                if (!removeLineUid) {
                    return;
                }

                warning(container, '');
                setBusy(container, true);
                processQuoteWizard(container, { selected_remove_line_uid: removeLineUid }).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to update quote draft.'));
                }).finally(function () {
                    setBusy(container, false);
                });
                return;
            }

            var quotePickButton = event.target.closest('[data-chat-quote-pick]');
            if (quotePickButton && container.contains(quotePickButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                var pickType = String(quotePickButton.getAttribute('data-chat-quote-pick') || '');
                var pickPayload = {};
                if (pickType === 'contact') {
                    pickPayload.selected_contact_id = Number(quotePickButton.getAttribute('data-chat-quote-contact-id') || 0);
                } else if (pickType === 'product') {
                    pickPayload.selected_product_id = Number(quotePickButton.getAttribute('data-chat-quote-product-id') || 0);
                    pickPayload.selected_line_uid = String(quotePickButton.getAttribute('data-chat-quote-line-uid') || '');
                }

                warning(container, '');
                setBusy(container, true);
                processQuoteWizard(container, pickPayload).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to update quote draft.'));
                }).finally(function () {
                    setBusy(container, false);
                });
                return;
            }

            var quoteConfirmButton = event.target.closest('[data-chat-quote-confirm]');
            if (quoteConfirmButton && container.contains(quoteConfirmButton)) {
                event.preventDefault();
                if (isBusy(container)) {
                    return;
                }

                warning(container, '');
                setBusy(container, true);
                confirmQuoteWizard(container).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to confirm quote draft.'));
                }).finally(function () {
                    setBusy(container, false);
                });
                return;
            }

            var newConversation = event.target.closest('[data-chat-new-conversation]');
            if (newConversation && container.contains(newConversation)) {
                event.preventDefault();
                createConversation(container).catch(function (error) {
                    warning(container, error.message || t('chat_provider_error', 'Unable to create conversation.'));
                });
                return;
            }

            var actionButton = event.target.closest('[data-chat-action]');
            if (actionButton && container.contains(actionButton)) {
                event.preventDefault();
                onAssistantAction(container, actionButton);
                return;
            }

            var shareButton = event.target.closest('[data-chat-share]');
            if (shareButton && container.contains(shareButton)) {
                event.preventDefault();
                share(container);
                return;
            }

            var exportButton = event.target.closest('[data-chat-export]');
            if (exportButton && container.contains(exportButton)) {
                event.preventDefault();
                exportConversation(container, exportButton.getAttribute('data-format') || 'markdown');
                return;
            }

            var replyButton = event.target.closest('[data-chat-suggested-reply]');
            if (replyButton && container.contains(replyButton)) {
                event.preventDefault();
                var input = container.querySelector('[data-kt-element="input"]');
                if (!input) {
                    return;
                }

                input.value = String(replyButton.getAttribute('data-chat-suggested-text') || '');
                input.focus();
            }
        });

        var input = container.querySelector('[data-kt-element="input"]');
        if (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    send(container);
                }
            });
        }

        var providerNode = container.querySelector('[data-chat-provider-select]');
        if (providerNode) {
            providerNode.addEventListener('change', function () {
                updateModelOptions(container);
            });
        }

        var quoteMissingInputNode = container.querySelector('[data-chat-quote-missing-modal-input]');
        if (quoteMissingInputNode) {
            quoteMissingInputNode.addEventListener('input', function () {
                var modalNode = getQuoteMissingModalNode(container);
                var errorNode = modalNode ? modalNode.querySelector('[data-chat-quote-missing-modal-error]') : null;
                if (errorNode) {
                    errorNode.textContent = '';
                }
            });
        }

        if (container.getAttribute('data-aichat-chat-container') !== 'drawer') {
            bootstrap(container);
        }
    }

    function bindDrawerBootstrap() {
        var drawer = document.getElementById('kt_drawer_chat');
        if (!drawer) {
            return;
        }

        drawer.addEventListener('kt.drawer.shown', function () {
            bootstrap(drawer);
        });

        if (!document.__aichatDrawerToggleBound) {
            document.__aichatDrawerToggleBound = true;
            document.addEventListener('click', function (event) {
                var toggle = event.target && event.target.closest ? event.target.closest('#kt_drawer_chat_toggle') : null;
                if (!toggle) {
                    return;
                }

                setTimeout(function () {
                    bootstrap(drawer);
                }, 250);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('[data-aichat-chat-container]');
        Array.prototype.forEach.call(containers, function (container) {
            bindContainer(container);
        });

        bindDrawerBootstrap();
    });
})();
