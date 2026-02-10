<?php
// Custom Rule Editor
$rule = $data['rule'] ?? null;
$conditions = $data['conditions'] ?? [];
$actions = $data['actions'] ?? [];
$isNew = $data['is_new'] ?? true;

$availableFields = $data['available_fields'] ?? [];
$comparisons = $data['comparisons'] ?? [];
$actionTypes = $data['action_types'] ?? [];

// Status-Optionen
$statusOptions = ['Offen', 'Interessent', 'Kundin', 'Partnerin', 'Stillgelegt', 'Abgeschlossen'];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
    <style>
        .condition-card,
        .action-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }

        .condition-card:hover,
        .action-card:hover {
            border-color: var(--color-secondary);
        }

        .condition-group {
            border-left: 3px solid var(--color-secondary);
            padding-left: 16px;
            margin-bottom: 16px;
        }

        .operator-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            cursor: pointer;
        }

        .operator-badge.and {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .operator-badge.or {
            background: #fef3c7;
            color: #b45309;
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="index.php?page=automation" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-gray-400"></i>
                </a>
                <h1 class="text-2xl font-bold font-display text-gray-800">
                    <?= $isNew ? 'Neue Automatisierung' : 'Automatisierung bearbeiten' ?>
                </h1>
            </div>
            <p class="text-gray-500 ml-12">Definiere Wenn-Dann-Regeln für automatische Aktionen</p>
        </header>

        <form id="rule-form" class="space-y-8">
            <input type="hidden" name="rule_id" value="<?= $rule['id'] ?? '' ?>">

            <!-- GRUNDEINSTELLUNGEN -->
            <section class="glass-card p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i data-lucide="settings" class="w-5 h-5 mr-2 text-gray-400"></i>
                    Grundeinstellungen
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name der Regel *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($rule['name'] ?? '') ?>"
                            placeholder="z.B. Geburtstags-Erinnerung"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Trigger-Status</label>
                        <select name="trigger_status"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary outline-none">
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= $status ?>" <?= ($rule['trigger_status'] ?? 'Interessent') === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" <?= ($rule['is_enabled'] ?? 1) ? 'checked' : '' ?>
                            class="w-5 h-5 rounded border-gray-300 text-secondary focus:ring-secondary">
                            <span class="text-sm font-medium text-gray-700">Regel aktivieren</span>
                        </label>
                    </div>
                </div>
            </section>

            <!-- WENN-BEDINGUNGEN -->
            <section class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i data-lucide="filter" class="w-5 h-5 mr-2 text-accent"></i>
                        WENN... <span class="text-gray-400 font-normal ml-2">(Bedingungen)</span>
                    </h2>
                    <button type="button" onclick="addCondition()"
                        class="flex items-center gap-2 px-3 py-2 bg-accent/10 text-accent rounded-lg text-sm font-medium hover:bg-accent/20 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Bedingung
                    </button>
                </div>

                <div id="conditions-container">
                    <?php if (empty($conditions)): ?>
                        <div class="text-center py-8 text-gray-400" id="no-conditions">
                            <i data-lucide="info" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p>Keine Bedingungen = Regel gilt immer für den gewählten Status</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conditions as $i => $cond): ?>
                            <div class="condition-card" data-index="<?= $i ?>">
                                <?php if ($i > 0): ?>
                                    <span class="operator-badge <?= ($cond['operator'] ?? 'AND') === 'AND' ? 'and' : 'or' ?> mb-3"
                                        onclick="toggleOperator(this)">
                                        <?= ($cond['operator'] ?? 'AND') === 'AND' ? 'UND' : 'ODER' ?>
                                    </span>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-3 items-center">
                                    <select class="cond-field px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                        <?php foreach ($availableFields as $key => $field): ?>
                                            <option value="<?= $key ?>" <?= $cond['field'] === $key ? 'selected' : '' ?>>
                                                <?= $field['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="cond-comparison px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                        <?php foreach ($comparisons as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= $cond['comparison'] === $key ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text"
                                        class="cond-value flex-1 min-w-[150px] px-3 py-2 border border-gray-200 rounded-lg text-sm"
                                        value="<?= htmlspecialchars($cond['value'] ?? '') ?>" placeholder="Wert">
                                    <button type="button" onclick="removeCondition(this)"
                                        class="p-2 hover:bg-red-50 rounded-lg">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- DANN-AKTIONEN -->
            <section class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i data-lucide="zap" class="w-5 h-5 mr-2 text-secondary"></i>
                        DANN... <span class="text-gray-400 font-normal ml-2">(Aktionen)</span>
                    </h2>
                    <button type="button" onclick="addAction()"
                        class="flex items-center gap-2 px-3 py-2 bg-secondary/10 text-secondary rounded-lg text-sm font-medium hover:bg-secondary/20 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Aktion
                    </button>
                </div>

                <div id="actions-container">
                    <?php if (empty($actions)): ?>
                        <div class="text-center py-8 text-gray-400" id="no-actions">
                            <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p>Mindestens eine Aktion ist erforderlich</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($actions as $i => $action): ?>
                            <?php $config = is_string($action['config'] ?? '') ? json_decode($action['config'], true) : ($action['config'] ?? []); ?>
                            <div class="action-card" data-index="<?= $i ?>">
                                <div class="flex flex-wrap gap-3 items-start">
                                    <select class="action-type px-3 py-2 border border-gray-200 rounded-lg text-sm"
                                        onchange="updateActionConfig(this)">
                                        <?php foreach ($actionTypes as $key => $type): ?>
                                            <option value="<?= $key ?>" <?= $action['action_type'] === $key ? 'selected' : '' ?>>
                                                <?= $type['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="action-config flex-1 flex flex-wrap gap-2">
                                        <!-- Config-Felder werden per JS eingefügt -->
                                    </div>
                                    <button type="button" onclick="removeAction(this)" class="p-2 hover:bg-red-50 rounded-lg">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ACTIONS -->
            <div class="flex items-center justify-between">
                <a href="index.php?page=automation"
                    class="px-6 py-3 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    Abbrechen
                </a>
                <button type="submit"
                    class="px-8 py-3 bg-secondary text-white rounded-xl font-medium hover:bg-secondary/90 transition-colors flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Speichern
                </button>
            </div>
        </form>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>

    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // Verfügbare Felder und Aktionstypen
        const availableFields = <?= json_encode($availableFields) ?>;
        const comparisons = <?= json_encode($comparisons) ?>;
        const actionTypes = <?= json_encode($actionTypes) ?>;

        let conditionIndex = <?= count($conditions) ?>;
        let actionIndex = <?= count($actions) ?>;

        // Bedingung hinzufügen
        function addCondition() {
            const container = document.getElementById('conditions-container');
            document.getElementById('no-conditions')?.remove();

            const isFirst = container.querySelectorAll('.condition-card').length === 0;

            const html = `
                <div class="condition-card" data-index="${conditionIndex}">
                    ${!isFirst ? '<span class="operator-badge and mb-3" onclick="toggleOperator(this)">UND</span>' : ''}
                    <div class="flex flex-wrap gap-3 items-center">
                        <select class="cond-field px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            ${Object.entries(availableFields).map(([k, v]) => `<option value="${k}">${v.label}</option>`).join('')}
                        </select>
                        <select class="cond-comparison px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            ${Object.entries(comparisons).map(([k, v]) => `<option value="${k}">${v}</option>`).join('')}
                        </select>
                        <input type="text" class="cond-value flex-1 min-w-[150px] px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Wert">
                        <button type="button" onclick="removeCondition(this)" class="p-2 hover:bg-red-50 rounded-lg">
                            <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                        </button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            lucide.createIcons();
            conditionIndex++;
        }

        function removeCondition(btn) {
            btn.closest('.condition-card').remove();
        }

        function toggleOperator(badge) {
            if (badge.classList.contains('and')) {
                badge.classList.remove('and');
                badge.classList.add('or');
                badge.textContent = 'ODER';
            } else {
                badge.classList.remove('or');
                badge.classList.add('and');
                badge.textContent = 'UND';
            }
        }

        // Aktion hinzufügen
        function addAction() {
            const container = document.getElementById('actions-container');
            document.getElementById('no-actions')?.remove();

            const html = `
                <div class="action-card" data-index="${actionIndex}">
                    <div class="flex flex-wrap gap-3 items-start">
                        <select class="action-type px-3 py-2 border border-gray-200 rounded-lg text-sm" onchange="updateActionConfig(this)">
                            ${Object.entries(actionTypes).map(([k, v]) => `<option value="${k}">${v.label}</option>`).join('')}
                        </select>
                        <div class="action-config flex-1 flex flex-wrap gap-2"></div>
                        <button type="button" onclick="removeAction(this)" class="p-2 hover:bg-red-50 rounded-lg">
                            <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                        </button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
            lucide.createIcons();

            // Config-Felder für ersten Typ generieren
            const card = container.lastElementChild;
            updateActionConfig(card.querySelector('.action-type'));
            actionIndex++;
        }

        function removeAction(btn) {
            btn.closest('.action-card').remove();
        }

        function updateActionConfig(select) {
            const card = select.closest('.action-card');
            const configContainer = card.querySelector('.action-config');
            const actionType = select.value;
            const typeInfo = actionTypes[actionType];

            if (!typeInfo || !typeInfo.config_fields) {
                configContainer.innerHTML = '';
                return;
            }

            let html = '';
            for (const [key, field] of Object.entries(typeInfo.config_fields)) {
                if (field.type === 'select' && field.options) {
                    const options = Array.isArray(field.options)
                        ? field.options.map(o => `<option value="${o}">${o}</option>`).join('')
                        : Object.entries(field.options).map(([k, v]) => `<option value="${k}">${v}</option>`).join('');
                    html += `<select class="config-${key} px-3 py-2 border border-gray-200 rounded-lg text-sm" data-key="${key}">${options}</select>`;
                } else if (field.type === 'textarea') {
                    html += `<textarea class="config-${key} px-3 py-2 border border-gray-200 rounded-lg text-sm flex-1 min-w-[200px]" data-key="${key}" placeholder="${field.label}" rows="1"></textarea>`;
                } else {
                    html += `<input type="${field.type || 'text'}" class="config-${key} px-3 py-2 border border-gray-200 rounded-lg text-sm" data-key="${key}" placeholder="${field.label}" ${field.default ? `value="${field.default}"` : ''}>`;
                }
            }
            configContainer.innerHTML = html;
        }

        // Daten sammeln
        function collectConditions() {
            return Array.from(document.querySelectorAll('.condition-card')).map((card, i) => ({
                group_id: 0,
                operator: i === 0 ? 'AND' : (card.querySelector('.operator-badge')?.classList.contains('or') ? 'OR' : 'AND'),
                field: card.querySelector('.cond-field').value,
                comparison: card.querySelector('.cond-comparison').value,
                value: card.querySelector('.cond-value').value
            }));
        }

        function collectActions() {
            return Array.from(document.querySelectorAll('.action-card')).map(card => {
                const config = {};
                card.querySelectorAll('[data-key]').forEach(el => {
                    config[el.dataset.key] = el.value;
                });
                return {
                    action_type: card.querySelector('.action-type').value,
                    config: config
                };
            });
        }

        // Form Submit
        document.getElementById('rule-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('conditions', JSON.stringify(collectConditions()));
            formData.append('actions', JSON.stringify(collectActions()));

            const ruleId = formData.get('rule_id');
            const endpoint = ruleId ? 'automation_update_custom' : 'automation_create_rule';

            fetch(`index.php?page=${endpoint}`, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Gespeichert!', 'success');
                        setTimeout(() => window.location.href = 'index.php?page=automation', 1500);
                    } else {
                        showToast(data.error || 'Fehler', 'error');
                    }
                });
        });

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-gray-700';
            toast.className = `${bgColor} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2`;
            toast.innerHTML = `<i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}" class="w-4 h-4"></i><span>${message}</span>`;
            container.appendChild(toast);
            lucide.createIcons();
            setTimeout(() => toast.remove(), 4000);
        }

        // Bestehende Aktionen initialisieren
        document.querySelectorAll('.action-type').forEach(select => updateActionConfig(select));
    </script>

</body>

</html>