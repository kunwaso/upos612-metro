(function () {
    'use strict';
    var config = window.__projectxChatConfig || null;
    if (!config || !config.enabled) { return; }
    var i18n = config.i18n || {};

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function escapeHtml(value) { var d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML; }
    function routeWithParams(template, params) {
        var out = String(template || '');
        Object.keys(params || {}).forEach(function (key) {
            out = out.replace(new RegExp('__' + key + '__', 'g'), params[key]);
        });
        return out;
    }
    function routeWithId(template, id) { return routeWithParams(template, { ID: id, MESSAGE_ID: id }); }
    function t(key, fallback) { return i18n[key] || fallback; }
    function nowLabel() { return 'Just now'; }
    function getCurrentFabricId() {
        var scopedFabricNode = document.querySelector('[data-projectx-fabric-id]');
        if (scopedFabricNode) {
            var scopedId = Number(scopedFabricNode.getAttribute('data-projectx-fabric-id') || 0);
            if (Number.isFinite(scopedId) && scopedId > 0) { return scopedId; }
        }
        var id = Number(window.__projectxCurrentFabricId || 0);
        return Number.isFinite(id) && id > 0 ? id : null;
    }
    function getCurrentTrimId() {
        var scopedTrimNode = document.querySelector('[data-projectx-trim-id]');
        if (scopedTrimNode) {
            var scopedId = Number(scopedTrimNode.getAttribute('data-projectx-trim-id') || 0);
            if (Number.isFinite(scopedId) && scopedId > 0) { return scopedId; }
        }
        var id = Number(window.__projectxCurrentTrimId || 0);
        return Number.isFinite(id) && id > 0 ? id : null;
    }
    function getCurrentQuoteId() {
        var scopedQuoteNode = document.querySelector('[data-projectx-quote-id]');
        if (scopedQuoteNode) {
            var scopedId = Number(scopedQuoteNode.getAttribute('data-projectx-quote-id') || 0);
            if (Number.isFinite(scopedId) && scopedId > 0) { return scopedId; }
        }
        var id = Number(window.__projectxCurrentQuoteId || 0);
        return Number.isFinite(id) && id > 0 ? id : null;
    }
    function getCurrentTransactionId() {
        var scopedTransactionNode = document.querySelector('[data-projectx-transaction-id]');
        if (scopedTransactionNode) {
            var scopedId = Number(scopedTransactionNode.getAttribute('data-projectx-transaction-id') || 0);
            if (Number.isFinite(scopedId) && scopedId > 0) { return scopedId; }
        }
        var id = Number(window.__projectxCurrentTransactionId || 0);
        return Number.isFinite(id) && id > 0 ? id : null;
    }
    function getConversationScopeFabricId(container) {
        var kind = (container && container.getAttribute('data-projectx-chat-container')) || '';
        if (kind !== 'drawer') { return null; }
        return getCurrentFabricId();
    }
    function toIsoDate(value) {
        if (!value) { return null; }
        var d = new Date(value);
        if (Number.isNaN(d.getTime())) { return null; }
        return d;
    }
    function formatRelativeTime(value) {
        var d = toIsoDate(value);
        if (!d) { return ''; }
        var now = Date.now();
        var diffMs = now - d.getTime();
        var future = diffMs < 0;
        var abs = Math.abs(diffMs);
        var sec = Math.round(abs / 1000);
        if (sec < 45) { return future ? 'in a few seconds' : 'just now'; }
        var min = Math.round(sec / 60);
        if (min < 60) { return future ? ('in ' + min + 'm') : (min + 'm ago'); }
        var hr = Math.round(min / 60);
        if (hr < 24) { return future ? ('in ' + hr + 'h') : (hr + 'h ago'); }
        var day = Math.round(hr / 24);
        if (day < 30) { return future ? ('in ' + day + 'd') : (day + 'd ago'); }
        var month = Math.round(day / 30);
        if (month < 12) { return future ? ('in ' + month + 'mo') : (month + 'mo ago'); }
        var year = Math.round(month / 12);
        return future ? ('in ' + year + 'y') : (year + 'y ago');
    }
    function formatConversationDate(value) {
        var d = toIsoDate(value);
        if (!d) { return ''; }
        var explicit = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        var rel = formatRelativeTime(value);
        return rel ? (explicit + ' (' + rel + ')') : explicit;
    }
    function buildListUrl(container) {
        var base = String((config.routes || {}).list_url || '');
        if (!base) { return ''; }
        var fabricId = getConversationScopeFabricId(container);
        if (!fabricId) { return base; }
        var joiner = base.indexOf('?') === -1 ? '?' : '&';
        return base + joiner + 'fabric_id=' + encodeURIComponent(String(fabricId));
    }
    function getFabricUpdateFieldTypes() {
        var fallback = {
            currency: 'string',
            price_per_meter: 'decimal',
            country_of_origin: 'string',
            mill_article_no: 'string'
        };
        var raw = (((config || {}).features || {}).fabric_update_fields || {}).types;
        if (!raw || typeof raw !== 'object' || Array.isArray(raw)) { return fallback; }
        var out = {};
        Object.keys(raw).forEach(function (field) {
            var normalized = String(raw[field] || '').toLowerCase().trim();
            if (['string', 'decimal', 'integer', 'boolean', 'date'].indexOf(normalized) === -1) { return; }
            out[field] = normalized;
        });
        return Object.keys(out).length ? out : fallback;
    }
    function getAllowedFabricUpdateFields() {
        var configured = (((config || {}).features || {}).fabric_update_fields || {}).allowed;
        if (Array.isArray(configured) && configured.length) {
            return configured.map(function (field) { return String(field || '').trim(); }).filter(function (field) { return field !== ''; });
        }
        return Object.keys(getFabricUpdateFieldTypes());
    }
    function parseBooleanValue(value) {
        if (value === true || value === false) { return value; }
        if (value == null || value === '') { return null; }
        var text = String(value).trim().toLowerCase();
        if (['1', 'true', 'yes', 'on'].indexOf(text) !== -1) { return true; }
        if (['0', 'false', 'no', 'off'].indexOf(text) !== -1) { return false; }
        return null;
    }
    function formatDateForUpdate(value) {
        if (value == null || value === '') { return null; }
        var d = new Date(value);
        if (Number.isNaN(d.getTime())) { return null; }
        return d.toISOString().slice(0, 10);
    }
    function sanitizeFabricUpdates(updates) {
        if (!updates || typeof updates !== 'object' || Array.isArray(updates)) { return null; }
        var types = getFabricUpdateFieldTypes();
        var allowed = getAllowedFabricUpdateFields();
        var keys = Object.keys(updates);
        if (!keys.length) { return null; }
        var invalid = keys.filter(function (k) { return allowed.indexOf(k) === -1; });
        if (invalid.length) { return null; }
        var out = {};
        allowed.forEach(function (field) {
            if (!Object.prototype.hasOwnProperty.call(updates, field)) { return; }
            var value = updates[field];
            var type = types[field] || 'string';
            if (value === '' || value == null) {
                out[field] = null;
                return;
            }

            if (type === 'decimal') {
                var decimalValue = Number(value);
                if (!Number.isFinite(decimalValue)) { return; }
                out[field] = decimalValue;
                return;
            }

            if (type === 'integer') {
                var integerValue = Number(value);
                if (!Number.isFinite(integerValue) || !Number.isInteger(integerValue)) { return; }
                out[field] = integerValue;
                return;
            }

            if (type === 'boolean') {
                var booleanValue = parseBooleanValue(value);
                if (booleanValue === null) { return; }
                out[field] = booleanValue;
                return;
            }

            if (type === 'date') {
                var formattedDate = formatDateForUpdate(value);
                if (!formattedDate) { return; }
                out[field] = formattedDate;
                return;
            }

            out[field] = String(value).trim();
        });
        return Object.keys(out).length ? out : null;
    }
    function parseFabricUpdatesFromMessage(content) {
        if (!content) { return null; }
        var text = String(content);
        var candidates = [];
        var fencedRegex = /```json\s*([\s\S]*?)```/gi;
        var fencedMatch;
        while ((fencedMatch = fencedRegex.exec(text)) !== null) {
            if (fencedMatch[1] && fencedMatch[1].indexOf('"fabric_updates"') !== -1) {
                candidates.push(fencedMatch[1].trim());
            }
        }

        var directObjectMatch = text.match(/\{\s*"fabric_updates"\s*:\s*\{[\s\S]*?\}\s*\}/i);
        if (directObjectMatch && directObjectMatch[0]) {
            candidates.push(directObjectMatch[0].trim());
        }

        for (var i = 0; i < candidates.length; i += 1) {
            try {
                var parsed = JSON.parse(candidates[i]);
                var updates = sanitizeFabricUpdates(parsed && parsed.fabric_updates ? parsed.fabric_updates : null);
                if (updates) { return updates; }
            } catch (e) {}
        }
        return null;
    }

    function parseEventStreamChunk(state, text, cb) {
        state.buffer += text;
        var lines = state.buffer.split(/\r?\n/);
        state.buffer = lines.pop() || '';
        var eventName = 'message';
        lines.forEach(function (line) {
            if (!line) { eventName = 'message'; return; }
            if (line.indexOf('event:') === 0) { eventName = line.slice(6).trim(); return; }
            if (line.indexOf('data:') === 0) {
                var dataText = line.slice(5).trim();
                if (!dataText) { return; }
                try { cb(eventName, JSON.parse(dataText)); } catch (e) {}
            }
        });
    }

    function request(url, options) {
        var opts = options || {};
        opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }, opts.headers || {});
        return fetch(url, opts).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (!res.ok || json.success === false) { throw new Error((json && (json.message || json.msg)) || t('request_failed', 'Request failed.')); }
                return json;
            });
        });
    }

    function getActiveConversation(container) { var hidden = container.querySelector('[data-chat-active-conversation]'); return hidden ? hidden.value : ''; }
    function setActiveConversation(container, id) { var hidden = container.querySelector('[data-chat-active-conversation]'); if (hidden) { hidden.value = id || ''; } }
    function resetIdleTimer(container) {
        var mins = Number((config.features || {}).idle_timeout_minutes || 0);
        if (!mins || mins < 1) { return; }
        clearTimeout(container.__chatIdleTimer);
        container.__chatIdleTimer = setTimeout(function () {
            var input = container.querySelector('[data-kt-element="input"]');
            if (input) { input.disabled = true; }
            showWarning(container, t('idle_message', 'Chat session is idle. Refresh the page to continue.'));
        }, mins * 60 * 1000);
    }
    function toggleBusy(container, busy) {
        container.__chatBusy = !!busy;
        var input = container.querySelector('[data-kt-element="input"]');
        var send = container.querySelector('[data-kt-element="send"]');
        if (input) { input.disabled = !!busy; }
        if (send) { send.disabled = !!busy; }
    }
    function showWarning(container, message) {
        var n = container.querySelector('[data-chat-warning-inline]');
        if (!n) { return; }
        n.textContent = message || '';
        if (message) { setTimeout(function () { n.textContent = ''; }, 6000); }
    }
    function appendChatNotice(container, message, tone) {
        if (!message) { return; }
        var messagesEl = container.querySelector('[data-kt-element="messages"]');
        if (!messagesEl) { return; }
        var node = document.createElement('div');
        node.className = 'text-center fs-7 fw-semibold py-3';
        node.classList.add(tone === 'success' ? 'text-success' : 'text-muted');
        node.setAttribute('data-chat-inline-notice', '1');
        node.textContent = String(message);
        messagesEl.appendChild(node);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    function buildPreviewText(value, maxWords) {
        var limit = Number(maxWords || 8);
        var normalized = String(value || '').replace(/\s+/g, ' ').trim();
        if (!normalized) { return '-'; }
        var words = normalized.split(' ');
        if (words.length <= limit) { return normalized; }
        return words.slice(0, limit).join(' ') + '...';
    }
    function buildFabricPayload(container) {
        var payload = {};
        var toggle = container.querySelector('[data-chat-fabric-toggle]');
        var currentFabricId = getCurrentFabricId();
        if (toggle && toggle.checked && currentFabricId) {
            payload.fabric_insight = 1;
            payload.fabric_id = currentFabricId;
        }
        var currentTrimId = getCurrentTrimId();
        if (currentTrimId) {
            payload.trim_id = currentTrimId;
        }
        var currentQuoteId = getCurrentQuoteId();
        if (currentQuoteId) {
            payload.quote_id = currentQuoteId;
        }
        var currentTransactionId = getCurrentTransactionId();
        if (currentTransactionId) {
            payload.transaction_id = currentTransactionId;
        }
        return payload;
    }
    function renderSuggestedReplies(container, replies) {
        var wrap = container.querySelector('[data-chat-suggested-replies]');
        if (!wrap) { return; }
        wrap.innerHTML = '';
        (replies || []).slice(0, 3).forEach(function (reply) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'btn btn-sm btn-light-primary'; b.textContent = reply;
            b.addEventListener('click', function () { var i = container.querySelector('[data-kt-element="input"]'); if (i) { i.value = reply; i.focus(); } });
            wrap.appendChild(b);
        });
    }

    function setMessageMeta(row, msg) {
        row.__chatMessage = msg || {};
        row.setAttribute('data-chat-message-id', msg && msg.id ? String(msg.id) : '');
        row.setAttribute('data-chat-feedback-value', (msg && msg.feedback_value) || '');
        row.setAttribute('data-chat-can-regenerate', msg && msg.can_regenerate ? '1' : '0');
        row.setAttribute('data-chat-message-role', (msg && msg.role) || 'assistant');
        row.setAttribute('data-chat-message-fabric-id', msg && msg.fabric_id ? String(msg.fabric_id) : '');
        row.setAttribute('data-chat-message-fabric-insight', msg && msg.fabric_insight ? '1' : '0');
    }
    function getMessageMeta(row) { return row ? (row.__chatMessage || null) : null; }
    function setFeedbackButtons(row, value) {
        var up = row.querySelector('[data-chat-action="feedback-up"]');
        var down = row.querySelector('[data-chat-action="feedback-down"]');
        if (up) { up.classList.toggle('active', value === 'up'); }
        if (down) { down.classList.toggle('active', value === 'down'); }
    }
    function setAssistantActionState(row, msg) {
        var id = !!(msg && msg.id);
        var regenAllowed = !!(msg && msg.can_regenerate && msg.id);
        var copy = row.querySelector('[data-chat-action="copy"]');
        var regen = row.querySelector('[data-chat-action="regenerate"]');
        var up = row.querySelector('[data-chat-action="feedback-up"]');
        var down = row.querySelector('[data-chat-action="feedback-down"]');
        if (copy) { copy.disabled = false; }
        if (regen) { regen.disabled = !regenAllowed; }
        if (up) { up.disabled = !id; }
        if (down) { down.disabled = !id; }
        setFeedbackButtons(row, (msg && msg.feedback_value) || null);
    }
    function setMessageContent(row, role, msg, streamingText) {
        var node = row.querySelector('[data-kt-element="message-text"]');
        if (!node) { return; }
        if (role === 'assistant') {
            if (streamingText != null) { node.innerHTML = escapeHtml(streamingText); return; }
            if (msg && msg.content_html) { node.innerHTML = String(msg.content_html); return; }
            node.innerHTML = escapeHtml((msg && msg.content) || '');
            return;
        }
        node.innerHTML = escapeHtml((msg && msg.content) || '');
    }
    function normalizeStreamErrorMessage(message) {
        var value = String(message || '');
        if (!value) { return t('request_failed', 'Request failed.'); }
        var lower = value.toLowerCase();
        if (
            lower.indexOf('quota') !== -1
            || lower.indexOf('rate limit') !== -1
            || lower.indexOf('too many requests') !== -1
            || lower.indexOf('resource has been exhausted') !== -1
        ) {
            return t('quota_exceeded', 'Quota exceeded for this model. Wait about 60 seconds and try again.');
        }
        return value;
    }
    function applyStreamErrorToAssistantNode(container, assistantNode, payload) {
        if (!assistantNode) { return; }
        var msg = payload && payload.error_message ? payload.error_message : null;
        var text = normalizeStreamErrorMessage((msg && msg.content) || (payload && payload.message) || t('error', 'Error'));
        var fallback = {
            id: msg && msg.id ? msg.id : null,
            role: 'assistant',
            content: text,
            content_html: null,
            feedback_value: null,
            can_regenerate: false,
            fabric_id: msg && msg.fabric_id ? msg.fabric_id : null,
            fabric_insight: !!(msg && msg.fabric_insight),
        };
        setMessageMeta(assistantNode, fallback);
        setMessageContent(assistantNode, 'assistant', fallback);
        setAssistantActionState(assistantNode, fallback);
    }
    function hasApplyFabricPermission() {
        return !!((config.permissions || {}).can_apply_fabric_updates);
    }
    function canRenderFabricApplyAction(msg) {
        var pageFabricId = getCurrentFabricId();
        if (!hasApplyFabricPermission() || !pageFabricId) { return false; }
        if (!msg || msg.role !== 'assistant' || !msg.id) { return false; }
        if (!msg.fabric_insight) { return false; }
        var messageFabricId = Number(msg.fabric_id || 0);
        if (!messageFabricId || messageFabricId !== Number(pageFabricId)) { return false; }
        return true;
    }
    function getFabricUpdateFieldLabel(field) {
        var aliases = {
            currency: 'Currency',
            fabric_sku: 'Fabric SKU',
            payment_terms: 'Payment terms',
            due_date: 'Due date',
            fds_date: 'FDS date',
            swatch_submit_date: 'Swatch submit date'
        };
        if (aliases[field]) { return aliases[field]; }
        return String(field || '').replace(/_/g, ' ').replace(/\b\w/g, function (match) { return match.toUpperCase(); });
    }
    function formatFabricUpdateValue(field, value) {
        if (value == null) { return '-'; }
        if (typeof value === 'string' && value.trim() === '') { return '-'; }
        var type = getFabricUpdateFieldTypes()[field] || 'string';
        if (type === 'decimal') {
            var decimalValue = Number(value);
            if (Number.isFinite(decimalValue)) { return decimalValue.toFixed(4).replace(/\.?0+$/, ''); }
        } else if (type === 'integer') {
            var integerValue = Number(value);
            if (Number.isFinite(integerValue) && Number.isInteger(integerValue)) { return String(integerValue); }
        } else if (type === 'boolean') {
            var boolValue = parseBooleanValue(value);
            if (boolValue === true) { return 'true'; }
            if (boolValue === false) { return 'false'; }
        } else if (type === 'date') {
            var dateValue = formatDateForUpdate(value);
            if (dateValue) { return dateValue; }
        }
        return String(value);
    }
    function formatFabricUpdateSummary(updates) {
        var keys = Object.keys(updates || {});
        if (!keys.length) { return ''; }
        return keys.map(function (key) {
            return getFabricUpdateFieldLabel(key) + ' = ' + formatFabricUpdateValue(key, updates[key]);
        }).join(' | ');
    }
    function buildApplySuccessText(json, fallbackUpdates) {
        var base = (json && (json.message || json.msg)) || t('apply_success', 'Fabric updated successfully.');
        var data = (json && json.data) || {};
        var summary = '';
        if (data.applied_summary) {
            summary = String(data.applied_summary).trim();
        }

        if (!summary) {
            var detail = formatFabricUpdateSummary(data.applied_changes || fallbackUpdates || {}).split(' | ').join(', ');
            if (detail) {
                summary = t('apply_success_detail', 'Updated: :details').replace(':details', detail);
            }
        }

        if (!summary) { return base; }
        if (summary === base) { return summary; }

        return base + ' ' + summary;
    }
    function applyFabricUpdatesFromRow(container, row) {
        var msg = getMessageMeta(row);
        var updates = row.__chatFabricUpdates || null;
        var fabricId = getCurrentFabricId();
        if (!msg || !msg.id || !updates || !fabricId) {
            showWarning(container, t('apply_error', 'Unable to apply fabric changes.'));
            return Promise.resolve();
        }
        if (!confirm(t('apply_confirm', 'Apply these values to the current fabric?'))) {
            return Promise.resolve();
        }
        if (!config.routes || !config.routes.apply_fabric_updates_url_template) {
            showWarning(container, t('apply_error', 'Unable to apply fabric changes.'));
            return Promise.resolve();
        }
        var url = routeWithParams(config.routes.apply_fabric_updates_url_template, {
            FABRIC_ID: fabricId,
            MESSAGE_ID: msg.id,
        });
        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ updates: updates }),
        }).then(function (json) {
            var successText = buildApplySuccessText(json, updates);
            showWarning(container, successText);
            appendChatNotice(container, successText, 'success');
        }).catch(function (e) {
            showWarning(container, e.message || t('apply_error', 'Unable to apply fabric changes.'));
        });
    }
    function renderFabricUpdateAction(container, row) {
        if (!row) { return; }
        var existing = row.querySelector('[data-chat-fabric-update-wrap]');
        if (existing) { existing.remove(); }

        var msg = getMessageMeta(row);
        if (!canRenderFabricApplyAction(msg)) { return; }
        var updates = parseFabricUpdatesFromMessage(msg.content || '');
        if (!updates) { return; }
        row.__chatFabricUpdates = updates;

        var actionHost = row.querySelector('[data-chat-assistant-actions]');
        if (!actionHost) { return; }

        var wrap = document.createElement('div');
        wrap.className = 'mt-2';
        wrap.setAttribute('data-chat-fabric-update-wrap', '1');

        var summary = document.createElement('div');
        summary.className = 'fs-8 text-gray-700 mb-2';
        summary.textContent = formatFabricUpdateSummary(updates);

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-light-primary';
        button.setAttribute('data-chat-action', 'apply-fabric-updates');
        button.textContent = t('apply_fabric_changes', 'Apply changes to fabric');
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            applyFabricUpdatesFromRow(container, row);
        });

        wrap.appendChild(summary);
        wrap.appendChild(button);
        actionHost.parentNode.appendChild(wrap);
    }

    function writeClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) { return navigator.clipboard.writeText(text); }
        return new Promise(function (resolve, reject) {
            try {
                var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
                document.body.appendChild(t); t.select(); var ok = document.execCommand('copy'); document.body.removeChild(t);
                if (!ok) { reject(new Error(t('copy_failed', 'Copy failed.'))); return; } resolve();
            } catch (e) { reject(e); }
        });
    }

    function saveFeedback(container, row, value) {
        var msg = getMessageMeta(row);
        if (!msg || !msg.id || !config.routes || !config.routes.feedback_url_template) { return Promise.resolve(); }
        return request(routeWithId(config.routes.feedback_url_template, msg.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ feedback: value })
        }).then(function () {
            msg.feedback_value = value;
            setMessageMeta(row, msg);
            setAssistantActionState(row, msg);
        });
    }

    function bindAssistantActions(container, row) {
        var copy = row.querySelector('[data-chat-action="copy"]');
        var regen = row.querySelector('[data-chat-action="regenerate"]');
        var up = row.querySelector('[data-chat-action="feedback-up"]');
        var down = row.querySelector('[data-chat-action="feedback-down"]');
        if (copy) {
            copy.onclick = function () {
                var msg = getMessageMeta(row);
                var raw = (msg && msg.content) || ((row.querySelector('[data-kt-element="message-text"]') || {}).innerText || '');
                if (!raw) { return; }
                writeClipboard(String(raw)).then(function () { showWarning(container, t('copied', 'Copied to clipboard.')); }).catch(function () { showWarning(container, t('copy_failed', 'Copy failed.')); });
            };
        }
        if (regen) { regen.onclick = function () { regenerateMessage(container, row); }; }
        if (up) { up.onclick = function () { saveFeedback(container, row, 'up').catch(function (e) { showWarning(container, e.message); }); }; }
        if (down) { down.onclick = function () { saveFeedback(container, row, 'down').catch(function (e) { showWarning(container, e.message); }); }; }
        setAssistantActionState(row, getMessageMeta(row));
        renderFabricUpdateAction(container, row);
    }

    function createMessageNode(container, role, message) {
        var messagesEl = container.querySelector('[data-kt-element="messages"]');
        var key = role === 'user' ? 'template-out' : 'template-in';
        var template = messagesEl ? messagesEl.querySelector('[data-kt-element="' + key + '"]') : null;
        if (!template) { return null; }
        var msg = message || {}; if (!msg.role) { msg.role = role; }
        var clone = template.cloneNode(true); clone.classList.remove('d-none');
        clone.removeAttribute('data-kt-element');
        setMessageMeta(clone, msg); setMessageContent(clone, role, msg);
        var smalls = clone.querySelectorAll('.text-muted.fs-7'); if (smalls.length > 0) { smalls[smalls.length - 1].textContent = nowLabel(); }
        if (role === 'assistant') { bindAssistantActions(container, clone); }
        messagesEl.appendChild(clone); messagesEl.scrollTop = messagesEl.scrollHeight;
        return clone;
    }

    function renderMessages(container, messages) {
        var messagesEl = container.querySelector('[data-kt-element="messages"]');
        if (!messagesEl) { return; }
        Array.prototype.slice.call(messagesEl.children).forEach(function (node) {
            var t = node.getAttribute('data-kt-element');
            var isTemplate = (t === 'template-out' || t === 'template-in') && node.classList.contains('d-none');
            var isEmpty = node.hasAttribute('data-chat-empty-state');
            if (!isTemplate && !isEmpty) { node.remove(); }
        });
        var empty = messagesEl.querySelector('[data-chat-empty-state]'); if (empty) { empty.style.display = (messages && messages.length) ? 'none' : ''; }
        (messages || []).forEach(function (msg) { createMessageNode(container, msg.role === 'user' ? 'user' : 'assistant', msg); });
    }
    function setConversationTitle(container, value) {
        var heading = container.querySelector('[data-chat-conversation-title]');
        if (!heading) { return; }
        heading.textContent = value || t('new_chat', 'New Chat');
    }
    function clearConversationState(container) {
        setActiveConversation(container, '');
        renderMessages(container, []);
        setConversationTitle(container, t('new_chat', 'New Chat'));
    }

    function renderConversationList(container, conversations, activeId) {
        var listEl = container.querySelector('[data-chat-conversations-list]');
        if (!listEl) { return; }
        listEl.innerHTML = '';
        if (!conversations.length) {
            var empty = document.createElement('div'); empty.className = 'text-muted text-center py-10'; empty.textContent = t('no_conversations', 'No conversations yet.'); listEl.appendChild(empty); return;
        }
        var canEdit = !!((config.permissions || {}).can_edit);
        conversations.forEach(function (c) {
            var row = document.createElement('div');
            row.className = 'd-flex align-items-center justify-content-between py-3 border-bottom border-gray-200 cursor-pointer';
            if (String(activeId || '') === String(c.id)) { row.classList.add('bg-light-primary'); }
            row.setAttribute('data-chat-conversation-item', '1'); row.setAttribute('data-conversation-id', c.id);
            var deleteAction = '';
            if (canEdit) {
                deleteAction = '<button type="button" class="btn btn-sm btn-icon btn-active-color-danger" data-chat-delete-conversation data-conversation-id="' + escapeHtml(c.id) + '" title="' + escapeHtml(t('delete_conversation', 'Delete conversation')) + '" aria-label="' + escapeHtml(t('delete_conversation', 'Delete conversation')) + '"><i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></button>';
            }
            row.innerHTML = '' +
                '<div class="d-flex align-items-center flex-grow-1 min-w-0 me-3">' +
                    '<div class="symbol symbol-35px symbol-circle flex-shrink-0">' +
                        '<span class="symbol-label bg-light-info text-info fw-bold">A</span>' +
                    '</div>' +
                    '<div class="ms-3 min-w-0 flex-grow-1">' +
                        '<div class="fw-bold text-gray-900 fs-6 text-truncate">' + escapeHtml(c.title || t('new_chat', 'New Chat')) + '</div>' +
                        '<div class="text-muted fs-7 text-truncate">' + escapeHtml(buildPreviewText(c.last_message_preview, 6)) + '</div>' +
                        '<div class="text-muted fs-8">' + escapeHtml(formatConversationDate(c.updated_at || '')) + '</div>' +
                    '</div>' +
                '</div>' +
                deleteAction;
            listEl.appendChild(row);
        });
    }
    function getConversationFromState(container, conversationId) {
        var list = container.__chatConversations || [];
        var id = String(conversationId || '');
        for (var i = 0; i < list.length; i += 1) {
            if (String(list[i].id) === id) { return list[i]; }
        }
        return null;
    }
    function ensureActiveConversationExists(container) {
        var activeId = getActiveConversation(container);
        if (!activeId) { return; }
        var exists = !!getConversationFromState(container, activeId);
        if (!exists) { clearConversationState(container); }
    }
    function deleteConversation(container, conversationId) {
        if (!conversationId || !config.routes || !config.routes.destroy_url_template) { return Promise.resolve(); }
        if (!confirm(t('delete_confirm', 'Delete this conversation? This cannot be undone.'))) {
            return Promise.resolve();
        }
        return request(routeWithId(config.routes.destroy_url_template, conversationId), {
            method: 'DELETE',
        }).then(function () {
            var current = container.__chatConversations || [];
            var remaining = current.filter(function (item) { return String(item.id) !== String(conversationId); });
            container.__chatConversations = remaining;
            var activeIdBeforeDelete = String(getActiveConversation(container) || '');
            var wasActive = activeIdBeforeDelete === String(conversationId);

            // When the deleted conversation was the one shown, clear the main body immediately
            // so the user sees content disappear without waiting for refresh or next load.
            if (wasActive) {
                renderMessages(container, []);
                setConversationTitle(container, t('new_chat', 'New Chat'));
            }

            // If no conversations remain, reset full conversation state.
            if (!remaining.length) {
                clearConversationState(container);
                renderConversationList(container, remaining, '');
                showWarning(container, t('delete_success', 'Conversation deleted successfully.'));
                return Promise.resolve();
            }

            renderConversationList(container, remaining, wasActive ? '' : getActiveConversation(container));
            if (wasActive) {
                setActiveConversation(container, remaining[0].id);
                return loadConversation(container, remaining[0].id).then(function () {
                    showWarning(container, t('delete_success', 'Conversation deleted successfully.'));
                });
            }
            showWarning(container, t('delete_success', 'Conversation deleted successfully.'));
            return Promise.resolve();
        }).catch(function (e) {
            showWarning(container, e.message || t('request_failed', 'Request failed.'));
        });
    }
    function bindConversationListHandlers(container) {
        var listEl = container.querySelector('[data-chat-conversations-list]');
        if (!listEl || listEl.__chatListBound) { return; }
        listEl.__chatListBound = true;
        listEl.addEventListener('click', function (event) {
            var deleteBtn = event.target.closest('[data-chat-delete-conversation]');
            if (deleteBtn && listEl.contains(deleteBtn)) {
                event.preventDefault();
                event.stopPropagation();
                var deleteId = deleteBtn.getAttribute('data-conversation-id') || '';
                if (deleteId) { deleteConversation(container, deleteId); }
                return;
            }
            var row = event.target.closest('[data-chat-conversation-item]');
            if (!row || !listEl.contains(row)) { return; }
            var conversationId = row.getAttribute('data-conversation-id') || '';
            if (conversationId) { loadConversation(container, conversationId); }
        });
    }

    function loadConversations(container) {
        var listUrl = buildListUrl(container);
        return request(listUrl).then(function (json) {
            var conv = (json.data || []).map(function (i) { return { id: i.id, fabric_id: i.fabric_id || null, title: i.title || t('new_chat', 'New Chat'), last_message_preview: i.last_message_preview || '', updated_at: i.updated_at || '' }; });
            container.__chatConversations = conv;
            ensureActiveConversationExists(container);
            renderConversationList(container, conv, getActiveConversation(container));
            return conv;
        });
    }
    function loadConversation(container, conversationId) {
        if (!conversationId) { clearConversationState(container); return Promise.resolve(null); }
        setActiveConversation(container, conversationId);
        return request(routeWithId(config.routes.conversation_url_template, conversationId)).then(function (json) {
            var payload = json.data || {}; var conv = payload.conversation || null;
            renderMessages(container, payload.messages || []);
            if (conv) { setConversationTitle(container, conv.title || t('new_chat', 'New Chat')); }
            renderConversationList(container, container.__chatConversations || [], conversationId);
            return payload;
        }).catch(function (e) { showWarning(container, e.message); return null; });
    }
    function createConversation(container) {
        var payload = { title: '' };
        var scopedFabricId = getConversationScopeFabricId(container);
        if (scopedFabricId) { payload.fabric_id = scopedFabricId; }
        return request(config.routes.create_url, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
        }).then(function (json) {
            var conv = json.data || null; if (!conv || !conv.id) { throw new Error(t('request_failed', 'Unable to create conversation.')); }
            setActiveConversation(container, conv.id);
            return loadConversations(container).then(function () { return loadConversation(container, conv.id); });
        });
    }

    function streamWithSse(url, payload, handlers) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            if (!response.body) { throw new Error(t('stream_not_supported', 'Streaming is not supported by this browser.')); }
            if (!response.ok) { throw new Error(t('stream_request_failed', 'Streaming request failed.')); }
            var reader = response.body.getReader(); var decoder = new TextDecoder(); var state = { buffer: '' };
            function readNext() {
                return reader.read().then(function (r) {
                    if (r.done) { return; }
                    parseEventStreamChunk(state, decoder.decode(r.value, { stream: true }), function (eventName, data) {
                        if (handlers && handlers[eventName]) { handlers[eventName](data || {}); }
                    });
                    return readNext();
                });
            }
            return readNext();
        });
    }

    function regenerateMessage(container, row) {
        if (container.__chatBusy) { return; }
        var msg = getMessageMeta(row);
        if (!msg || !msg.id || !config.routes || !config.routes.regenerate_url_template) { return; }
        toggleBusy(container, true); resetIdleTimer(container); showWarning(container, '');
        setMessageContent(row, 'assistant', null, '...');
        var contentBuffer = '';
        streamWithSse(routeWithId(config.routes.regenerate_url_template, msg.id), buildFabricPayload(container), {
            warning: function (d) { showWarning(container, d.message || t('warning', 'Warning')); },
            chunk: function (d) { contentBuffer += String(d.text || ''); setMessageContent(row, 'assistant', null, contentBuffer); },
            done: function (d) {
                if (d.assistant_message) {
                    setMessageMeta(row, d.assistant_message);
                    setMessageContent(row, 'assistant', d.assistant_message);
                    bindAssistantActions(container, row);
                }
                renderSuggestedReplies(container, d.suggested_replies || (config.features || {}).suggested_replies || []);
            },
            error: function (d) {
                var message = normalizeStreamErrorMessage(d && d.message ? d.message : t('error', 'Error'));
                setMessageMeta(row, Object.assign({}, getMessageMeta(row) || {}, {
                    role: 'assistant',
                    content: message,
                    can_regenerate: false,
                    feedback_value: null,
                }));
                setMessageContent(row, 'assistant', { content: message, content_html: null });
                setAssistantActionState(row, getMessageMeta(row) || {});
                showWarning(container, message);
            }
        }).then(function () {
            return loadConversations(container).then(function () {
                var activeId = getActiveConversation(container);
                if (!activeId) { return null; }
                return loadConversation(container, activeId);
            });
        }).catch(function (e) {
            showWarning(container, e.message || t('request_failed', 'Request failed.'));
        }).finally(function () {
            toggleBusy(container, false);
        });
    }

    function sendMessage(container) {
        if (container.__chatBusy) { return; }
        var input = container.querySelector('[data-kt-element="input"]');
        if (!input) { return; }
        var prompt = (input.value || '').trim();
        if (!prompt) { return; }
        var conversationId = getActiveConversation(container);
        var provider = (container.querySelector('[data-chat-provider-select]') || {}).value || config.default_provider;
        var model = (container.querySelector('[data-chat-model-select]') || {}).value || config.default_model;
        if (!conversationId) {
            createConversation(container).then(function () { sendMessage(container); }).catch(function (e) { showWarning(container, e.message); });
            return;
        }

        var payload = { prompt: prompt, provider: provider, model: model };
        Object.assign(payload, buildFabricPayload(container));
        input.value = '';
        resetIdleTimer(container);
        toggleBusy(container, true);
        createMessageNode(container, 'user', { role: 'user', content: prompt });
        var assistantNode = createMessageNode(container, 'assistant', { role: 'assistant', content: '...', can_regenerate: false, feedback_value: null });
        var streamUrl = routeWithId(config.routes.stream_url_template, conversationId);
        var contentBuffer = '';
        streamWithSse(streamUrl, payload, {
            warning: function (d) { showWarning(container, d.message || t('warning', 'Warning')); },
            chunk: function (d) { contentBuffer += String(d.text || ''); if (assistantNode) { setMessageContent(assistantNode, 'assistant', null, contentBuffer); } },
            done: function (d) {
                if (assistantNode && d.assistant_message) {
                    setMessageMeta(assistantNode, d.assistant_message);
                    setMessageContent(assistantNode, 'assistant', d.assistant_message);
                    bindAssistantActions(container, assistantNode);
                }
                renderSuggestedReplies(container, d.suggested_replies || (config.features || {}).suggested_replies || []);
            },
            error: function (d) {
                var message = normalizeStreamErrorMessage(d && d.message ? d.message : t('error', 'Error'));
                applyStreamErrorToAssistantNode(container, assistantNode, d || {});
                showWarning(container, message);
            }
        }).then(function () {
            return loadConversations(container).then(function () {
                var activeId = getActiveConversation(container);
                if (!activeId) { return null; }
                return loadConversation(container, activeId);
            });
        }).catch(function (e) {
            showWarning(container, e.message || t('request_failed', 'Request failed.'));
        }).finally(function () {
            toggleBusy(container, false);
        });
    }

    function updateModelSelect(container) {
        var providerSelect = container.querySelector('[data-chat-provider-select]');
        var modelSelect = container.querySelector('[data-chat-model-select]');
        if (!providerSelect || !modelSelect) { return; }
        var provider = providerSelect.value; var first = null;
        Array.prototype.forEach.call(modelSelect.options, function (opt) {
            var match = opt.getAttribute('data-provider') === provider;
            opt.hidden = !match; if (match && !first) { first = opt.value; }
        });
        if (modelSelect.selectedOptions.length === 0 || modelSelect.selectedOptions[0].hidden) { if (first) { modelSelect.value = first; } }
    }
    function isDrawerContainer(container) {
        return (container && container.getAttribute('data-projectx-chat-container')) === 'drawer';
    }
    function bootstrapContainerConversations(container, autoCreateIfEmpty) {
        if (container.__chatBootstrapPromise) {
            return container.__chatBootstrapPromise;
        }
        container.__chatBootstrapPromise = loadConversations(container).then(function (conv) {
            var activeId = getActiveConversation(container);
            if (!activeId && conv.length) {
                activeId = conv[0].id;
                setActiveConversation(container, activeId);
            }
            if (activeId) { return loadConversation(container, activeId); }
            if (autoCreateIfEmpty) { return createConversation(container); }
            clearConversationState(container);
            return null;
        }).catch(function (e) {
            showWarning(container, e.message || t('request_failed', 'Request failed.'));
            return null;
        }).finally(function () {
            container.__chatBootstrapPromise = null;
        });
        return container.__chatBootstrapPromise;
    }

    function bindContainer(container) {
        if (!container) { return; }
        if (container.__chatBound) { return; }
        container.__chatBound = true;
        bindConversationListHandlers(container);
        var fabricWrap = container.querySelector('[data-chat-fabric-toggle-wrap]');
        var fabricToggle = container.querySelector('[data-chat-fabric-toggle]');
        if (fabricWrap) {
            if (getCurrentFabricId()) {
                fabricWrap.classList.remove('d-none');
                if (fabricToggle) { fabricToggle.checked = true; }
            } else {
                fabricWrap.classList.add('d-none');
                if (fabricToggle) { fabricToggle.checked = false; }
            }
        }
        updateModelSelect(container);
        resetIdleTimer(container);
        var providerSelect = container.querySelector('[data-chat-provider-select]'); if (providerSelect) { providerSelect.addEventListener('change', function () { updateModelSelect(container); }); }
        var send = container.querySelector('[data-kt-element="send"]'); if (send) { send.addEventListener('click', function () { sendMessage(container); }); }
        var input = container.querySelector('[data-kt-element="input"]');
        if (input) {
            input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(container); } });
            input.addEventListener('input', function () { resetIdleTimer(container); });
        }
        var newBtn = container.querySelector('[data-chat-new-conversation]');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                createConversation(container).catch(function (e) { showWarning(container, e.message); });
            });
        }
        var share = container.querySelector('[data-chat-share]');
        if (share) {
            share.addEventListener('click', function () {
                var id = getActiveConversation(container); if (!id) { return; }
                request(routeWithId(config.routes.share_url_template, id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) })
                    .then(function (json) { var url = (((json || {}).data || {}).url) || ''; if (url) { window.prompt('Share URL', url); } })
                    .catch(function (e) { showWarning(container, e.message); });
            });
        }
        container.querySelectorAll('[data-chat-export]').forEach(function (button) {
            button.addEventListener('click', function () {
                var id = getActiveConversation(container); if (!id) { return; }
                var format = button.getAttribute('data-format') || 'markdown';
                window.open(routeWithId(config.routes.export_url_template, id) + '?format=' + encodeURIComponent(format), '_blank');
            });
        });
        if (!isDrawerContainer(container)) {
            bootstrapContainerConversations(container, true);
        }
    }

    function initDrawerLazyLoad() {
        var drawer = document.getElementById('kt_drawer_chat');
        if (!drawer) { return; }
        drawer.addEventListener('click', function () { resetIdleTimer(drawer); });
        drawer.addEventListener('kt.drawer.shown', function () {
            bootstrapContainerConversations(drawer, true);
        });

        if (!document.__projectxChatToggleBootstrapBound) {
            document.__projectxChatToggleBootstrapBound = true;
            document.addEventListener('click', function (event) {
                var toggle = event.target && event.target.closest
                    ? event.target.closest('#kt_drawer_chat_toggle')
                    : null;
                if (!toggle) { return; }
                setTimeout(function () { bootstrapContainerConversations(drawer, true); }, 250);
            });
        }
    }

    function parseFabricManagerRoute(url) {
        try {
            var parsedUrl = new URL(url, window.location.href);
            if (parsedUrl.origin !== window.location.origin) { return null; }
            var normalizedPath = parsedUrl.pathname.replace(/\/+$/, '');
            var match = normalizedPath.match(/\/projectx\/fabric-manager\/fabric\/(\d+)(?:\/(datasheet|budget|users|files|activity|settings))?$/i);
            if (!match) { return null; }
            return {
                url: parsedUrl.toString(),
                fabricId: Number(match[1]),
                tab: (match[2] || 'overview').toLowerCase()
            };
        } catch (e) {
            return null;
        }
    }

    function hasModifierClick(event) {
        return event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey;
    }

    function hasScriptSource(src) {
        var targetHref;
        try {
            targetHref = new URL(src, window.location.href).href;
        } catch (e) {
            return false;
        }
        return Array.prototype.some.call(document.querySelectorAll('script[src]'), function (script) {
            try {
                return new URL(script.getAttribute('src'), window.location.href).href === targetHref;
            } catch (e) {
                return false;
            }
        });
    }

    function executeScriptNode(scriptNode) {
        return new Promise(function (resolve) {
            if (!scriptNode) { resolve(); return; }

            var script = document.createElement('script');
            Array.prototype.forEach.call(scriptNode.attributes || [], function (attr) {
                script.setAttribute(attr.name, attr.value);
            });

            if (scriptNode.src) {
                if (hasScriptSource(scriptNode.src)) { resolve(); return; }
                script.src = scriptNode.src;
                script.onload = function () { resolve(); };
                script.onerror = function () { resolve(); };
                document.body.appendChild(script);
                return;
            }

            script.text = scriptNode.textContent || scriptNode.innerText || '';
            document.body.appendChild(script);
            document.body.removeChild(script);
            resolve();
        });
    }

    function executeScriptsFromHtml(html) {
        if (!html) { return Promise.resolve(); }
        var wrapper = document.createElement('div');
        wrapper.innerHTML = String(html);
        var scripts = Array.prototype.slice.call(wrapper.querySelectorAll('script'));
        var sequence = Promise.resolve();
        scripts.forEach(function (scriptNode) {
            sequence = sequence.then(function () {
                return executeScriptNode(scriptNode);
            });
        });
        return sequence;
    }

    function parseBooleanAttribute(value, fallback) {
        if (value == null || value === '') { return !!fallback; }
        var text = String(value).trim().toLowerCase();
        if (['1', 'true', 'yes', 'on'].indexOf(text) !== -1) { return true; }
        if (['0', 'false', 'no', 'off'].indexOf(text) !== -1) { return false; }
        return !!fallback;
    }

    function reinitializeSelect2In(container) {
        if (!container) { return; }
        if (typeof window.jQuery === 'undefined') { return; }
        var $ = window.jQuery;
        if (!$.fn || typeof $.fn.select2 === 'undefined') { return; }

        var nodes = container.querySelectorAll('[data-control="select2"], [data-kt-select2="true"]');
        Array.prototype.forEach.call(nodes, function (node) {
            var $node = $(node);
            if ($node.hasClass('select2-hidden-accessible')) { return; }

            var options = {
                dir: document.body.getAttribute('direction')
            };

            if (String(node.getAttribute('data-hide-search') || '').toLowerCase() === 'true') {
                options.minimumResultsForSearch = Infinity;
            }

            if (node.hasAttribute('data-placeholder')) {
                options.placeholder = node.getAttribute('data-placeholder');
            }

            if (node.hasAttribute('data-close-on-select')) {
                options.closeOnSelect = parseBooleanAttribute(node.getAttribute('data-close-on-select'), true);
            }

            if (node.hasAttribute('data-allow-clear')) {
                options.allowClear = parseBooleanAttribute(node.getAttribute('data-allow-clear'), false);
            }

            if (node.hasAttribute('data-dropdown-parent')) {
                var dropdownParentSelector = node.getAttribute('data-dropdown-parent');
                var dropdownParent = dropdownParentSelector ? document.querySelector(dropdownParentSelector) : null;
                if (dropdownParent) {
                    options.dropdownParent = $(dropdownParent);
                }
            }

            $node.select2(options);
            node.setAttribute('data-kt-initialized', '1');
        });
    }

    function reinitializeFabricTabUi(contentHost) {
        if (window.KTMenu && typeof window.KTMenu.createInstances === 'function') {
            window.KTMenu.createInstances();
        }
        if (window.KTDrawer && typeof window.KTDrawer.createInstances === 'function') {
            window.KTDrawer.createInstances();
        }
        if (window.KTScroll && typeof window.KTScroll.createInstances === 'function') {
            window.KTScroll.createInstances();
        }
        if (window.KTImageInput && typeof window.KTImageInput.createInstances === 'function') {
            window.KTImageInput.createInstances();
        }

        reinitializeSelect2In(contentHost || document);

        if (window.KTProjectOverview && typeof window.KTProjectOverview.init === 'function') {
            try { window.KTProjectOverview.init(); } catch (e) {}
        }
        if (window.KTProjectUsers && typeof window.KTProjectUsers.init === 'function') {
            try { window.KTProjectUsers.init(); } catch (e) {}
        }
        if (window.KTProjectSettings && typeof window.KTProjectSettings.init === 'function') {
            try { window.KTProjectSettings.init(); } catch (e) {}
        }
    }

    function applyFabricTabPartialResponse(url, responseData, pushHistory) {
        var contentHost = document.getElementById('kt_content');
        if (!contentHost) { throw new Error('Fabric content host is missing.'); }
        contentHost.innerHTML = String((responseData || {}).content_html || '');

        var title = (responseData || {}).title;
        if (title) { document.title = String(title); }

        var responseFabricId = Number((responseData || {}).fabric_id || 0);
        if (Number.isFinite(responseFabricId) && responseFabricId > 0) {
            window.__projectxCurrentFabricId = responseFabricId;
        }

        return executeScriptsFromHtml((responseData || {}).page_javascript_html || '')
            .then(function () {
                reinitializeFabricTabUi(contentHost);
                if (pushHistory) {
                    window.history.pushState({ projectxFabricPartial: true, url: url }, '', url);
                }
            });
    }

    function fetchFabricTabPartial(url, pushHistory) {
        if (fetchFabricTabPartial.__inFlight) {
            return Promise.resolve();
        }
        fetchFabricTabPartial.__inFlight = true;

        return request(url, {
            method: 'GET',
            headers: {
                'X-ProjectX-Partial': 'fabric-tab'
            }
        }).then(function (json) {
            var data = (json && json.data) || null;
            if (!data || typeof data.content_html !== 'string') {
                throw new Error('Invalid partial response.');
            }
            return applyFabricTabPartialResponse(url, data, !!pushHistory);
        }).finally(function () {
            fetchFabricTabPartial.__inFlight = false;
        });
    }

    function initFabricTabNoReloadNavigation() {
        if (!getCurrentFabricId()) { return; }

        document.addEventListener('click', function (event) {
            if (hasModifierClick(event)) { return; }
            var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
            if (!link) { return; }
            if ((link.getAttribute('target') || '').toLowerCase() === '_blank') { return; }
            if (link.hasAttribute('download')) { return; }
            if (String(link.getAttribute('href') || '').toLowerCase().indexOf('javascript:') === 0) { return; }

            var targetRoute = parseFabricManagerRoute(link.href);
            if (!targetRoute) { return; }
            var currentRoute = parseFabricManagerRoute(window.location.href);
            if (!currentRoute) { return; }
            if (targetRoute.url === window.location.href) { return; }
            if (targetRoute.fabricId !== currentRoute.fabricId) { return; }
            if (targetRoute.fabricId !== Number(getCurrentFabricId())) { return; }

            event.preventDefault();
            fetchFabricTabPartial(targetRoute.url, true).catch(function () {
                window.location.href = targetRoute.url;
            });
        }, true);

        window.addEventListener('popstate', function () {
            var route = parseFabricManagerRoute(window.location.href);
            if (!route) {
                window.location.reload();
                return;
            }
            if (route.fabricId !== Number(getCurrentFabricId() || 0)) {
                window.location.reload();
                return;
            }
            fetchFabricTabPartial(route.url, false).catch(function () {
                window.location.reload();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-projectx-chat-container]').forEach(function (container) { bindContainer(container); });
        initDrawerLazyLoad();
        initFabricTabNoReloadNavigation();
    });
})();
