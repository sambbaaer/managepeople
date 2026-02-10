<?php
// templates/tasks/form.php
$prefill = $prefill ?? [
    'due_date' => '',
    'contact_id' => '',
    'contact_name' => '',
    'redirect_to' => 'index.php?page=tasks'
];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50">
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- Breadcrumb / Back -->
        <div class="mb-6">
            <a href="<?= htmlspecialchars($prefill['redirect_to']) ?>"
                class="inline-flex items-center text-gray-500 hover:text-primary transition-colors text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Zurück
            </a>
        </div>

        <!-- Form Card -->
        <div class="max-w-2xl mx-auto">
            <div class="glass-card p-6 md:p-8">
                <h1 class="text-3xl font-bold font-display text-gray-800 mb-6 flex items-center">
                    <i data-lucide="plus-circle" class="w-8 h-8 mr-3 text-secondary"></i>
                    Neue Aufgabe / Termin
                </h1>

                <form action="index.php?page=task_create" method="POST" class="space-y-6">
                    <!-- Hidden redirect field -->
                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($prefill['redirect_to']) ?>">

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="edit-3" class="w-4 h-4 inline mr-1 text-gray-400"></i>
                            Titel <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required placeholder="Was ist zu tun?"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none text-gray-800 font-medium"
                            autofocus>
                    </div>

                    <!-- Contact -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="user" class="w-4 h-4 inline mr-1 text-gray-400"></i>
                            Kontakt (optional)
                        </label>
                        <input type="text" name="contact_name" list="contacts_list" placeholder="Kontakt suchen..."
                            value="<?= htmlspecialchars($prefill['contact_name']) ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
                        <datalist id="contacts_list">
                            <?php foreach ($allContacts as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>">
                                <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" name="contact_id" value="<?= htmlspecialchars($prefill['contact_id']) ?>">
                    </div>

                    <!-- Date and Priority -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Due Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="calendar" class="w-4 h-4 inline mr-1 text-gray-400"></i>
                                Fälligkeitsdatum
                            </label>
                            <input type="date" name="due_date" value="<?= htmlspecialchars($prefill['due_date']) ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none">
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="flag" class="w-4 h-4 inline mr-1 text-gray-400"></i>
                                Priorität
                            </label>
                            <select name="priority"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none bg-white">
                                <option value="low">Niedrig</option>
                                <option value="medium" selected>Mittel</option>
                                <option value="high">Hoch</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="file-text" class="w-4 h-4 inline mr-1 text-gray-400"></i>
                            Beschreibung (optional)
                        </label>
                        <textarea name="description" rows="4" placeholder="Zusätzliche Notizen oder Details..."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary outline-none resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse md:flex-row justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= htmlspecialchars($prefill['redirect_to']) ?>"
                            class="px-6 py-3 text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors text-center">
                            Abbrechen
                        </a>
                        <button type="submit"
                            class="px-6 py-3 bg-secondary hover:bg-teal-500 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center">
                            <i data-lucide="check" class="w-5 h-5 mr-2"></i>
                            Aufgabe erstellen
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>