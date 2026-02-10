<?php
// Workflows-Übersicht - Dashboard-Style Design
$templates = $data['templates'] ?? [];
$activeInstances = $data['active_instances'] ?? [];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/layout/head.php'; ?>
    <style>
        .workflow-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .workflow-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-secondary), var(--color-primary));
            transition: width 0.5s ease;
        }

        .offset-badge {
            font-size: 0.65rem;
            padding: 2px 6px;
        }

        /* Autocomplete styles */
        #contact-suggestions {
            position: absolute;
            z-index: 100;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            margin-top: 0.25rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .suggestion-item:hover {
            background-color: #f9fafb;
            color: var(--color-secondary);
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold font-display text-gray-800 flex items-center">
                    <i data-lucide="git-branch" class="mr-3 text-secondary w-8 h-8"></i>
                    Abläufe
                </h1>
                <p class="text-gray-500 mt-1">
                    Workflow-Vorlagen für <span class="highlighter">automatisierte ToDo-Erstellung</span>
                </p>
            </div>

            <div class="flex gap-3">
                <label
                    class="glass-card px-4 py-2 cursor-pointer hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <i data-lucide="upload" class="w-4 h-4 text-gray-500"></i>
                    <span class="text-sm font-medium text-gray-700">Importieren</span>
                    <input type="file" accept=".json" class="hidden" id="import-file" onchange="importWorkflow(this)">
                </label>
                <a href="index.php?page=workflow_edit"
                    class="glass-card px-4 py-2 bg-secondary text-white hover:bg-secondary/90 transition-colors flex items-center gap-2 rounded-xl">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Neuer Ablauf</span>
                </a>
            </div>
        </header>

        <!-- TABS -->
        <div class="flex gap-2 mb-8 border-b border-gray-200">
            <a href="index.php?page=automation"
                class="px-4 py-3 text-gray-500 hover:text-gray-700 font-medium border-b-2 border-transparent">
                Automatisierungen
            </a>
            <a href="index.php?page=workflows" class="px-4 py-3 text-secondary font-medium border-b-2 border-secondary">
                Abläufe
            </a>
        </div>

        <!-- AKTIVE ABLÄUFE -->
        <?php if (!empty($activeInstances)): ?>
            <section class="glass-card p-6 md:p-8 mb-8 relative overflow-hidden">
                <h2 class="text-xl font-bold font-display text-gray-800 mb-6 flex items-center">
                    <i data-lucide="play-circle" class="w-5 h-5 mr-2 text-accent"></i>
                    Aktive Abläufe
                    <span class="ml-2 text-sm font-normal text-gray-400">(
                        <?= count($activeInstances) ?>)
                    </span>
                </h2>
                <?php echo getDoodle('Fortschritt', 'doodle doodle-coral w-14 h-14 -top-2 -right-4 rotate-12 opacity-10'); ?>

                <div class="space-y-4">
                    <?php foreach ($activeInstances as $instance): ?>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 hover:border-secondary/30 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center">
                                        <i data-lucide="calendar-check" class="w-5 h-5 text-secondary"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">
                                            <?= htmlspecialchars($instance['template_name']) ?>
                                        </h4>
                                        <?php if ($instance['contact_name']): ?>
                                            <p class="text-xs text-gray-500">
                                                <i data-lucide="user" class="w-3 h-3 inline mr-1"></i>
                                                <?= htmlspecialchars($instance['contact_name']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-800">
                                        📅
                                        <?= date('d.m.Y', strtotime($instance['target_date'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-400">Zieldatum</div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width: <?= $instance['progress']['percent'] ?>%">
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    <?= $instance['progress']['completed'] ?>/
                                    <?= $instance['progress']['total'] ?> Aufgaben erledigt
                                </span>
                                <div class="flex gap-2">
                                    <a href="index.php?page=workflow_instance&id=<?= $instance['id'] ?>"
                                        class="text-xs text-secondary hover:underline">Details</a>
                                    <button onclick="cancelInstance(<?= $instance['id'] ?>)"
                                        class="text-xs text-red-500 hover:underline">Abbrechen</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- WORKFLOW-VORLAGEN -->
        <section class="glass-card p-6 md:p-8 relative overflow-hidden">
            <h2 class="text-xl font-bold font-display text-gray-800 mb-6 flex items-center">
                <i data-lucide="folder" class="w-5 h-5 mr-2 text-primary"></i>
                Ablauf-Vorlagen
            </h2>
            <?php echo getDoodle('Ordner', 'doodle doodle-purple w-14 h-14 -top-2 -right-4 -rotate-6 opacity-10'); ?>

            <?php if (empty($templates)): ?>
                <div class="text-center py-12">
                    <i data-lucide="inbox" class="w-16 h-16 text-gray-200 mx-auto mb-4"></i>
                    <p class="text-gray-500">Noch keine Ablauf-Vorlagen vorhanden.</p>
                    <a href="index.php?page=workflow_edit"
                        class="inline-flex items-center gap-2 mt-4 text-secondary hover:underline">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Ersten Ablauf erstellen
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($templates as $template): ?>
                        <div class="workflow-card bg-white rounded-xl border border-gray-100 p-5 relative">
                            <!-- Icon & Title -->
                            <div class="flex items-start gap-3 mb-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-<?= $template['color'] ?? 'secondary' ?>/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="<?= $template['icon'] ?? 'calendar' ?>"
                                        class="w-6 h-6 text-<?= $template['color'] ?? 'secondary' ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-800 truncate">
                                        <?= htmlspecialchars($template['name']) ?>
                                    </h3>
                                    <p class="text-xs text-gray-400 line-clamp-2">
                                        <?= htmlspecialchars($template['description'] ?: 'Keine Beschreibung') ?>
                                    </p>
                                </div>
                                <?php if ($template['is_system']): ?>
                                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full">System</span>
                                <?php endif; ?>
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="list-checks" class="w-3 h-3"></i>
                                    <?= $template['step_count'] ?> Schritte
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar-range" class="w-3 h-3"></i>
                                    <?= $template['min_offset'] ?> bis +
                                    <?= $template['max_offset'] ?> Tage
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <button
                                    onclick="openStartModal(<?= $template['id'] ?>, '<?= htmlspecialchars($template['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($template['start_date_label'], ENT_QUOTES) ?>')"
                                    class="flex items-center gap-1 px-3 py-2 bg-secondary text-white rounded-lg text-sm font-medium hover:bg-secondary/90 transition-colors">
                                    <i data-lucide="play" class="w-4 h-4"></i>
                                    Starten
                                </button>
                                <div class="flex gap-1">
                                    <a href="index.php?page=workflow_edit&id=<?= $template['id'] ?>"
                                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Bearbeiten">
                                        <i data-lucide="pencil" class="w-4 h-4 text-gray-400"></i>
                                    </a>
                                    <button onclick="duplicateTemplate(<?= $template['id'] ?>)"
                                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Duplizieren">
                                        <i data-lucide="copy" class="w-4 h-4 text-gray-400"></i>
                                    </button>
                                    <a href="index.php?page=workflow_export&id=<?= $template['id'] ?>"
                                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Exportieren">
                                        <i data-lucide="download" class="w-4 h-4 text-gray-400"></i>
                                    </a>
                                    <?php if (!$template['is_system']): ?>
                                        <button onclick="deleteTemplate(<?= $template['id'] ?>)"
                                            class="p-2 hover:bg-red-50 rounded-lg transition-colors" title="Löschen">
                                            <i data-lucide="trash-2" class="w-4 h-4 text-red-400"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/layout/footer.php'; ?>

    <!-- START MODAL -->
    <div id="start-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold font-display text-gray-800 flex items-center gap-2">
                        <i data-lucide="play-circle" class="w-5 h-5 text-secondary"></i>
                        <span id="modal-title">Ablauf starten</span>
                    </h3>
                    <button onclick="closeStartModal()" class="p-2 hover:bg-gray-100 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
                    </button>
                </div>
            </div>

            <form id="start-form" class="p-6 space-y-5">
                <input type="hidden" name="template_id" id="modal-template-id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" id="modal-date-label">Startdatum</label>
                    <input type="date" name="target_date" id="modal-date" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Verknüpfter Kontakt <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" id="contact-search" placeholder="Kontakt suchen..." autocomplete="off"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none">
                    <input type="hidden" name="contact_id" id="modal-contact-id">
                    <div id="contact-suggestions" class="hidden"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Notizen <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea name="notes" rows="2" placeholder="Optionale Notizen..."
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-secondary focus:ring-2 focus:ring-secondary/20 outline-none resize-none"></textarea>
                </div>

                <div id="preview-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vorschau der ToDos</label>
                    <div id="preview-list" class="space-y-2 max-h-48 overflow-y-auto bg-gray-50 rounded-xl p-3"></div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeStartModal()"
                        class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-3 bg-secondary text-white rounded-xl font-medium hover:bg-secondary/90 transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="play" class="w-4 h-4"></i>
                        Jetzt starten
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // Modal Functions
        function openStartModal(templateId, templateName, dateLabel) {
            document.getElementById('modal-template-id').value = templateId;
            document.getElementById('modal-title').textContent = templateName + ' starten';
            document.getElementById('modal-date-label').textContent = dateLabel || 'Startdatum';
            document.getElementById('modal-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('start-modal').classList.remove('hidden');
            document.getElementById('start-modal').classList.add('flex');
            loadPreview();
        }

        function closeStartModal() {
            document.getElementById('start-modal').classList.add('hidden');
            document.getElementById('start-modal').classList.remove('flex');
            document.getElementById('preview-container').classList.add('hidden');

            // Reset contact search
            document.getElementById('contact-search').value = '';
            document.getElementById('modal-contact-id').value = '';
            document.getElementById('contact-suggestions').classList.add('hidden');
        }

        // Preview laden wenn Datum sich ändert
        document.getElementById('modal-date').addEventListener('change', loadPreview);

        function loadPreview() {
            const templateId = document.getElementById('modal-template-id').value;
            const targetDate = document.getElementById('modal-date').value;

            if (!templateId || !targetDate) return;

            fetch(`index.php?page=workflow_preview&template_id=${templateId}&target_date=${targetDate}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.steps) {
                        const container = document.getElementById('preview-list');
                        container.innerHTML = data.steps.map(step => `
                            <div class="flex items-center justify-between bg-white p-2 rounded-lg text-sm">
                                <span class="text-gray-700">${step.title}</span>
                                <span class="text-gray-400">${step.formatted_date}</span>
                            </div>
                        `).join('');
                        document.getElementById('preview-container').classList.remove('hidden');
                    }
                });
        }

        // Contact Autocomplete
        let debounceTimer;
        const contactSearch = document.getElementById('contact-search');
        const contactSuggestions = document.getElementById('contact-suggestions');
        const modalContactId = document.getElementById('modal-contact-id');

        contactSearch.addEventListener('input', function () {
            const query = this.value.trim();

            // Wenn leer, ID zurücksetzen
            if (query === '') {
                modalContactId.value = '';
                contactSuggestions.innerHTML = '';
                contactSuggestions.classList.add('hidden');
                return;
            }

            // Debounce
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetch(`index.php?page=workflow_contacts&q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(contacts => {
                        if (contacts.length > 0) {
                            contactSuggestions.innerHTML = contacts.map(c => `
                                <div class="suggestion-item font-medium" data-id="${c.id}" data-name="${c.name}">
                                    ${c.name}
                                </div>
                            `).join('');
                            contactSuggestions.classList.remove('hidden');
                        } else {
                            contactSuggestions.innerHTML = '<div class="p-3 text-sm text-gray-500">Keine Kontakte gefunden</div>';
                            contactSuggestions.classList.remove('hidden');
                        }
                    });
            }, 300);
        });

        // Klick auf Vorschlag
        contactSuggestions.addEventListener('click', function (e) {
            const item = e.target.closest('.suggestion-item');
            if (item) {
                const id = item.dataset.id;
                const name = item.dataset.name;

                contactSearch.value = name;
                modalContactId.value = id;
                contactSuggestions.classList.add('hidden');
            }
        });

        // Click outside suggestions list
        document.addEventListener('click', function (e) {
            if (!contactSearch.contains(e.target) && !contactSuggestions.contains(e.target)) {
                contactSuggestions.classList.add('hidden');
            }
        });

        // Form Submit
        document.getElementById('start-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('index.php?page=workflow_start', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Ablauf gestartet! ${data.tasks_created} ToDos erstellt.`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.error || 'Fehler beim Starten', 'error');
                    }
                });
        });

        // Template Actions
        function duplicateTemplate(id) {
            fetch('index.php?page=workflow_duplicate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ablauf dupliziert!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.error || 'Fehler', 'error');
                    }
                });
        }

        function deleteTemplate(id) {
            if (!confirm('Diesen Ablauf wirklich löschen?')) return;

            fetch('index.php?page=workflow_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ablauf gelöscht', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.error || 'Fehler', 'error');
                    }
                });
        }

        function cancelInstance(id) {
            if (!confirm('Diesen laufenden Ablauf abbrechen?')) return;

            fetch('index.php?page=workflow_cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ablauf abgebrochen', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.error || 'Fehler', 'error');
                    }
                });
        }

        function importWorkflow(input) {
            if (!input.files[0]) return;

            const formData = new FormData();
            formData.append('file', input.files[0]);

            fetch('index.php?page=workflow_import', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ablauf importiert!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.error || 'Import fehlgeschlagen', 'error');
                    }
                });

            input.value = '';
        }

        // Toast Notification
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-gray-700';

            toast.className = `${bgColor} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2 animate-slide-in`;
            toast.innerHTML = `
                <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info'}" class="w-4 h-4"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => toast.remove(), 4000);
        }
    </script>

</body>

</html>