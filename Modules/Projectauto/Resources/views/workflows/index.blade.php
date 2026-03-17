@extends('layouts.app')
@section('title', __('projectauto::lang.workflows'))

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5">
        <div class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectauto::lang.workflows') }}</h1>
                <div class="text-muted fw-semibold fs-7">{{ __('projectauto::lang.workflow_index_helper_text') }}</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#projectauto_simple_create_modal">{{ __('projectauto::lang.create_workflow') }}</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectauto_wizard_modal">{{ __('projectauto::lang.create_with_wizard') }}</button>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="card">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>{{ __('projectauto::lang.name') }}</th>
                                <th>{{ __('projectauto::lang.trigger_type') }}</th>
                                <th>{{ __('projectauto::lang.is_active') }}</th>
                                <th>{{ __('projectauto::lang.updated_at') }}</th>
                                <th class="text-end">{{ __('projectauto::lang.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($workflows as $workflow)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900">{{ $workflow->name }}</div>
                                        @if($workflow->description)
                                            <div class="text-muted fs-7">{{ $workflow->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $workflow->trigger_type ? __('projectauto::lang.' . $workflow->trigger_type) : __('projectauto::lang.not_configured') }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $workflow->is_active ? 'success' : 'secondary' }}">
                                            {{ $workflow->is_active ? __('lang_v1.yes') : __('lang_v1.no') }}
                                        </span>
                                    </td>
                                    <td>{{ optional($workflow->updated_at)->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('projectauto.workflows.build', ['id' => $workflow->id]) }}" class="btn btn-sm btn-light-primary">{{ __('projectauto::lang.open_builder') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('projectauto::lang.no_records_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $workflows->links() }}</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="projectauto_simple_create_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('projectauto.workflows.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">{{ __('projectauto::lang.create_workflow') }}</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-2"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-5">
                            <label class="form-label required">{{ __('projectauto::lang.name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('projectauto::lang.description') }}</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('projectauto::lang.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('projectauto::lang.open_builder') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="projectauto_wizard_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">{{ __('projectauto::lang.create_with_wizard') }}</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="stepper stepper-pills mb-8">
                        <div class="stepper-nav flex-center flex-wrap gap-4">
                            <div class="stepper-item current" data-wizard-step-marker="1"><div class="stepper-label"><h3 class="stepper-title">1. {{ __('projectauto::lang.setup') }}</h3></div></div>
                            <div class="stepper-item" data-wizard-step-marker="2"><div class="stepper-label"><h3 class="stepper-title">2. {{ __('projectauto::lang.actions') }}</h3></div></div>
                            <div class="stepper-item" data-wizard-step-marker="3"><div class="stepper-label"><h3 class="stepper-title">3. {{ __('projectauto::lang.review') }}</h3></div></div>
                        </div>
                    </div>

                    <div id="projectauto-wizard-error" class="alert alert-danger d-none"></div>

                    <div data-wizard-step="1">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectauto::lang.name') }}</label>
                                <input type="text" class="form-control" data-wizard-input="name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('projectauto::lang.description') }}</label>
                                <input type="text" class="form-control" data-wizard-input="description">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectauto::lang.trigger_type') }}</label>
                                <select class="form-select" data-wizard-input="trigger"></select>
                            </div>
                            <div class="col-12">
                                <div class="border border-dashed rounded p-5 bg-light-primary">
                                    <div class="fw-bold text-gray-900 mb-3">{{ __('projectauto::lang.trigger_configuration') }}</div>
                                    <div data-wizard-trigger-config></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('projectauto::lang.condition_field') }}</label>
                                <select class="form-select" data-wizard-input="condition-field"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('projectauto::lang.condition_operator') }}</label>
                                <select class="form-select" data-wizard-input="condition-operator"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('projectauto::lang.condition_value') }}</label>
                                <div data-wizard-condition-value></div>
                            </div>
                        </div>
                    </div>

                    <div data-wizard-step="2" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <div>
                                <h4 class="mb-1">{{ __('projectauto::lang.actions') }}</h4>
                                <div class="text-muted fs-7">{{ __('projectauto::lang.wizard_actions_helper') }}</div>
                            </div>
                            <button type="button" class="btn btn-light-primary" data-wizard-action="add">{{ __('projectauto::lang.add_action') }}</button>
                        </div>
                        <div id="projectauto-wizard-actions" class="d-flex flex-column gap-5"></div>
                    </div>

                    <div data-wizard-step="3" class="d-none">
                        <div class="card bg-light-primary">
                            <div class="card-body">
                                <div id="projectauto-wizard-review"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-wizard-nav="prev">{{ __('projectauto::lang.previous') }}</button>
                    <button type="button" class="btn btn-primary" data-wizard-nav="next">{{ __('projectauto::lang.next') }}</button>
                    <button type="button" class="btn btn-success d-none" data-wizard-nav="submit">{{ __('projectauto::lang.create_and_open_builder') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="projectauto-workflow-index-data">@json([
        'definitions' => $definitions,
        'pageConfig' => $pageConfig,
    ])</script>

    <script>
        (function () {
            const payload = JSON.parse(document.getElementById('projectauto-workflow-index-data').textContent);
            const definitions = payload.definitions || {};
            const triggers = definitions.triggers || [];
            const actions = definitions.actions || [];
            const conditions = definitions.conditions || {};
            const operators = definitions.operators || {};
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const wizardState = {
                step: 1,
                name: '',
                description: '',
                trigger_type: triggers[0] ? triggers[0].type : '',
                trigger_config: {},
                condition: {
                    field: '',
                    operator: '',
                    value: ''
                },
                actions: []
            };

            const triggerSelect = document.querySelector('[data-wizard-input="trigger"]');
            const triggerConfigContainer = document.querySelector('[data-wizard-trigger-config]');
            const conditionFieldSelect = document.querySelector('[data-wizard-input="condition-field"]');
            const conditionOperatorSelect = document.querySelector('[data-wizard-input="condition-operator"]');
            const conditionValueContainer = document.querySelector('[data-wizard-condition-value]');

            triggerSelect.innerHTML = triggers.map(function (trigger) {
                return '<option value="' + escapeHtml(trigger.type) + '">' + escapeHtml(trigger.label) + '</option>';
            }).join('');
            triggerSelect.value = wizardState.trigger_type;

            conditionFieldSelect.innerHTML = '<option value="">{{ __('projectauto::lang.no_condition') }}</option>' + Object.keys(conditions).map(function (key) {
                return '<option value="' + escapeHtml(key) + '">' + escapeHtml(conditions[key].label) + '</option>';
            }).join('');

            bindWizardInputs();
            renderWizard();

            function bindWizardInputs() {
                document.querySelector('[data-wizard-input="name"]').addEventListener('input', function (event) {
                    wizardState.name = event.target.value;
                });

                document.querySelector('[data-wizard-input="description"]').addEventListener('input', function (event) {
                    wizardState.description = event.target.value;
                });

                triggerSelect.addEventListener('change', function () {
                    wizardState.trigger_type = triggerSelect.value;
                    wizardState.trigger_config = {};
                    wizardState.condition.field = '';
                    wizardState.condition.operator = '';
                    wizardState.condition.value = '';
                    renderTriggerConfigFields();
                    renderConditionControls();
                    renderWizard();
                });

                conditionFieldSelect.addEventListener('change', function () {
                    wizardState.condition.field = conditionFieldSelect.value;
                    wizardState.condition.operator = '';
                    wizardState.condition.value = '';
                    renderConditionControls();
                });

                conditionOperatorSelect.addEventListener('change', function () {
                    wizardState.condition.operator = conditionOperatorSelect.value;
                    wizardState.condition.value = ['in', 'not_in'].includes(conditionOperatorSelect.value) ? [] : '';
                    renderConditionValueField();
                });

                document.querySelector('[data-wizard-action="add"]').addEventListener('click', function () {
                    const action = actions[0];
                    if (!action) {
                        return;
                    }

                    wizardState.actions.push({
                        id: Math.random().toString(36).slice(2, 10),
                        type: action.type,
                        config: {}
                    });
                    renderActions();
                });

                document.querySelectorAll('[data-wizard-nav]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const nav = button.getAttribute('data-wizard-nav');
                        if (nav === 'prev') {
                            wizardState.step = Math.max(1, wizardState.step - 1);
                            renderWizard();
                            return;
                        }

                        if (nav === 'next') {
                            if (!validateStep()) {
                                return;
                            }
                            wizardState.step = Math.min(3, wizardState.step + 1);
                            renderWizard();
                            return;
                        }

                        if (!validateStep()) {
                            return;
                        }

                        submitWizard();
                    });
                });

                renderTriggerConfigFields();
                renderConditionControls();
            }

            function renderWizard() {
                document.querySelectorAll('[data-wizard-step]').forEach(function (stepSection) {
                    stepSection.classList.toggle('d-none', stepSection.getAttribute('data-wizard-step') !== String(wizardState.step));
                });

                document.querySelectorAll('[data-wizard-step-marker]').forEach(function (marker) {
                    marker.classList.toggle('current', marker.getAttribute('data-wizard-step-marker') === String(wizardState.step));
                });

                document.querySelector('[data-wizard-nav="prev"]').classList.toggle('d-none', wizardState.step === 1);
                document.querySelector('[data-wizard-nav="next"]').classList.toggle('d-none', wizardState.step === 3);
                document.querySelector('[data-wizard-nav="submit"]').classList.toggle('d-none', wizardState.step !== 3);

                if (wizardState.step === 2) {
                    renderActions();
                }

                if (wizardState.step === 3) {
                    renderReview();
                }
            }

            function renderConditionControls() {
                renderConditionFieldOptions();
                const field = conditions[wizardState.condition.field] || null;
                const allowedOperators = Object.keys(operators).filter(function (operatorKey) {
                    if (!field) {
                        return true;
                    }

                    return (operators[operatorKey].value_types || []).includes(field.value_type);
                });

                conditionOperatorSelect.innerHTML = '<option value="">{{ __('projectauto::lang.select_condition_operator') }}</option>' + allowedOperators.map(function (operatorKey) {
                    return '<option value="' + escapeHtml(operatorKey) + '">' + escapeHtml(operators[operatorKey].label) + '</option>';
                }).join('');
                conditionOperatorSelect.value = wizardState.condition.operator || '';
                renderConditionValueField();
            }

            function renderTriggerConfigFields() {
                triggerConfigContainer.innerHTML = '';
                const definition = triggerDefinition(wizardState.trigger_type);
                const schema = definition ? (definition.config_schema || []) : [];

                if (!schema.length) {
                    triggerConfigContainer.innerHTML = '<div class="text-muted fs-7">{{ __('projectauto::lang.no_additional_trigger_configuration') }}</div>';
                    return;
                }

                schema.forEach(function (field) {
                    triggerConfigContainer.appendChild(renderField(field, wizardState.trigger_config, renderTriggerConfigFields));
                });
            }

            function renderConditionFieldOptions() {
                const entries = supportedConditionEntries(wizardState.trigger_type);
                conditionFieldSelect.innerHTML = '<option value="">{{ __('projectauto::lang.no_condition') }}</option>' + entries.map(function (entry) {
                    return '<option value="' + escapeHtml(entry[0]) + '">' + escapeHtml(entry[1].label) + '</option>';
                }).join('');

                if (!entries.some(function (entry) { return entry[0] === wizardState.condition.field; })) {
                    wizardState.condition.field = '';
                    wizardState.condition.operator = '';
                    wizardState.condition.value = '';
                }

                conditionFieldSelect.value = wizardState.condition.field || '';
            }

            function renderConditionValueField() {
                conditionValueContainer.innerHTML = '';
                const field = conditions[wizardState.condition.field] || null;
                const operator = wizardState.condition.operator || '';

                if (!wizardState.condition.field) {
                    conditionValueContainer.innerHTML = '<div class="text-muted fs-7">{{ __('projectauto::lang.no_condition_selected') }}</div>';
                    return;
                }

                if (field && field.options && !['in', 'not_in'].includes(operator)) {
                    const select = document.createElement('select');
                    select.className = 'form-select';
                    select.innerHTML = '<option value="">{{ __('projectauto::lang.select_value') }}</option>' + field.options.map(function (option) {
                        return '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</option>';
                    }).join('');
                    select.value = wizardState.condition.value || '';
                    select.addEventListener('change', function () {
                        wizardState.condition.value = select.value;
                    });
                    conditionValueContainer.appendChild(select);
                    return;
                }

                const input = document.createElement('input');
                input.className = 'form-control';
                input.type = field && field.value_type === 'number' ? 'number' : 'text';
                input.value = Array.isArray(wizardState.condition.value) ? wizardState.condition.value.join(',') : (wizardState.condition.value || '');
                input.placeholder = ['in', 'not_in'].includes(operator)
                    ? '{{ __('projectauto::lang.condition_value_list_placeholder') }}'
                    : '{{ __('projectauto::lang.condition_value_placeholder') }}';
                input.addEventListener('input', function () {
                    if (['in', 'not_in'].includes(operator)) {
                        wizardState.condition.value = input.value.split(',').map(function (item) {
                            return item.trim();
                        }).filter(Boolean);
                    } else if (field && field.value_type === 'number') {
                        wizardState.condition.value = input.value === '' ? '' : Number(input.value);
                    } else {
                        wizardState.condition.value = input.value;
                    }
                });
                conditionValueContainer.appendChild(input);
            }

            function renderActions() {
                const container = document.getElementById('projectauto-wizard-actions');
                container.innerHTML = '';

                if (!wizardState.actions.length) {
                    container.innerHTML = '<div class="text-muted">{{ __('projectauto::lang.no_actions_added') }}</div>';
                    return;
                }

                wizardState.actions.forEach(function (actionState, index) {
                    const definition = actions.find(function (action) { return action.type === actionState.type; });
                    const card = document.createElement('div');
                    card.className = 'card border border-dashed';
                    const body = document.createElement('div');
                    body.className = 'card-body';

                    const header = document.createElement('div');
                    header.className = 'd-flex justify-content-between align-items-center mb-5';

                    const title = document.createElement('h4');
                    title.className = 'mb-0';
                    title.textContent = definition ? definition.label : actionState.type;
                    header.appendChild(title);

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-sm btn-light-danger';
                    remove.textContent = '{{ __('projectauto::lang.remove') }}';
                    remove.addEventListener('click', function () {
                        wizardState.actions.splice(index, 1);
                        renderActions();
                    });
                    header.appendChild(remove);
                    body.appendChild(header);

                    const typeSelect = document.createElement('select');
                    typeSelect.className = 'form-select mb-5';
                    typeSelect.innerHTML = actions.map(function (action) {
                        return '<option value="' + escapeHtml(action.type) + '">' + escapeHtml(action.label) + '</option>';
                    }).join('');
                    typeSelect.value = actionState.type;
                    typeSelect.addEventListener('change', function () {
                        actionState.type = typeSelect.value;
                        actionState.config = {};
                        renderActions();
                    });
                    body.appendChild(formGroup('{{ __('projectauto::lang.action_type') }}', typeSelect));

                    (definition ? definition.config_schema : []).forEach(function (field) {
                        body.appendChild(renderField(field, actionState.config, renderActions));
                    });

                    card.appendChild(body);
                    container.appendChild(card);
                });
            }

            function renderField(field, model, rerender) {
                const value = model[field.key];

                if (field.type === 'repeater') {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-5';
                    const label = document.createElement('label');
                    label.className = 'form-label fw-semibold';
                    label.textContent = field.label;
                    wrapper.appendChild(label);

                    const rows = Array.isArray(model[field.key]) ? model[field.key] : [];
                    const list = document.createElement('div');
                    list.className = 'd-flex flex-column gap-4';

                    rows.forEach(function (row, rowIndex) {
                        const card = document.createElement('div');
                        card.className = 'border rounded p-4';
                        (field.children || []).forEach(function (child) {
                            card.appendChild(renderField(child, row, rerender));
                        });
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'btn btn-sm btn-light-danger mt-3';
                        remove.textContent = '{{ __('projectauto::lang.remove_row') }}';
                        remove.addEventListener('click', function () {
                            model[field.key].splice(rowIndex, 1);
                            rerender();
                        });
                        card.appendChild(remove);
                        list.appendChild(card);
                    });

                    const add = document.createElement('button');
                    add.type = 'button';
                    add.className = 'btn btn-sm btn-light-primary';
                    add.textContent = '{{ __('projectauto::lang.add_row') }}';
                    add.addEventListener('click', function () {
                        model[field.key] = model[field.key] || [];
                        model[field.key].push(defaultRepeaterRow(field.children || []));
                        rerender();
                    });

                    wrapper.appendChild(list);
                    wrapper.appendChild(add);
                    return wrapper;
                }

                const choices = fieldChoices(field);
                const fieldType = choices.length ? 'select' : field.type;
                const input = fieldType === 'textarea'
                    ? document.createElement('textarea')
                    : (fieldType === 'select' ? document.createElement('select') : document.createElement('input'));

                if (fieldType === 'select') {
                    input.className = 'form-select';
                    input.innerHTML = '<option value="">{{ __('projectauto::lang.select_option') }}</option>' + choices.map(function (option) {
                        return '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</option>';
                    }).join('');
                    input.value = value || '';
                } else if (fieldType === 'textarea') {
                    input.className = 'form-control';
                    input.rows = 3;
                    input.value = value || '';
                } else {
                    input.className = 'form-control';
                    input.type = field.type === 'number' ? 'number' : (field.type === 'integer' ? 'number' : (field.type === 'date' ? 'date' : (field.type === 'boolean' ? 'checkbox' : 'text')));
                    if (field.type === 'boolean') {
                        input.className = 'form-check-input';
                        input.checked = Boolean(value);
                    } else {
                        input.value = value ?? '';
                    }
                }

                const inputEvent = (field.type === 'boolean' || fieldType === 'select') ? 'change' : 'input';
                input.addEventListener(inputEvent, function () {
                    if (field.type === 'boolean') {
                        model[field.key] = input.checked;
                    } else if (field.type === 'integer') {
                        model[field.key] = input.value === '' ? '' : parseInt(input.value, 10);
                    } else if (field.type === 'number') {
                        model[field.key] = input.value === '' ? '' : Number(input.value);
                    } else {
                        model[field.key] = input.value;
                    }
                });

                return formGroup(field.label, input, field.type === 'boolean');
            }

            function renderReview() {
                const review = document.getElementById('projectauto-wizard-review');
                const trigger = triggers.find(function (item) { return item.type === wizardState.trigger_type; });
                const conditionField = conditions[wizardState.condition.field];
                const conditionSummary = wizardState.condition.field
                    ? conditionField.label + ' ' + ((operators[wizardState.condition.operator] || {}).label || wizardState.condition.operator) + ' ' + formatValue(wizardState.condition.value)
                    : '{{ __('projectauto::lang.no_condition') }}';
                const triggerSummary = summarizeConfig(trigger ? (trigger.config_schema || []) : [], wizardState.trigger_config);

                review.innerHTML = '' +
                    '<div class="mb-5"><span class="fw-bold">{{ __('projectauto::lang.name') }}:</span> ' + escapeHtml(wizardState.name) + '</div>' +
                    '<div class="mb-5"><span class="fw-bold">{{ __('projectauto::lang.trigger_type') }}:</span> ' + escapeHtml(trigger ? trigger.label : '') + '</div>' +
                    '<div class="mb-5"><span class="fw-bold">{{ __('projectauto::lang.trigger_configuration') }}:</span> ' + escapeHtml(triggerSummary) + '</div>' +
                    '<div class="mb-5"><span class="fw-bold">{{ __('projectauto::lang.condition') }}:</span> ' + escapeHtml(conditionSummary) + '</div>' +
                    '<div><span class="fw-bold">{{ __('projectauto::lang.actions') }}:</span><ul class="mt-3">' +
                    wizardState.actions.map(function (actionState) {
                        const definition = actions.find(function (action) { return action.type === actionState.type; });
                        return '<li>' + escapeHtml(definition ? definition.label : actionState.type) + ' - ' + escapeHtml(summarizeConfig(definition ? (definition.config_schema || []) : [], actionState.config || {})) + '</li>';
                    }).join('') +
                    '</ul></div>';
            }

            function validateStep() {
                clearError();

                if (wizardState.step === 1) {
                    if (!wizardState.name.trim()) {
                        showError('{{ __('projectauto::lang.workflow_name_required') }}');
                        return false;
                    }

                    if (wizardState.condition.field && (!wizardState.condition.operator || wizardState.condition.value === '' || wizardState.condition.value === null || (Array.isArray(wizardState.condition.value) && !wizardState.condition.value.length))) {
                        showError('{{ __('projectauto::lang.complete_condition_before_continue') }}');
                        return false;
                    }
                }

                if (wizardState.step === 2 && !wizardState.actions.length) {
                    showError('{{ __('projectauto::lang.at_least_one_action_required') }}');
                    return false;
                }

                return true;
            }

            function submitWizard() {
                clearError();

                fetch(payload.pageConfig.api.fromWizard, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: wizardState.name,
                        description: wizardState.description,
                        trigger_type: wizardState.trigger_type,
                        trigger_config: wizardState.trigger_config,
                        condition: wizardState.condition.field ? wizardState.condition : null,
                        actions: wizardState.actions.map(function (actionState) {
                            return {
                                type: actionState.type,
                                config: actionState.config
                            };
                        })
                    })
                }).then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                }).then(function (result) {
                    if (!result.ok) {
                        throw result.data;
                    }

                    window.location.href = result.data.redirect_url;
                }).catch(function (error) {
                    const messages = flattenErrors(error.errors || {});
                    showError(messages.length ? messages.join('\n') : '{{ __('projectauto::lang.workflow_action_failed') }}');
                });
            }

            function formGroup(label, input, inlineBoolean) {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-5';

                if (inlineBoolean) {
                    wrapper.className = 'form-check form-switch form-check-custom form-check-solid mb-5';
                    input.id = input.id || ('chk_' + Math.random().toString(36).slice(2));
                    const checkboxLabel = document.createElement('label');
                    checkboxLabel.className = 'form-check-label ms-3';
                    checkboxLabel.setAttribute('for', input.id);
                    checkboxLabel.textContent = label;
                    wrapper.appendChild(input);
                    wrapper.appendChild(checkboxLabel);
                    return wrapper;
                }

                const labelEl = document.createElement('label');
                labelEl.className = 'form-label fw-semibold';
                labelEl.textContent = label;
                wrapper.appendChild(labelEl);
                wrapper.appendChild(input);
                return wrapper;
            }

            function defaultRepeaterRow(children) {
                const row = {};
                children.forEach(function (child) {
                    row[child.key] = child.type === 'boolean' ? false : '';
                });
                return row;
            }

            function showError(message) {
                const error = document.getElementById('projectauto-wizard-error');
                error.textContent = message;
                error.classList.remove('d-none');
            }

            function clearError() {
                const error = document.getElementById('projectauto-wizard-error');
                error.textContent = '';
                error.classList.add('d-none');
            }

            function flattenErrors(errors) {
                return Object.keys(errors).reduce(function (carry, key) {
                    return carry.concat(errors[key]);
                }, []);
            }

            function formatValue(value) {
                return Array.isArray(value) ? value.join(', ') : value;
            }

            function summarizeConfig(schema, config) {
                if (!schema || !schema.length) {
                    return '{{ __('projectauto::lang.no_additional_configuration') }}';
                }

                const parts = [];
                schema.forEach(function (field) {
                    const value = config[field.key];
                    if (value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length)) {
                        return;
                    }

                    if (field.type === 'repeater') {
                        parts.push(field.label + ': ' + value.length);
                        return;
                    }

                    if (field.type === 'boolean') {
                        parts.push(field.label + ': ' + (value ? '{{ __('lang_v1.yes') }}' : '{{ __('lang_v1.no') }}'));
                        return;
                    }

                    parts.push(field.label + ': ' + formatFieldValue(field, value));
                });

                return parts.length ? parts.join(' • ') : '{{ __('projectauto::lang.no_additional_configuration') }}';
            }

            function formatFieldValue(field, value) {
                const choices = fieldChoices(field);
                if (choices.length) {
                    const match = choices.find(function (option) {
                        return option.value === value;
                    });

                    if (match) {
                        return match.label;
                    }
                }

                return formatValue(value);
            }

            function fieldChoices(field) {
                if (Array.isArray(field.options)) {
                    return field.options;
                }

                if (Array.isArray(field.enum)) {
                    return field.enum.map(function (value) {
                        return { value: value, label: value };
                    });
                }

                return [];
            }

            function supportedConditionEntries(triggerType) {
                return Object.entries(conditions).filter(function (entry) {
                    const supportedTriggers = entry[1].supported_triggers || [];
                    return !supportedTriggers.length || supportedTriggers.includes(triggerType);
                });
            }

            function triggerDefinition(type) {
                return triggers.find(function (trigger) {
                    return trigger.type === type;
                }) || null;
            }

            function escapeHtml(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        })();
    </script>
@endsection
