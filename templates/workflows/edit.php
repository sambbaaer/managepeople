<?php
// Workflow-Template Editor
$template = $data['template'] ?? null;
$steps = $data['steps'] ?? [];
$isNew = $data['is_new'] ?? true;

$icons = ['calendar', 'party-popper', 'users', 'package', 'gift', 'heart', 'star', 'zap', 'target', 'flag'];
$colors = [
    'secondary' => 'Türkis',
    'primary' => 'Pink',
    'accent' => 'Orange',
    'purple' => 'Violett',
    'blue' => 'Blau',
    'green' => 'Grün'
];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
    <style>
        .step-card {
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .step-card:active {
            cursor: grabbing;
        }

        .step-card.dragging {
            opacity: 0.5;
            transform: scale(1.02);
        }

        .timeline-line {
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--color-secondary), var(--color-primary));
        }

        .icon-picker-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .icon-picker-btn:hover,
        .icon-picker-btn.selected {
            border-color: var(--color-secondary);
            background: var(--color-secondary-light);
        }

        .color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s;
        }

        .color-dot:hover,
        .color-dot.selected {
            transform: scale(1.2);
            border-color: #374151;
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="index.php?page=workflows" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-gray-400"></i>
                </a>
                <h1 class="text-2xl font-bold font-display text-gray-800">
                    <?= $isNew ? 'Neuer Ablauf' : 'Ablauf bearbeiten' ?>
                </h1>
            </div>
        </header>

        <form id="template-form" class="space-y-8">
            <input type="hidden" name="id" value="<?= $template['id'] ?? '' ?>">

            <!-- GRUNDEINSTELLUNGEN -->
            <section class="glass-card p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i data-lucide="settings" class="w-5 h-5 mr-2 text-gray-400"></i>
                    Grundeinstellungen
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($template['name'] ?? '') ?>"
                            placeholder="z.B. Freshdate"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Datum-Label</label>
                        <input type="text" name="start_date_label"
                            value="<?= htmlspecialchars($template['start_date_label'] ?? 'Startdatum') ?>"
                            placeholder="z.B. Freshdate-Datum"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                        <textarea name="description" rows="2" placeholder="Optionale Beschreibung..."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none resize-none"><?= htmlspecialchars($template['description'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($icons as $icon): ?>
                                <button type="button"
                                    class="icon-picker-btn <?= ($template['icon'] ?? 'calendar') === $icon ? 'selected' : '' ?>"
                                    data-icon="<?= $icon ?>" onclick="selectIcon('<?= $icon ?>')">
                                    <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-gray-600"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icon" id="selected-icon"
                            value="<?= $template['icon'] ?? 'calendar' ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Farbe</label>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($colors as $colorKey => $colorName): ?>
                                <button type="button"
                                    class="color-dot bg-<?= $colorKey ?> <?= ($template['color'] ?? 'secondary') === $colorKey ? 'selected' : '' ?>"
                                    data-color="<?= $colorKey ?>" title="<?= $colorName ?>"
                                    onclick="selectColor('<?= $colorKey ?>')">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="color" id="selected-color"
                            value="<?= $template['color'] ?? 'secondary' ?>">
                    </div>
                </div>
            </section>

            <!-- SCHRITTE -->
            <section class="glass-card p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i data-lucide="list-checks" class="w-5 h-5 mr-2 text-gray-400"></i>
                        Schritte
                    </h2>
                    <button type="button" onclick="addStep()"
                        class="flex items-center gap-2 px-4 py-2 bg-secondary text-white rounded-xl text-sm font-medium hover:bg-secondary/90 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Schritt hinzufügen
                    </button>
                </div>

                <div id="steps-container" class="space-y-3 relative">
                    <?php if (!empty($steps)): ?>
                        <div class="timeline-line"></div>
                    <?php endif; ?>

                    <?php foreach ($steps as $index => $step): ?>
                        <div class="step-card bg-white border border-gray-100 rounded-xl p-4 pl-12 relative"
                            data-step-id="<?= $step['id'] ?>">
                            <div
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-secondary flex items-center justify-center text-white text-xs font-bold z-10">
                                <?= $index + 1 ?>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <input type="text" placeholder="Schritt-Titel"
                                        value="<?= htmlspecialchars($step['title']) ?>"
                                        class="step-title w-full font-medium text-gray-800 border-0 outline-none bg-transparent focus:bg-gray-50 rounded px-2 py-1 -mx-2">
                                    <input type="text" placeholder="Beschreibung (optional)"
                                        value="<?= htmlspecialchars($step['description'] ?? '') ?>"
                                        class="step-description w-full text-sm text-gray-500 border-0 outline-none bg-transparent focus:bg-gray-50 rounded px-2 py-1 -mx-2 mt-1">
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <input type="number" value="<?= $step['days_offset'] ?>"
                                            class="step-offset w-16 px-2 py-1 border border-gray-200 rounded-lg text-center text-sm">
                                        <span class="text-sm text-gray-500">Tage</span>
                                    </div>

                                    <select class="step-priority px-2 py-1 border border-gray-200 rounded-lg text-sm">
                                        <option value="normal" <?= $step['priority'] === 'normal' ? 'selected' : '' ?>>Normal
                                        </option>
                                        <option value="high" <?= $step['priority'] === 'high' ? 'selected' : '' ?>>Hoch
                                        </option>
                                        <option value="urgent" <?= $step['priority'] === 'urgent' ? 'selected' : '' ?>>Dringend
                                        </option>
                                    </select>

                                    <button type="button" onclick="deleteStep(this)" class="p-2 hover:bg-red-50 rounded-lg">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($steps)): ?>
                    <div id="empty-state" class="text-center py-12 text-gray-400">
                        <i data-lucide="list-plus" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                        <p>Noch keine Schritte vorhanden.</p>
                        <p class="text-sm mt-1">Füge Schritte hinzu, um den Ablauf zu definieren.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ACTIONS -->
            <div class="flex items-center justify-between">
                <a href="index.php?page=workflows"
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
        let stepCounter = <?= count($steps) ?>;

        function selectIcon(icon) {
            document.querySelectorAll('.icon-picker-btn').forEach(btn => btn.classList.remove('selected'));
            document.querySelector(`[data-icon="${icon}"]`).classList.add('selected');
            document.getElementById('selected-icon').value = icon;
        }

        function selectColor(color) {
            document.querySelectorAll('.color-dot').forEach(dot => dot.classList.remove('selected'));
            document.querySelector(`[data-color="${color}"]`).classList.add('selected');
            document.getElementById('selected-color').value = color;
        }

        function addStep() {
            stepCounter++;
            const container = document.getElementById('steps-container');
            const emptyState = document.getElementById('empty-state');

            if (emptyState) emptyState.remove();

            // Add timeline if first step
            if (!container.querySelector('.timeline-line')) {
                const line = document.createElement('div');
                line.className = 'timeline-line';
                container.appendChild(line);
            }

            const stepHtml = `
                <div class="step-card bg-white border border-gray-100 rounded-xl p-4 pl-12 relative" data-step-id="new-${stepCounter}">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-secondary flex items-center justify-center text-white text-xs font-bold z-10">
                        ${stepCounter}
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <input type="text" placeholder="Schritt-Titel" 
                                   class="step-title w-full font-medium text-gray-800 border-0 outline-none bg-transparent focus:bg-gray-50 rounded px-2 py-1 -mx-2">
                            <input type="text" placeholder="Beschreibung (optional)"
                                   class="step-description w-full text-sm text-gray-500 border-0 outline-none bg-transparent focus:bg-gray-50 rounded px-2 py-1 -mx-2 mt-1">
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <input type="number" value="0"
                                       class="step-offset w-16 px-2 py-1 border border-gray-200 rounded-lg text-center text-sm">
                                <span class="text-sm text-gray-500">Tage</span>
                            </div>
                            
                            <select class="step-priority px-2 py-1 border border-gray-200 rounded-lg text-sm">
                                <option value="normal">Normal</option>
                                <option value="high">Hoch</option>
                                <option value="urgent">Dringend</option>
                            </select>
                            
                            <button type="button" onclick="deleteStep(this)" class="p-2 hover:bg-red-50 rounded-lg">
                                <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', stepHtml);
            lucide.createIcons();
            updateStepNumbers();
        }

        function deleteStep(btn) {
            const card = btn.closest('.step-card');
            card.remove();
            updateStepNumbers();
        }

        function updateStepNumbers() {
            document.querySelectorAll('.step-card').forEach((card, index) => {
                const badge = card.querySelector('.absolute.w-6');
                if (badge) badge.textContent = index + 1;
            });
        }

        // Form Submit
        document.getElementById('template-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Collect steps
            const steps = [];
            document.querySelectorAll('.step-card').forEach((card, index) => {
                steps.push({
                    id: card.dataset.stepId.startsWith('new-') ? null : card.dataset.stepId,
                    title: card.querySelector('.step-title').value,
                    description: card.querySelector('.step-description').value,
                    days_offset: parseInt(card.querySelector('.step-offset').value) || 0,
                    priority: card.querySelector('.step-priority').value,
                    sort_order: index
                });
            });

            formData.append('steps', JSON.stringify(steps));

            // Save template first
            const templateResponse = await fetch('index.php?page=workflow_save', {
                method: 'POST',
                body: formData
            });
            const templateResult = await templateResponse.json();

            if (!templateResult.success) {
                showToast(templateResult.error || 'Fehler beim Speichern', 'error');
                return;
            }

            const templateId = templateResult.template_id;

            // Save steps
            for (const step of steps) {
                const stepData = new FormData();
                stepData.append('template_id', templateId);
                if (step.id) stepData.append('step_id', step.id);
                stepData.append('title', step.title);
                stepData.append('description', step.description);
                stepData.append('days_offset', step.days_offset);
                stepData.append('priority', step.priority);
                stepData.append('sort_order', step.sort_order);

                await fetch('index.php?page=workflow_save_step', {
                    method: 'POST',
                    body: stepData
                });
            }

            showToast('Ablauf gespeichert!', 'success');
            setTimeout(() => window.location.href = 'index.php?page=workflows', 1500);
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
    </script>

</body>

</html>