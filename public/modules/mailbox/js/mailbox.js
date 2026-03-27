document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mailbox-auto-submit').forEach(function (element) {
        element.addEventListener('change', function () {
            if (element.form) {
                element.form.submit();
            }
        });
    });

    document.querySelectorAll('[data-mailbox-test-form="true"]').forEach(function (form) {
        var button = form.querySelector('[data-mailbox-test-button]');
        var feedback = form.querySelector('[data-mailbox-test-feedback]');
        var url = form.getAttribute('data-mailbox-test-url');

        if (!button || !url) {
            return;
        }

        button.addEventListener('click', function () {
            var formData = new FormData(form);
            if (!formData.has('sync_enabled')) {
                formData.append('sync_enabled', '0');
            }

            var originalText = button.textContent;
            button.disabled = true;
            button.textContent = button.getAttribute('data-loading-text') || originalText;

            if (feedback) {
                feedback.textContent = '';
                feedback.classList.remove('text-success', 'text-danger');
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {
                            success: false,
                            message: 'Unable to validate mailbox settings right now.'
                        };
                    }).then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!feedback) {
                        return;
                    }

                    feedback.textContent = result.payload.message || '';
                    feedback.classList.add(result.ok && result.payload.success ? 'text-success' : 'text-danger');
                })
                .catch(function () {
                    if (!feedback) {
                        return;
                    }

                    feedback.textContent = 'Unable to validate mailbox settings right now.';
                    feedback.classList.add('text-danger');
                })
                .finally(function () {
                    button.disabled = false;
                    button.textContent = originalText;
                });
        });
    });
});
