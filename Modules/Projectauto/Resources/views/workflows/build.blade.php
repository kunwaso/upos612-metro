@extends('layouts.app')
@section('title', __('projectauto::lang.workflow_builder'))

@section('css')
    @foreach(($builderAssets['css'] ?? []) as $cssAsset)
        <link rel="stylesheet" href="{{ $cssAsset }}">
    @endforeach
@endsection

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5">
        <div class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ $workflow->name }}</h1>
                <div class="text-muted fw-semibold fs-7">{{ __('projectauto::lang.workflow_builder_helper_text') }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('projectauto.workflows.index') }}" class="btn btn-light">{{ __('projectauto::lang.back_to_workflows') }}</a>
                <button type="button" class="btn btn-light-primary" data-builder-action="validate">{{ __('projectauto::lang.validate_draft') }}</button>
                <button type="button" class="btn btn-primary" data-builder-action="save">{{ __('projectauto::lang.save_draft') }}</button>
                <button type="button" class="btn btn-success" data-builder-action="publish">{{ __('projectauto::lang.publish_workflow') }}</button>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="alert alert-info d-flex align-items-center p-5 mb-7">
            <i class="ki-duotone ki-information-5 fs-2hx text-info me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="d-flex flex-column">
                <h4 class="mb-1 text-gray-900">{{ __('projectauto::lang.predefined_only_builder') }}</h4>
                <span>{{ __('projectauto::lang.predefined_only_builder_description') }}</span>
            </div>
        </div>

        <div id="projectauto-builder-app" class="row g-7">
            <div class="col-12 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7"><h3 class="fw-bold">{{ __('projectauto::lang.builder_palette') }}</h3></div>
                    <div class="card-body pt-4">
                        <div class="mb-6">
                            <label class="form-label fw-semibold">{{ __('projectauto::lang.trigger') }}</label>
                            <select class="form-select" data-builder-input="trigger"></select>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-6">
                            <input class="form-check-input" type="checkbox" id="builder_use_condition" data-builder-input="use-condition">
                            <label class="form-check-label" for="builder_use_condition">{{ __('projectauto::lang.use_condition_branch') }}</label>
                        </div>
                        <div class="separator separator-dashed my-7"></div>
                        <div class="d-flex flex-column gap-3">
                            <button type="button" class="btn btn-light-primary" data-builder-add-action="direct">{{ __('projectauto::lang.add_action_direct') }}</button>
                            <button type="button" class="btn btn-light-primary d-none" data-builder-add-action="true">{{ __('projectauto::lang.add_action_true_branch') }}</button>
                            <button type="button" class="btn btn-light-primary d-none" data-builder-add-action="false">{{ __('projectauto::lang.add_action_false_branch') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7"><h3 class="fw-bold">{{ __('projectauto::lang.workflow_canvas') }}</h3></div>
                    <div class="card-body pt-5">
                        <div class="border border-dashed rounded p-5 mb-5 bg-light-primary">
                            <div class="text-uppercase text-muted fs-8 fw-bold mb-2">{{ __('projectauto::lang.trigger') }}</div>
                            <div id="builder-trigger-card"></div>
                        </div>
                        <div id="builder-condition-zone" class="border border-dashed rounded p-5 mb-5 bg-light-warning d-none">
                            <div class="text-uppercase text-muted fs-8 fw-bold mb-2">{{ __('projectauto::lang.condition') }}</div>
                            <div id="builder-condition-card"></div>
                        </div>
                        <div id="builder-direct-zone" class="border border-dashed rounded p-5 bg-light d-block">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-uppercase text-muted fs-8 fw-bold">{{ __('projectauto::lang.direct_actions') }}</div>
                                <span class="badge badge-light-primary" id="builder-direct-count">0</span>
                            </div>
                            <div class="d-flex flex-column gap-3" id="builder-direct-actions"></div>
                        </div>
                        <div id="builder-conditional-zones" class="row g-5 mt-1 d-none">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 bg-light-success h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-uppercase text-muted fs-8 fw-bold">{{ __('projectauto::lang.true_branch_actions') }}</div>
                                        <span class="badge badge-light-success" id="builder-true-count">0</span>
                                    </div>
                                    <div class="d-flex flex-column gap-3" id="builder-true-actions"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 bg-light-danger h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-uppercase text-muted fs-8 fw-bold">{{ __('projectauto::lang.false_branch_actions') }}</div>
                                        <span class="badge badge-light-danger" id="builder-false-count">0</span>
                                    </div>
                                    <div class="d-flex flex-column gap-3" id="builder-false-actions"></div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-secondary mt-5 mb-0" id="builder-canvas-hint">{{ __('projectauto::lang.workflow_builder_hint') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7"><h3 class="fw-bold">{{ __('projectauto::lang.node_inspector') }}</h3></div>
                    <div class="card-body pt-4">
                        <div id="builder-inspector-empty" class="text-muted">{{ __('projectauto::lang.select_node_to_edit') }}</div>
                        <div id="builder-inspector" class="d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="projectauto-builder-data">@json([
        'workflow' => $workflow,
        'definitions' => $definitions,
        'pageConfig' => $pageConfig,
    ])</script>

    <style>
        .projectauto-node-card.is-selected {
            box-shadow: 0 0 0 2px rgba(62, 151, 255, 0.4);
            border-color: #3e97ff !important;
        }
    </style>

    <script>
        (function () {
            const payload = JSON.parse(document.getElementById('projectauto-builder-data').textContent);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const definitions = payload.definitions;
            const triggers = definitions.triggers || [];
            const actions = definitions.actions || [];
            const conditionFields = definitions.conditions || {};
            const operatorMap = definitions.operators || {};
            const workflow = payload.workflow || {};
            const existingGraph = workflow.draft_graph || workflow.published_graph || { version: 1, nodes: [], edges: [] };
            const state = createState(existingGraph);

            const triggerSelect = document.querySelector('[data-builder-input="trigger"]');
            const useConditionToggle = document.querySelector('[data-builder-input="use-condition"]');
            const addActionButtons = document.querySelectorAll('[data-builder-add-action]');

            triggerSelect.innerHTML = triggers.map(function (trigger) {
                return '<option value="' + escapeHtml(trigger.type) + '">' + escapeHtml(trigger.label) + '</option>';
            }).join('');

            if (state.triggerNode.type) {
                triggerSelect.value = state.triggerNode.type;
            } else if (triggers[0]) {
                state.triggerNode = makeNode('trigger', triggers[0].type, {});
                triggerSelect.value = triggers[0].type;
            }

            useConditionToggle.checked = !!state.conditionNode;

            triggerSelect.addEventListener('change', function () {
                state.triggerNode.type = triggerSelect.value;
                state.triggerNode.config = {};
                resetConditionIfUnsupported();
                render();
            });

            useConditionToggle.addEventListener('change', function () {
                if (useConditionToggle.checked) {
                    state.conditionNode = state.conditionNode || makeConditionNode();
                    state.trueActions = state.trueActions.concat(state.directActions);
                    state.directActions = [];
                } else {
                    state.directActions = state.directActions.concat(state.trueActions, state.falseActions);
                    state.trueActions = [];
                    state.falseActions = [];
                    state.conditionNode = null;
                }

                state.selected = null;
                render();
            });

            addActionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const branch = button.getAttribute('data-builder-add-action');
                    const defaultAction = actions[0];
                    if (!defaultAction) {
                        return;
                    }

                    const node = makeNode('action', defaultAction.type, {});
                    getBranchActions(branch).push(node);
                    state.selected = { branch: branch, id: node.id, family: 'action' };
                    render();
                });
            });

            document.querySelectorAll('[data-builder-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    submitGraph(button.getAttribute('data-builder-action'));
                });
            });

            render();

            function createState(graph) {
                const nodeMap = {};
                (graph.nodes || []).forEach(function (node) {
                    nodeMap[node.id] = node;
                });

                const triggerNode = (graph.nodes || []).find(function (node) {
                    return node.family === 'trigger';
                }) || makeNode('trigger', '', {});
                const conditionNode = (graph.nodes || []).find(function (node) {
                    return node.type === 'logic.if_else';
                }) || null;
                const directActions = [];
                const trueActions = [];
                const falseActions = [];

                (graph.edges || []).forEach(function (edge) {
                    const targetNode = nodeMap[edge.target];
                    if (!targetNode || targetNode.family !== 'action') {
                        return;
                    }

                    if (conditionNode && edge.source === conditionNode.id) {
                        if ((edge.source_port || 'true') === 'false') {
                            falseActions.push(targetNode);
                        } else {
                            trueActions.push(targetNode);
                        }
                    } else {
                        directActions.push(targetNode);
                    }
                });

                return {
                    triggerNode: triggerNode,
                    conditionNode: conditionNode,
                    directActions: directActions,
                    trueActions: trueActions,
                    falseActions: falseActions,
                    selected: null
                };
            }

            function render() {
                const hasCondition = !!state.conditionNode;
                document.getElementById('builder-condition-zone').classList.toggle('d-none', !hasCondition);
                document.getElementById('builder-conditional-zones').classList.toggle('d-none', !hasCondition);
                document.getElementById('builder-direct-zone').classList.toggle('d-none', hasCondition);
                document.querySelector('[data-builder-add-action="direct"]').classList.toggle('d-none', hasCondition);
                document.querySelector('[data-builder-add-action="true"]').classList.toggle('d-none', !hasCondition);
                document.querySelector('[data-builder-add-action="false"]').classList.toggle('d-none', !hasCondition);

                renderNodeCard(document.getElementById('builder-trigger-card'), state.triggerNode, 'direct', true);
                renderConditionCard();
                renderActionList('builder-direct-actions', state.directActions, 'direct');
                renderActionList('builder-true-actions', state.trueActions, 'true');
                renderActionList('builder-false-actions', state.falseActions, 'false');
                document.getElementById('builder-direct-count').textContent = state.directActions.length;
                document.getElementById('builder-true-count').textContent = state.trueActions.length;
                document.getElementById('builder-false-count').textContent = state.falseActions.length;
                renderInspector();
            }

            function renderConditionCard() {
                const container = document.getElementById('builder-condition-card');
                if (!state.conditionNode) {
                    container.innerHTML = '';
                    return;
                }

                const spec = (state.conditionNode.config && state.conditionNode.config.condition_spec) || {};
                const field = conditionFields[spec.field] || null;
                const summary = field
                    ? field.label + ' ' + ((operatorMap[spec.operator] || {}).label || spec.operator || '') + ' ' + formatValue(spec.value)
                    : '{{ __('projectauto::lang.condition_not_configured') }}';

                container.innerHTML = nodeMarkup('logic.if_else', '{{ __('projectauto::lang.if_else_condition') }}', summary, selectedClass(state.conditionNode.id));
                container.firstElementChild.addEventListener('click', function () {
                    state.selected = { family: 'logic', id: state.conditionNode.id, branch: 'condition' };
                    renderInspector();
                });
            }

            function renderNodeCard(container, node, branch, isTrigger) {
                const definition = isTrigger ? triggerDefinition(node.type) : actionDefinition(node.type);
                const title = definition ? definition.label : (node.type || '{{ __('projectauto::lang.unconfigured_node') }}');
                const summary = isTrigger
                    ? summarizeConfig(definition ? definition.config_schema : [], node.config || {}, definition ? definition.description : '{{ __('projectauto::lang.select_trigger') }}')
                    : summarizeConfig(definition ? definition.config_schema : [], node.config || {});

                container.innerHTML = nodeMarkup(node.type, title, summary, selectedClass(node.id));
                container.firstElementChild.addEventListener('click', function () {
                    state.selected = { family: isTrigger ? 'trigger' : 'action', id: node.id, branch: branch };
                    renderInspector();
                });
            }

            function renderActionList(elementId, nodes, branch) {
                const container = document.getElementById(elementId);
                if (!container) {
                    return;
                }

                container.innerHTML = '';

                if (!nodes.length) {
                    container.innerHTML = '<div class="text-muted">{{ __('projectauto::lang.no_actions_added') }}</div>';
                    return;
                }

                nodes.forEach(function (node) {
                    const definition = actionDefinition(node.type);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = nodeMarkup(
                        node.type,
                        definition ? definition.label : node.type,
                        summarizeConfig(definition ? definition.config_schema : [], node.config || {}),
                        selectedClass(node.id)
                    );

                    const card = wrapper.firstElementChild;
                    card.addEventListener('click', function () {
                        state.selected = { family: 'action', id: node.id, branch: branch };
                        renderInspector();
                    });

                    const actionsRow = document.createElement('div');
                    actionsRow.className = 'd-flex gap-2 mt-3';

                    if (state.conditionNode && branch !== 'direct') {
                        ['true', 'false'].forEach(function (targetBranch) {
                            if (targetBranch === branch) {
                                return;
                            }

                            const moveButton = document.createElement('button');
                            moveButton.type = 'button';
                            moveButton.className = 'btn btn-sm btn-light';
                            moveButton.textContent = targetBranch === 'true'
                                ? '{{ __('projectauto::lang.move_to_true') }}'
                                : '{{ __('projectauto::lang.move_to_false') }}';
                            moveButton.addEventListener('click', function (event) {
                                event.stopPropagation();
                                moveAction(node.id, branch, targetBranch);
                            });
                            actionsRow.appendChild(moveButton);
                        });
                    }

                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'btn btn-sm btn-light-danger';
                    deleteButton.textContent = '{{ __('projectauto::lang.remove') }}';
                    deleteButton.addEventListener('click', function (event) {
                        event.stopPropagation();
                        removeAction(node.id, branch);
                    });
                    actionsRow.appendChild(deleteButton);

                    card.appendChild(actionsRow);
                    container.appendChild(card);
                });
            }

            function renderInspector() {
                const empty = document.getElementById('builder-inspector-empty');
                const inspector = document.getElementById('builder-inspector');
                inspector.innerHTML = '';

                if (!state.selected) {
                    empty.classList.remove('d-none');
                    inspector.classList.add('d-none');
                    return;
                }

                empty.classList.add('d-none');
                inspector.classList.remove('d-none');

                const selection = selectedNode();
                if (!selection) {
                    return;
                }

                if (selection.family === 'trigger') {
                    const select = document.createElement('select');
                    select.className = 'form-select mb-5';
                    select.innerHTML = triggers.map(function (trigger) {
                        return '<option value="' + escapeHtml(trigger.type) + '">' + escapeHtml(trigger.label) + '</option>';
                    }).join('');
                    select.value = state.triggerNode.type;
                    select.addEventListener('change', function () {
                        state.triggerNode.type = select.value;
                        state.triggerNode.config = {};
                        render();
                    });
                    inspector.appendChild(formGroup('{{ __('projectauto::lang.trigger_type') }}', select));

                    const schema = triggerDefinition(state.triggerNode.type).config_schema || [];
                    schema.forEach(function (field) {
                        inspector.appendChild(renderField(field, state.triggerNode.config || {}, renderInspector));
                    });
                    return;
                }

                if (selection.family === 'logic') {
                    inspector.appendChild(renderConditionInspector());
                    return;
                }

                const definition = actionDefinition(selection.node.type);
                if (!definition) {
                    return;
                }

                const typeSelect = document.createElement('select');
                typeSelect.className = 'form-select mb-5';
                typeSelect.innerHTML = actions.map(function (action) {
                    return '<option value="' + escapeHtml(action.type) + '">' + escapeHtml(action.label) + '</option>';
                }).join('');
                typeSelect.value = selection.node.type;
                typeSelect.addEventListener('change', function () {
                    selection.node.type = typeSelect.value;
                    selection.node.config = {};
                    render();
                });
                inspector.appendChild(formGroup('{{ __('projectauto::lang.action_type') }}', typeSelect));

                const schema = actionDefinition(selection.node.type).config_schema || [];
                schema.forEach(function (field) {
                    inspector.appendChild(renderField(field, selection.node.config || {}, renderInspector));
                });
            }

            function renderConditionInspector() {
                const wrapper = document.createElement('div');
                const condition = state.conditionNode.config.condition_spec || {};
                const fieldSelect = document.createElement('select');
                fieldSelect.className = 'form-select';
                fieldSelect.innerHTML = '<option value="">{{ __('projectauto::lang.select_condition_field') }}</option>' + supportedConditionEntries(state.triggerNode.type).map(function (entry) {
                    return '<option value="' + escapeHtml(entry[0]) + '">' + escapeHtml(entry[1].label) + '</option>';
                }).join('');
                fieldSelect.value = condition.field || '';
                fieldSelect.addEventListener('change', function () {
                    state.conditionNode.config.condition_spec.field = fieldSelect.value;
                    state.conditionNode.config.condition_spec.operator = '';
                    state.conditionNode.config.condition_spec.value = '';
                    render();
                });
                wrapper.appendChild(formGroup('{{ __('projectauto::lang.condition_field') }}', fieldSelect));

                const operatorSelect = document.createElement('select');
                operatorSelect.className = 'form-select';
                const allowedOperators = Object.keys(operatorMap).filter(function (operatorKey) {
                    const field = conditionFields[condition.field];
                    if (!field) {
                        return true;
                    }

                    return (operatorMap[operatorKey].value_types || []).includes(field.value_type);
                });
                operatorSelect.innerHTML = '<option value="">{{ __('projectauto::lang.select_condition_operator') }}</option>' + allowedOperators.map(function (operatorKey) {
                    return '<option value="' + escapeHtml(operatorKey) + '">' + escapeHtml(operatorMap[operatorKey].label) + '</option>';
                }).join('');
                operatorSelect.value = condition.operator || '';
                operatorSelect.addEventListener('change', function () {
                    state.conditionNode.config.condition_spec.operator = operatorSelect.value;
                    state.conditionNode.config.condition_spec.value = ['in', 'not_in'].includes(operatorSelect.value) ? [] : '';
                    render();
                });
                wrapper.appendChild(formGroup('{{ __('projectauto::lang.condition_operator') }}', operatorSelect));
                wrapper.appendChild(formGroup('{{ __('projectauto::lang.condition_value') }}', renderConditionValueField(condition)));

                return wrapper;
            }

            function renderConditionValueField(condition) {
                const fieldDefinition = conditionFields[condition.field] || null;
                const operator = condition.operator || '';

                if (fieldDefinition && fieldDefinition.options && !['in', 'not_in'].includes(operator)) {
                    const select = document.createElement('select');
                    select.className = 'form-select';
                    select.innerHTML = '<option value="">{{ __('projectauto::lang.select_value') }}</option>' + fieldDefinition.options.map(function (option) {
                        return '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</option>';
                    }).join('');
                    select.value = condition.value || '';
                    select.addEventListener('change', function () {
                        state.conditionNode.config.condition_spec.value = select.value;
                    });
                    return select;
                }

                const input = document.createElement('input');
                input.className = 'form-control';
                input.type = fieldDefinition && fieldDefinition.value_type === 'number' ? 'number' : 'text';
                input.value = Array.isArray(condition.value) ? condition.value.join(',') : (condition.value || '');
                input.placeholder = ['in', 'not_in'].includes(operator)
                    ? '{{ __('projectauto::lang.condition_value_list_placeholder') }}'
                    : '{{ __('projectauto::lang.condition_value_placeholder') }}';
                input.addEventListener('input', function () {
                    if (['in', 'not_in'].includes(operator)) {
                        state.conditionNode.config.condition_spec.value = input.value.split(',').map(function (item) {
                            return item.trim();
                        }).filter(Boolean);
                    } else if (fieldDefinition && fieldDefinition.value_type === 'number') {
                        state.conditionNode.config.condition_spec.value = input.value === '' ? '' : Number(input.value);
                    } else {
                        state.conditionNode.config.condition_spec.value = input.value;
                    }
                });
                return input;
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

                    rows.forEach(function (row, index) {
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
                            model[field.key].splice(index, 1);
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

                input.addEventListener(field.type === 'boolean' ? 'change' : 'input', function () {
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

            function summarizeConfig(schema, config, emptyLabel) {
                if (!schema || !schema.length) {
                    return emptyLabel || '{{ __('projectauto::lang.no_additional_configuration') }}';
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

                return parts.length ? parts.join(' • ') : (emptyLabel || '{{ __('projectauto::lang.no_additional_configuration') }}');
            }

            function formatValue(value) {
                if (Array.isArray(value)) {
                    return value.join(', ');
                }

                if (typeof value === 'boolean') {
                    return value ? '{{ __('lang_v1.yes') }}' : '{{ __('lang_v1.no') }}';
                }

                return value;
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
                return Object.entries(conditionFields).filter(function (entry) {
                    const supportedTriggers = entry[1].supported_triggers || [];
                    return !supportedTriggers.length || supportedTriggers.includes(triggerType);
                });
            }

            function resetConditionIfUnsupported() {
                if (!state.conditionNode) {
                    return;
                }

                const field = state.conditionNode.config.condition_spec.field || '';
                const supportedFields = supportedConditionEntries(state.triggerNode.type).map(function (entry) {
                    return entry[0];
                });

                if (field && supportedFields.includes(field)) {
                    return;
                }

                state.conditionNode.config.condition_spec = {
                    field: '',
                    operator: '',
                    value: ''
                };
            }

            function selectedNode() {
                if (!state.selected) {
                    return null;
                }

                if (state.selected.family === 'trigger') {
                    return { family: 'trigger', node: state.triggerNode };
                }

                if (state.selected.family === 'logic') {
                    return { family: 'logic', node: state.conditionNode };
                }

                const branchNodes = getBranchActions(state.selected.branch);
                return {
                    family: 'action',
                    node: branchNodes.find(function (node) { return node.id === state.selected.id; })
                };
            }

            function selectedClass(id) {
                return state.selected && state.selected.id === id ? 'is-selected' : '';
            }

            function getBranchActions(branch) {
                if (branch === 'true') {
                    return state.trueActions;
                }

                if (branch === 'false') {
                    return state.falseActions;
                }

                return state.directActions;
            }

            function moveAction(id, fromBranch, toBranch) {
                const fromList = getBranchActions(fromBranch);
                const nodeIndex = fromList.findIndex(function (node) { return node.id === id; });
                if (nodeIndex === -1) {
                    return;
                }

                const node = fromList.splice(nodeIndex, 1)[0];
                getBranchActions(toBranch).push(node);
                state.selected = { family: 'action', id: id, branch: toBranch };
                render();
            }

            function removeAction(id, branch) {
                const branchNodes = getBranchActions(branch);
                const index = branchNodes.findIndex(function (node) { return node.id === id; });
                if (index !== -1) {
                    branchNodes.splice(index, 1);
                }
                if (state.selected && state.selected.id === id) {
                    state.selected = null;
                }
                render();
            }

            function defaultRepeaterRow(children) {
                const row = {};
                children.forEach(function (child) {
                    row[child.key] = child.type === 'boolean' ? false : '';
                });
                return row;
            }

            function makeNode(family, type, config) {
                return {
                    id: family + '_' + Math.random().toString(36).slice(2, 10),
                    family: family,
                    type: type,
                    label: type,
                    config: config || {}
                };
            }

            function makeConditionNode() {
                return {
                    id: 'condition_' + Math.random().toString(36).slice(2, 10),
                    family: 'logic',
                    type: 'logic.if_else',
                    label: 'If / Else',
                    config: {
                        condition_spec: {
                            field: '',
                            operator: '',
                            value: ''
                        }
                    }
                };
            }

            function actionDefinition(type) {
                return actions.find(function (action) { return action.type === type; }) || null;
            }

            function triggerDefinition(type) {
                return triggers.find(function (trigger) { return trigger.type === type; }) || null;
            }

            function nodeMarkup(type, title, summary, selectedClassName) {
                return '' +
                    '<div class="projectauto-node-card border rounded p-4 cursor-pointer ' + selectedClassName + '">' +
                        '<div class="d-flex justify-content-between align-items-start gap-3">' +
                            '<div>' +
                                '<div class="fw-bold text-gray-900">' + escapeHtml(title) + '</div>' +
                                '<div class="text-muted fs-7 mt-2">' + escapeHtml(summary) + '</div>' +
                            '</div>' +
                            '<span class="badge badge-light">' + escapeHtml(type) + '</span>' +
                        '</div>' +
                    '</div>';
            }

            function serializeGraph() {
                const nodes = [state.triggerNode];
                const edges = [];

                if (state.conditionNode) {
                    nodes.push(state.conditionNode);
                    state.conditionNode.config.condition = buildConditionExpression(state.conditionNode.config.condition_spec || {});
                    edges.push({
                        id: 'edge_' + state.triggerNode.id + '_' + state.conditionNode.id,
                        source: state.triggerNode.id,
                        source_port: 'next',
                        target: state.conditionNode.id,
                        target_port: 'input'
                    });

                    state.trueActions.forEach(function (node) {
                        nodes.push(node);
                        edges.push({
                            id: 'edge_' + state.conditionNode.id + '_' + node.id + '_true',
                            source: state.conditionNode.id,
                            source_port: 'true',
                            target: node.id,
                            target_port: 'input'
                        });
                    });

                    state.falseActions.forEach(function (node) {
                        nodes.push(node);
                        edges.push({
                            id: 'edge_' + state.conditionNode.id + '_' + node.id + '_false',
                            source: state.conditionNode.id,
                            source_port: 'false',
                            target: node.id,
                            target_port: 'input'
                        });
                    });
                } else {
                    state.directActions.forEach(function (node) {
                        nodes.push(node);
                        edges.push({
                            id: 'edge_' + state.triggerNode.id + '_' + node.id,
                            source: state.triggerNode.id,
                            source_port: 'next',
                            target: node.id,
                            target_port: 'input'
                        });
                    });
                }

                return {
                    version: 1,
                    nodes: nodes,
                    edges: edges
                };
            }

            function buildConditionExpression(spec) {
                if (!spec || !spec.field || !spec.operator) {
                    return '';
                }

                const operator = {
                    equals: '==',
                    not_equals: '!=',
                    greater_than: '>',
                    less_than: '<',
                    contains: 'contains',
                    in: 'in',
                    not_in: 'not in'
                }[spec.operator] || spec.operator;

                let value = spec.value;
                if (Array.isArray(value)) {
                    value = JSON.stringify(value);
                } else if (typeof value === 'string') {
                    value = '"' + value.replace(/"/g, '\\"') + '"';
                }

                return spec.field + ' ' + operator + ' ' + value;
            }

            function submitGraph(action) {
                const endpoints = payload.pageConfig.api || {};
                const graph = serializeGraph();
                let url = endpoints.saveDraft;
                let method = 'PUT';
                let body = { graph: graph };

                if (action === 'validate') {
                    url = endpoints.validateDraft;
                    method = 'POST';
                    body = { graph: graph, strict: true };
                }

                if (action === 'publish') {
                    url = endpoints.publish;
                    method = 'POST';
                    body = { graph: graph };
                }

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body)
                }).then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                }).then(function (result) {
                    if (!result.ok) {
                        throw result.data;
                    }

                    showStatus('success', result.data.message || '{{ __('projectauto::lang.saved') }}');
                }).catch(function (error) {
                    const messages = flattenErrors(error.errors || {});
                    showStatus('danger', messages.length ? messages.join('\n') : '{{ __('projectauto::lang.workflow_action_failed') }}');
                });
            }

            function flattenErrors(errors) {
                return Object.keys(errors).reduce(function (carry, key) {
                    return carry.concat(errors[key]);
                }, []);
            }

            function showStatus(type, message) {
                const existing = document.getElementById('projectauto-builder-status');
                if (existing) {
                    existing.remove();
                }

                const alert = document.createElement('div');
                alert.id = 'projectauto-builder-status';
                alert.className = 'alert alert-' + type + ' mt-6';
                alert.textContent = message;
                document.getElementById('projectauto-builder-app').prepend(alert);
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

    @if(!empty($builderAssets['js']))
        <script type="module" src="{{ $builderAssets['js'] }}"></script>
    @endif
@endsection
